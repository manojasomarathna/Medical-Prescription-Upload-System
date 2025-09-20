<?php
$page_title = 'Create Quotation';
require_once '../includes/header.php';

// Check if user is logged in and is a pharmacy
if (!isLoggedIn() || !isPharmacy()) {
    redirect('login.php');
}

$pharmacy_id = $_SESSION['user_id'];
$prescription_id = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;
$is_edit = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$prescription_id) {
    redirect('view_prescriptions.php');
}

// Get prescription details
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as user_name,
           u.email as user_email,
           u.contact_no,
           GROUP_CONCAT(pi.image_path) as images
    FROM prescriptions p 
    JOIN users u ON p.user_id = u.id
    LEFT JOIN prescription_images pi ON p.id = pi.prescription_id 
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$prescription_id]);
$prescription = $stmt->fetch();

if (!$prescription) {
    redirect('view_prescriptions.php');
}

// Check if quotation already exists
$existing_quotation = null;
$quotation_items = [];

$stmt = $pdo->prepare("SELECT * FROM quotations WHERE prescription_id = ? AND pharmacy_id = ?");
$stmt->execute([$prescription_id, $pharmacy_id]);
$existing_quotation = $stmt->fetch();

if ($existing_quotation) {
    $stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $stmt->execute([$existing_quotation['id']]);
    $quotation_items = $stmt->fetchAll();
    
    if (!$is_edit) {
        $page_title = 'View Quotation';
    }
}

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $drug_names = $_POST['drug_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];
    $total_amount = (float)($_POST['total_amount'] ?? 0);
    
    // Validation
    if (empty($drug_names) || empty($drug_names[0])) {
        $errors[] = 'At least one drug item is required';
    }
    
    if ($total_amount <= 0) {
        $errors[] = 'Total amount must be greater than 0';
    }
    
    // Validate each item
    $items = [];
    for ($i = 0; $i < count($drug_names); $i++) {
        if (!empty($drug_names[$i]) && !empty($quantities[$i]) && !empty($unit_prices[$i])) {
            $item_total = (float)$quantities[$i] * (float)$unit_prices[$i];
            $items[] = [
                'drug_name' => trim($drug_names[$i]),
                'quantity' => (int)$quantities[$i],
                'unit_price' => (float)$unit_prices[$i],
                'total_price' => $item_total
            ];
        }
    }
    
    if (empty($items)) {
        $errors[] = 'At least one complete drug item is required';
    }
    
    // Calculate total from items
    $calculated_total = array_sum(array_column($items, 'total_price'));
    if (abs($calculated_total - $total_amount) > 0.01) {
        $errors[] = 'Total amount does not match item calculations';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            if ($existing_quotation) {
                // Update existing quotation
                $stmt = $pdo->prepare("UPDATE quotations SET total_amount = ? WHERE id = ?");
                $stmt->execute([$total_amount, $existing_quotation['id']]);
                
                // Delete old items
                $stmt = $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
                $stmt->execute([$existing_quotation['id']]);
                
                $quotation_id = $existing_quotation['id'];
            } else {
                // Create new quotation
                $stmt = $pdo->prepare("INSERT INTO quotations (prescription_id, pharmacy_id, total_amount, status) VALUES (?, ?, ?, 'sent')");
                $stmt->execute([$prescription_id, $pharmacy_id, $total_amount]);
                $quotation_id = $pdo->lastInsertId();
                
                // Update prescription status
                $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'quoted' WHERE id = ?");
                $stmt->execute([$prescription_id]);
            }
            
            // Insert quotation items
            $stmt = $pdo->prepare("INSERT INTO quotation_items (quotation_id, drug_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([
                    $quotation_id,
                    $item['drug_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price']
                ]);
            }
            
            $pdo->commit();
            
            // Send email notification (basic implementation)
            $subject = "Quotation Received for Your Prescription";
            $message = "Dear {$prescription['user_name']},\n\n";
            $message .= "You have received a new quotation for your prescription.\n";
            $message .= "Total Amount: LKR " . number_format($total_amount, 2) . "\n\n";
            $message .= "Please log in to your account to view details and accept/reject the quotation.\n\n";
            $message .= "Thank you!";
            
            // In a real application, you would use a proper email library like PHPMailer
            mail($prescription['user_email'], $subject, $message);
            
            $success = $existing_quotation ? 'Quotation updated successfully!' : 'Quotation created and sent successfully!';
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Failed to save quotation: ' . $e->getMessage();
        }
    }
}
?>

<h1><i class="fas fa-calculator"></i> 
    <?php echo $existing_quotation && !$is_edit ? 'View' : ($existing_quotation ? 'Edit' : 'Create'); ?> 
    Quotation
</h1>

