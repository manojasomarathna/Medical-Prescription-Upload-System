<?php
$page_title = 'View Quotations';
require_once '../includes/header.php';

// Check if user is logged in and is a regular user
if (!isLoggedIn() || !isUser()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$prescription_id = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;

// Handle quotation response
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $quotation_id = (int)$_POST['quotation_id'];
    $action = $_POST['action']; // 'accept' or 'reject'
    
    if (in_array($action, ['accept', 'reject'])) {
        try {
            $pdo->beginTransaction();
            
            // Update quotation status
            $stmt = $pdo->prepare("UPDATE quotations SET status = ? WHERE id = ?");
            $stmt->execute([$action . 'ed', $quotation_id]);
            
            // Update prescription status
            $stmt = $pdo->prepare("UPDATE prescriptions SET status = ? WHERE id = (SELECT prescription_id FROM quotations WHERE id = ?)");
            $stmt->execute([$action . 'ed', $quotation_id]);
            
            $pdo->commit();
            
            $message = $action == 'accept' ? 'Quotation accepted successfully! The pharmacy will proceed with your order.' 
                                          : 'Quotation rejected. You can wait for other quotations or upload a new prescription.';
            $_SESSION['message'] = $message;
            $_SESSION['message_type'] = 'success';
            
            // Redirect to avoid form resubmission
            redirect('view_quotations.php');
            
        } catch (Exception $e) {
            $pdo->rollback();
            $_SESSION['message'] = 'Failed to process your response. Please try again.';
            $_SESSION['message_type'] = 'error';
        }
    }
}

// Get user's prescriptions with quotations
$whereClause = '';
$params = [$user_id];

if ($prescription_id) {
    $whereClause = ' AND p.id = ?';
    $params[] = $prescription_id;
}

$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(pi.image_path) as images,
           q.id as quotation_id,
           q.total_amount,
           q.status as quotation_status,
           u.name as pharmacy_name
    FROM prescriptions p 
    LEFT JOIN prescription_images pi ON p.id = pi.prescription_id 
    LEFT JOIN quotations q ON p.id = q.prescription_id
    LEFT JOIN users u ON q.pharmacy_id = u.id
    WHERE p.user_id = ? $whereClause
    GROUP BY p.id, q.id
    ORDER BY p.created_at DESC, q.created_at DESC
");

$stmt->execute($params);
$prescriptions = $stmt->fetchAll();

// Group by prescription
$grouped_prescriptions = [];
foreach ($prescriptions as $row) {
    $pid = $row['id'];
    if (!isset($grouped_prescriptions[$pid])) {
        $grouped_prescriptions[$pid] = [
            'prescription' => $row,
            'quotations' => []
        ];
    }
    
    if ($row['quotation_id']) {
        $grouped_prescriptions[$pid]['quotations'][] = $row;
    }
}
?>

<h1><i class="fas fa-file-invoice"></i> My Quotations</h1>

<?php
// Show messages
if (isset($_SESSION['message'])) {
    $message_type = $_SESSION['message_type'] ?? 'info';
    echo "<div class='alert alert-{$message_type}'>";
    echo "<i class='fas fa-info-circle'></i> " . sanitize($_SESSION['message']);
    echo "</div>";
    unset($_SESSION['message'], $_SESSION['message_type']);
}
?>

<?php if (empty($grouped_prescriptions)): ?>
<div class="card">
    <div class="card-body" style="text-align: center; padding: 3rem;">
        <i class="fas fa-prescription" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
        <h3>No Prescriptions Found</h3>
        <p>You haven't uploaded any prescriptions yet.</p>
        <a href="upload_prescription.php" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload Your First Prescription
        </a>
    </div>
</div>
<?php else: ?>

