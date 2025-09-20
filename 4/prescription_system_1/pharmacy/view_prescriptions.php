<?php
$page_title = 'View Prescriptions';
require_once '../includes/header.php';

// Check if user is logged in and is a pharmacy
if (!isLoggedIn() || !isPharmacy()) {
    redirect('login.php');
}

$pharmacy_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$prescription_id = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;

// Build query based on filters
$whereClause = '';
$params = [];

if ($status_filter) {
    $whereClause .= ' AND p.status = ?';
    $params[] = $status_filter;
}

if ($prescription_id) {
    $whereClause .= ' AND p.id = ?';
    $params[] = $prescription_id;
}

// Get prescriptions
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as user_name,
           u.email as user_email,
           u.contact_no,
           u.address as user_address,
           GROUP_CONCAT(pi.image_path) as images,
           q.id as quotation_id,
           q.total_amount,
           q.status as quotation_status
    FROM prescriptions p 
    JOIN users u ON p.user_id = u.id
    LEFT JOIN prescription_images pi ON p.id = pi.prescription_id 
    LEFT JOIN quotations q ON p.id = q.prescription_id AND q.pharmacy_id = ?
    WHERE 1=1 $whereClause
    GROUP BY p.id 
    ORDER BY p.created_at DESC
");

$stmt->execute(array_merge([$pharmacy_id], $params));
$prescriptions = $stmt->fetchAll();
?>

<h1><i class="fas fa-prescription"></i> Prescription Management</h1>

<!-- Filters -->
<div class="card">
    <div class="card-body" style="padding: 1rem;">
        <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div>
                <label for="status" style="margin-right: 0.5rem; font-weight: 600;">Filter by Status:</label>
                <select name="status" id="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="quoted" <?php echo $status_filter == 'quoted' ? 'selected' : ''; ?>>Quoted</option>
                    <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="padding: 8px 15px;">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
            
            <a href="view_prescriptions.php" class="btn btn-secondary" style="padding: 8px 15px;">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>
</div>

<!-- Prescriptions List -->
<div class="card">
    <div class="card-header">
        <h3>
            <i class="fas fa-list"></i> 
            <?php 
            if ($status_filter) {
                echo ucfirst($status_filter) . ' ';
            }
            ?>Prescriptions (<?php echo count($prescriptions); ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($prescriptions)): ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <i class="fas fa-prescription" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                <h3>No prescriptions found</h3>
                <?php if ($status_filter): ?>
                    <p>No prescriptions with status "<?php echo ucfirst($status_filter); ?>" found.</p>
                    <a href="view_prescriptions.php" class="btn btn-primary">View All Prescriptions</a>
                <?php else: ?>
                    <p>New prescriptions will appear here when patients upload them.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($prescriptions as $prescription): ?>
            <div style="border: 1px solid #e9ecef; border-radius: 10px; margin-bottom: 2rem; overflow: hidden;">
                <div style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #e9ecef;">
                    <div style="display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h4 style="margin: 0; color: #667eea;">
                                <i class="fas fa-user"></i> <?php echo sanitize($prescription['user_name']); ?>
                                <small style="color: #666; font-weight: normal;">
                                    (<?php echo date('M j, Y g:i A', strtotime($prescription['created_at'])); ?>)
                                </small>
                            </h4>
                            <p style="margin: 0.5rem 0 0 0; color: #666;">
                                <i class="fas fa-phone"></i> <?php echo sanitize($prescription['contact_no']); ?> |
                                <i class="fas fa-envelope"></i> <?php echo sanitize($prescription['user_email']); ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <span class="status-badge status-<?php echo $prescription['status']; ?>">
                                <?php echo ucfirst($prescription['status']); ?>
                            </span>
                            <?php if ($prescription['quotation_status']): ?>
                                <br><small style="color: #666;">Your Quote: 
                                    <span class="status-badge status-<?php echo $prescription['quotation_status']; ?>" style="margin-top: 4px;">
                                        <?php echo ucfirst($prescription['quotation_status']); ?>
                                    </span>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div style="padding: 1.5rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <div>
                            <h5><i class="fas fa-images"></i> Prescription Images</h5>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php 
                                $images = array_filter(explode(',', $prescription['images']));
                                foreach ($images as $image): 
                                ?>
                                <img src="../uploads/prescriptions/<?php echo sanitize($image); ?>" 
                                     class="prescription-image" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid #ddd;"
                                     alt="Prescription Image"
                                     onclick="showImageModal('../uploads/prescriptions/<?php echo sanitize($image); ?>')">
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($prescription['note']): ?>
                                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9ff; border-radius: 5px; border-left: 4px solid #667eea;">
                                    <h6><i class="fas fa-sticky-note"></i> Patient Note:</h6>
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
                            
                            <?php if ($prescription['total_amount']): ?>
                                <div style="margin-top: 1rem; padding: 1rem; background: #e9f7ef; border-radius: 5px; border: 1px solid #28a745;">
                                    <h6><i class="fas fa-calculator"></i> Your Quotation:</h6>
                                    <p style="font-size: 1.2rem; font-weight: bold; color: #28a745; margin: 0;">
                                        LKR <?php echo number_format($prescription['total_amount'], 2); ?>
                                    </p>
                                    <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                                        Status: <strong><?php echo ucfirst($prescription['quotation_status']); ?></strong>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid #e9ecef; padding-top: 1rem; text-align: right;">
                        <?php if (!$prescription['quotation_id']): ?>
                            <a href="create_quotation.php?prescription_id=<?php echo $prescription['id']; ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Quotation
                            </a>
                        <?php else: ?>
                            <a href="create_quotation.php?prescription_id=<?php echo $prescription['id']; ?>&edit=1" 
                               class="btn btn-secondary" style="margin-right: 1rem;">
                                <i class="fas fa-edit"></i> Edit Quotation
                            </a>
                            
                            <?php if ($prescription['quotation_status'] == 'accepted'): ?>
                                <span class="btn btn-success" style="cursor: default;">
                                    <i class="fas fa-check"></i> Order Accepted
                                </span>
                            <?php elseif ($prescription['quotation_status'] == 'rejected'): ?>
                                <span class="btn btn-danger" style="cursor: default;">
                                    <i class="fas fa-times"></i> Order Rejected
                                </span>
                            <?php else: ?>
                                <span class="btn btn-info" style="cursor: default;">
                                    <i class="fas fa-clock"></i> Awaiting Response
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Image Modal Script -->
<script>
function showImageModal(imageSrc) {
    // Create modal overlay
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