<!-- Prescription Details -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-prescription"></i> Prescription Details</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h5><i class="fas fa-user"></i> Patient Information</h5>
                <p><strong>Name:</strong> <?php echo sanitize($prescription['user_name']); ?></p>
                <p><strong>Email:</strong> <?php echo sanitize($prescription['user_email']); ?></p>
                <p><strong>Contact:</strong> <?php echo sanitize($prescription['contact_no']); ?></p>
                <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($prescription['created_at'])); ?></p>
                
                <?php if ($prescription['note']): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9ff; border-radius: 5px;">
                    <h6><i class="fas fa-sticky-note"></i> Patient Note:</h6>
                    <p><?php echo nl2br(sanitize($prescription['note'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h5><i class="fas fa-images"></i> Prescription Images</h5>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php 
                    $images = array_filter(explode(',', $prescription['images']));
                    foreach ($images as $image): 
                    ?>
                    <img src="../uploads/prescriptions/<?php echo sanitize($image); ?>" 
                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid #ddd;"
                         onclick="showImageModal('../uploads/prescriptions/<?php echo sanitize($image); ?>')"
                         alt="Prescription">
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                    <h6><i class="fas fa-truck"></i> Delivery Info:</h6>
                    <p><strong>Address:</strong><br><?php echo nl2br(sanitize($prescription['delivery_address'])); ?></p>
                    <p><strong>Time:</strong> <?php echo sanitize($prescription['delivery_time']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success">
    <h4><i class="fas fa-check-circle"></i> Success!</h4>
    <p><?php echo sanitize($success); ?></p>
    <a href="view_prescriptions.php" class="btn btn-primary">
        <i class="fas fa-list"></i> Back to Prescriptions
    </a>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <h4><i class="fas fa-exclamation-triangle"></i> Errors:</h4>
    <ul style="margin: 0.5rem 0 0 2rem;">
        <?php foreach ($errors as $error): ?>
            <li><?php echo sanitize($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Quotation Form -->
<?php if (!$existing_quotation || $is_edit): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-edit"></i> <?php echo $existing_quotation ? 'Edit' : 'Create'; ?> Quotation</h3>
    </div>
    <div class="card-body">
        <form method="POST" id="quotation-form">
            <div id="quotation-items">
                <?php if (!empty($quotation_items)): ?>
                    <?php foreach ($quotation_items as $item): ?>
                    <div class="quotation-item">
                        <div class="form-group" style="flex: 2;">
                            <label>Drug Name</label>
                            <input type="text" name="drug_name[]" class="form-control" value="<?php echo sanitize($item['drug_name']); ?>" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Quantity</label>
                            <input type="number" name="quantity[]" class="form-control" min="1" value="<?php echo $item['quantity']; ?>" required onchange="calculateItemTotal(this)">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Unit Price (LKR)</label>
                            <input type="number" name="unit_price[]" class="form-control" min="0" step="0.01" value="<?php echo $item['unit_price']; ?>" required onchange="calculateItemTotal(this)">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Total (LKR)</label>
                            <input type="number" name="item_total[]" class="form-control" value="<?php echo $item['total_price']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger" onclick="removeQuotationItem(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="quotation-item">
                        <div class="form-group" style="flex: 2;">
                            <label>Drug Name</label>
                            <input type="text" name="drug_name[]" class="form-control" placeholder="e.g., Amoxicillin 250mg" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Quantity</label>
                            <input type="number" name="quantity[]" class="form-control" min="1" required onchange="calculateItemTotal(this)">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Unit Price (LKR)</label>
                            <input type="number" name="unit_price[]" class="form-control" min="0" step="0.01" required onchange="calculateItemTotal(this)">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Total (LKR)</label>
                            <input type="number" name="item_total[]" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger" onclick="removeQuotationItem(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin: 2rem 0; text-align: center;">
                <button type="button" id="add-item" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Add Another Item
                </button>
            </div>
            
            <div style="background: #f8f9fa; padding: 2rem; border-radius: 10px; text-align: center;">
                <h3>Grand Total: <span id="grand-total">LKR 0.00</span></h3>
                <input type="hidden" name="total_amount" value="<?php echo $existing_quotation ? $existing_quotation['total_amount'] : '0'; ?>">
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
                    <i class="fas fa-paper-plane"></i> 
                    <?php echo $existing_quotation ? 'Update Quotation' : 'Send Quotation'; ?>
                </button>
                <a href="view_prescriptions.php" class="btn btn-secondary" style="margin-left: 1rem;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
<?php elseif ($existing_quotation): ?>
<!-- View Only Mode -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-invoice"></i> Quotation Details</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Drug Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotation_items as $item): ?>
                <tr>
                    <td><?php echo sanitize($item['drug_name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>LKR <?php echo number_format($item['unit_price'], 2); ?></td>
                    <td>LKR <?php echo number_format($item['total_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold; font-size: 1.1rem;">
                    <td colspan="3">Grand Total:</td>
                    <td>LKR <?php echo number_format($existing_quotation['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="create_quotation.php?prescription_id=<?php echo $prescription_id; ?>&edit=1" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Quotation
            </a>
            <a href="view_prescriptions.php" class="btn btn-secondary" style="margin-left: 1rem;">
                <i class="fas fa-list"></i> Back to Prescriptions
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Initialize total calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
});
</script>

<?php require_once '../includes/footer.php'; ?>