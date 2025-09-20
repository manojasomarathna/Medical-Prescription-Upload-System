<?php
$page_title = 'User Dashboard';
require_once '../includes/header.php';

// Check if user is logged in and is a regular user
if (!isLoggedIn() || !isUser()) {
    redirect('login.php');
}

// Get user statistics
$user_id = $_SESSION['user_id'];

$stats = [];

// Total prescriptions
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['total_prescriptions'] = $stmt->fetch()['count'];

// Pending prescriptions
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$stats['pending_prescriptions'] = $stmt->fetch()['count'];

// Quoted prescriptions
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE user_id = ? AND status = 'quoted'");
$stmt->execute([$user_id]);
$stats['quoted_prescriptions'] = $stmt->fetch()['count'];

// Accepted quotations
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE user_id = ? AND status = 'accepted'");
$stmt->execute([$user_id]);
$stats['accepted_quotations'] = $stmt->fetch()['count'];

// Recent prescriptions
$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(pi.image_path) as images,
           q.total_amount,
           q.status as quotation_status
    FROM prescriptions p 
    LEFT JOIN prescription_images pi ON p.id = pi.prescription_id 
    LEFT JOIN quotations q ON p.id = q.prescription_id
    WHERE p.user_id = ? 
    GROUP BY p.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_prescriptions = $stmt->fetchAll();
?>

<h1><i class="fas fa-tachometer-alt"></i> Welcome back, <?php echo sanitize($_SESSION['name']); ?>!</h1>

<!-- Dashboard Statistics -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_prescriptions']; ?></div>
        <div class="stat-label">Total Prescriptions</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['pending_prescriptions']; ?></div>
        <div class="stat-label">Pending Review</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['quoted_prescriptions']; ?></div>
        <div class="stat-label">Quotations Received</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['accepted_quotations']; ?></div>
        <div class="stat-label">Accepted Orders</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <a href="upload_prescription.php" class="btn btn-primary" style="padding: 2rem; text-align: center; display: block;">
                <i class="fas fa-upload" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                Upload New Prescription
            </a>
            <a href="view_quotations.php" class="btn btn-success" style="padding: 2rem; text-align: center; display: block;">
                <i class="fas fa-file-invoice" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                View Quotations
            </a>
        </div>
    </div>
</div>

<!-- Recent Prescriptions -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Recent Prescriptions</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recent_prescriptions)): ?>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-prescription" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>No prescriptions uploaded yet.</p>
                <a href="upload_prescription.php" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Your First Prescription
                </a>
            </div>
        <?php else: ?>
            <div class="table" style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Images</th>
                            <th>Delivery Address</th>
                            <th>Status</th>
                            <th>Quotation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_prescriptions as $prescription): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($prescription['created_at'])); ?></td>
                            <td>
                                <?php 
                                $images = explode(',', $prescription['images']);
                                echo count(array_filter($images)) . ' images';
                                ?>
                            </td>
                            <td><?php echo sanitize(substr($prescription['delivery_address'], 0, 50)) . '...'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $prescription['status']; ?>">
                                    <?php echo ucfirst($prescription['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($prescription['total_amount']): ?>
                                    LKR <?php echo number_format($prescription['total_amount'], 2); ?>
                                <?php else: ?>
                                    <span style="color: #666;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prescription['status'] == 'quoted'): ?>
                                    <a href="view_quotations.php?prescription_id=<?php echo $prescription['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.8rem;">
                                        View Quotation
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="view_quotations.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> View All Prescriptions
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>