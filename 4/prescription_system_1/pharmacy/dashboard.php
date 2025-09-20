<?php
$page_title = 'Pharmacy Dashboard';
require_once '../includes/header.php';

// Check if user is logged in and is a pharmacy
if (!isLoggedIn() || !isPharmacy()) {
    redirect('login.php');
}

$pharmacy_id = $_SESSION['user_id'];

// Get pharmacy statistics
$stats = [];

// Total prescriptions received
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions");
$stmt->execute();
$stats['total_prescriptions'] = $stmt->fetch()['count'];

// Pending prescriptions (not quoted yet)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE status = 'pending'");
$stmt->execute();
$stats['pending_prescriptions'] = $stmt->fetch()['count'];

// Quotations sent by this pharmacy
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotations WHERE pharmacy_id = ?");
$stmt->execute([$pharmacy_id]);
$stats['quotations_sent'] = $stmt->fetch()['count'];

// Accepted quotations
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotations WHERE pharmacy_id = ? AND status = 'accepted'");
$stmt->execute([$pharmacy_id]);
$stats['accepted_quotations'] = $stmt->fetch()['count'];

// Recent prescriptions
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as user_name,
           u.contact_no,
           GROUP_CONCAT(pi.image_path) as images,
           q.total_amount,
           q.status as quotation_status
    FROM prescriptions p 
    JOIN users u ON p.user_id = u.id
    LEFT JOIN prescription_images pi ON p.id = pi.prescription_id 
    LEFT JOIN quotations q ON p.id = q.prescription_id AND q.pharmacy_id = ?
    GROUP BY p.id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$stmt->execute([$pharmacy_id]);
$recent_prescriptions = $stmt->fetchAll();
?>

<h1><i class="fas fa-store"></i> Welcome back, <?php echo sanitize($_SESSION['name']); ?>!</h1>

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
        <div class="stat-number"><?php echo $stats['quotations_sent']; ?></div>
        <div class="stat-label">Quotations Sent</div>
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
            <a href="view_prescriptions.php" class="btn btn-primary" style="padding: 2rem; text-align: center; display: block;">
                <i class="fas fa-prescription" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                View All Prescriptions
            </a>
            <a href="view_prescriptions.php?status=pending" class="btn btn-success" style="padding: 2rem; text-align: center; display: block;">
                <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                Pending Prescriptions
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
                <p>No prescriptions available yet.</p>
                <p>New prescriptions will appear here when patients upload them.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Images</th>
                            <th>Delivery Time</th>
                            <th>Status</th>
                            <th>Your Quotation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_prescriptions as $prescription): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($prescription['created_at'])); ?></td>
                            <td><?php echo sanitize($prescription['user_name']); ?></td>
                            <td><?php echo sanitize($prescription['contact_no']); ?></td>
                            <td>
                                <?php 
                                $images = explode(',', $prescription['images']);
                                echo count(array_filter($images)) . ' images';
                                ?>
                            </td>
                            <td><?php echo sanitize($prescription['delivery_time']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $prescription['status']; ?>">
                                    <?php echo ucfirst($prescription['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($prescription['total_amount']): ?>
                                    <span class="status-badge status-<?php echo $prescription['quotation_status']; ?>">
                                        LKR <?php echo number_format($prescription['total_amount'], 2); ?>
                                        (<?php echo ucfirst($prescription['quotation_status']); ?>)
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;">Not quoted</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$prescription['total_amount']): ?>
                                    <a href="create_quotation.php?prescription_id=<?php echo $prescription['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.8rem;">
                                        Create Quote
                                    </a>
                                <?php else: ?>
                                    <a href="view_prescriptions.php?prescription_id=<?php echo $prescription['id']; ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.8rem;">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="view_prescriptions.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> View All Prescriptions
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Business Tips -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-lightbulb"></i> Business Tips</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div style="background-color: #e9f7ef; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #28a745;">
                <h4><i class="fas fa-fast-forward" style="color: #28a745;"></i> Quick Response</h4>
                <p>Respond to prescriptions quickly to increase your chances of getting accepted by patients.</p>
            </div>
            
            <div style="background-color: #e7f3ff; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #007bff;">
                <h4><i class="fas fa-calculator" style="color: #007bff;"></i> Competitive Pricing</h4>
                <p>Provide competitive and accurate quotations with clear medicine descriptions.</p>
            </div>
            
            <div style="background-color: #fff3cd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h4><i class="fas fa-star" style="color: #ffc107;"></i> Quality Service</h4>
                <p>Ensure medicine quality and timely delivery to build patient trust and loyalty.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>