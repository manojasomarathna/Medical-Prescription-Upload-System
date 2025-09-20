<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Medical Prescription System</title>
    <link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : '../css/'; ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-pills"></i> MedPrescription</h1>
            <nav class="nav">
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isUser()): ?>
                            <li><a href="../user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="../user/upload_prescription.php"><i class="fas fa-upload"></i> Upload</a></li>
                            <li><a href="../user/view_quotations.php"><i class="fas fa-file-invoice"></i> Quotations</a></li>
                        <?php elseif (isPharmacy()): ?>
                            <li><a href="../pharmacy/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="../pharmacy/view_prescriptions.php"><i class="fas fa-prescription"></i> Prescriptions</a></li>
                        <?php endif; ?>
                        <li><a href="../api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo sanitize($_SESSION['name']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="../user/login.php"><i class="fas fa-sign-in-alt"></i> User Login</a></li>
                        <li><a href="../pharmacy/login.php"><i class="fas fa-store"></i> Pharmacy Login</a></li>
                        <li><a href="../user/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container" style="margin-top: 2rem;"><?php // Main content starts here ?>