<?php foreach ($grouped_prescriptions as $pid => $data): ?>
    <?php $prescription = $data['prescription']; ?>
    <?php $quotations = $data['quotations']; ?>
    
    <div class="card">
        <div class="card-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>
                    <i class="fas fa-prescription"></i> 
                    Prescription #<?php echo $pid; ?> 
                    <small style="color: #666; font-weight: normal;">
                        (<?php echo date('M j, Y g:i A', strtotime($prescription['created_at'])); ?>)
                    </small>
                </h3>
                <span class="status-badge status-<?php echo $prescription['status']; ?>">
                    <?php echo ucfirst($prescription['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Prescription Details -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h5><i class="fas fa-images"></i> Prescription Images</h5>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php 
                        $images = array_filter(explode(',', $prescription['images']));
                        foreach ($images as $image): 
                        ?>
                        <img src="../uploads/prescriptions/<?php echo sanitize($image); ?>" 
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid #ddd;"
                             onclick="showImageModal('../uploads/prescriptions/<?php echo sanitize($image); ?>')"
                             alt="Prescription">
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($prescription['note']): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8f9ff; border-radius: 5px;">
                        <h6><i class="fas fa-sticky-note"></i> Your Note:</h6>
                        <p style="margin: 0.5rem 0 0 0;"><?php echo nl2br(sanitize($prescription['note'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h5><i class="fas fa-truck"></i> Delivery Information</h5>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px;">
                        <p><strong>Address:</strong><br>
                        <?php echo nl2br(sanitize($prescription['delivery_address'])); ?></p>
                        
                        <p><strong>Preferred Time:</strong><br>
                        <i class="fas fa-clock"></i> <?php echo sanitize($prescription['delivery_time']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Quotations Section -->
            <div style="border-top: 1px solid #e9ecef; padding-top: 2rem;">
                <h5><i class="fas fa-calculator"></i> Quotations 
                    <span style="color: #666; font-size: 0.8rem; font-weight: normal;">
                        (<?php echo count($quotations); ?> received)
                    </span>
                </h5>
                
                <?php if (empty($quotations)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 5px;">
                        <i class="fas fa-hourglass-half" style="font-size: 2rem; color: #666; margin-bottom: 1rem;"></i>
                        <p><strong>No quotations received yet</strong></p>
                        <p style="color: #666; margin: 0;">Pharmacies are reviewing your prescription. You'll receive email notifications when quotations arrive.</p>
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($quotations as $quotation): ?>
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem; <?php echo $quotation['quotation_status'] == 'accepted' ? 'background: #e9f7ef; border-color: #28a745;' : ($quotation['quotation_status'] == 'rejected' ? 'background: #f8d7da; border-color: #dc3545;' : ''); ?>">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h6 style="margin: 0; color: #667eea;">
                                        <i class="fas fa-store"></i> <?php echo sanitize($quotation['pharmacy_name']); ?>
                                    </h6>
                                    <p style="margin: 0.25rem 0; color: #666; font-size: 0.9rem;">
                                        Received: <?php echo date('M j, Y g:i A', strtotime($quotation['created_at'])); ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                                        LKR <?php echo number_format($quotation['total_amount'], 2); ?>
                                    </div>
                                    <span class="status-badge status-<?php echo $quotation['quotation_status']; ?>">
                                        <?php echo ucfirst($quotation['quotation_status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Get quotation items -->
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
                            $stmt->execute([$quotation['quotation_id']]);
                            $items = $stmt->fetchAll();
                            ?>
                            
                            <?php if (!empty($items)): ?>
                            <div style="margin: 1rem 0;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                    <thead>
                                        <tr style="background: #f8f9fa;">
                                            <th style="padding: 8px; text-align: left; border-bottom: 1px solid #dee2e6;">Medicine</th>
                                            <th style="padding: 8px; text-align: center; border-bottom: 1px solid #dee2e6;">Qty</th>
                                            <th style="padding: 8px; text-align: right; border-bottom: 1px solid #dee2e6;">Unit Price</th>
                                            <th style="padding: 8px; text-align: right; border-bottom: 1px solid #dee2e6;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td style="padding: 8px; border-bottom: 1px solid #f1f3f4;"><?php echo sanitize($item['drug_name']); ?></td>
                                            <td style="padding: 8px; text-align: center; border-bottom: 1px solid #f1f3f4;"><?php echo $item['quantity']; ?></td>
                                            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #f1f3f4;">LKR <?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td style="padding: 8px; text-align: right; border-bottom: 1px solid #f1f3f4;">LKR <?php echo number_format($item['total_price'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Action buttons -->
                            <?php if ($quotation['quotation_status'] == 'sent'): ?>
                            <div style="text-align: right; margin-top: 1rem;">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to accept this quotation?')">
                                    <input type="hidden" name="quotation_id" value="<?php echo $quotation['quotation_id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success" style="margin-right: 0.5rem;">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this quotation?')">
                                    <input type="hidden" name="quotation_id" value="<?php echo $quotation['quotation_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                            <?php elseif ($quotation['quotation_status'] == 'accepted'): ?>
                            <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: rgba(40, 167, 69, 0.1); border-radius: 5px;">
                                <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.5rem; margin-right: 0.5rem;"></i>
                                <strong style="color: #28a745;">Order Confirmed!</strong>
                                <p style="margin: 0.5rem 0 0 0; color: #155724;">The pharmacy will prepare and deliver your medicines as scheduled.</p>
                            </div>
                            <?php elseif ($quotation['quotation_status'] == 'rejected'): ?>
                            <div style="text-align: center; margin-top: 1rem; padding: 1rem; background: rgba(220, 53, 69, 0.1); border-radius: 5px;">
                                <i class="fas fa-times-circle" style="color: #dc3545; font-size: 1.5rem; margin-right: 0.5rem;"></i>
                                <strong style="color: #dc3545;">Quotation Rejected</strong>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Image Modal Script -->
<script>
function showImageModal(imageSrc) {
    const modal = document.createElement('div');
    modal.className = 'image-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        cursor: pointer;
    `;
    
    modal.innerHTML = `
        <img src="${imageSrc}" style="max-width: 90%; max-height: 90%; object-fit: contain;">
        <span style="position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; cursor: pointer;">&times;</span>
    `;
    
    modal.addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    document.body.appendChild(modal);
}
</script>

<?php require_once '../includes/footer.php'; ?>