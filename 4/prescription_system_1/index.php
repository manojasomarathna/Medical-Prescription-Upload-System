<?php
$page_title = 'Home';
$css_path = 'css/';
require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-home"></i> Welcome to Medical Prescription System</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h3><i class="fas fa-users"></i> For Patients</h3>
                <p>Upload your prescriptions securely and get quotations from verified pharmacies. Track your prescription status and manage deliveries easily.</p>
                <div style="margin-top: 1rem;">
                    <a href="user/register.php" class="btn btn-primary" style="margin-right: 1rem;">
                        <i class="fas fa-user-plus"></i> Register as Patient
                    </a>
                    <a href="user/login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Patient Login
                    </a>
                </div>
            </div>
            
            <div>
                <h3><i class="fas fa-store"></i> For Pharmacies</h3>
                <p>View prescription uploads from patients, prepare accurate quotations, and manage your pharmacy business efficiently with our platform.</p>
                <div style="margin-top: 1rem;">
                    <a href="pharmacy/login.php" class="btn btn-primary">
                        <i class="fas fa-store"></i> Pharmacy Login
                    </a>
                </div>
            </div>
        </div>
        
        <div style="background-color: #f8f9ff; padding: 2rem; border-radius: 10px; border-left: 4px solid #667eea;">
            <h3><i class="fas fa-shield-alt"></i> Secure & Reliable</h3>
            <p>Your prescription data is encrypted and securely stored. We ensure privacy and compliance with healthcare data protection standards.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div style="text-align: center;">
                    <i class="fas fa-upload" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                    <h4>Easy Upload</h4>
                    <p>Upload up to 5 prescription images with notes and delivery preferences</p>
                </div>
                
                <div style="text-align: center;">
                    <i class="fas fa-calculator" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                    <h4>Quick Quotations</h4>
                    <p>Get detailed quotations from pharmacies within hours</p>
                </div>
                
                <div style="text-align: center;">
                    <i class="fas fa-truck" style="font-size: 3rem; color: #fd7e14; margin-bottom: 1rem;"></i>
                    <h4>Fast Delivery</h4>
                    <p>Choose your preferred 2-hour delivery time slot</p>
                </div>
                
                <div style="text-align: center;">
                    <i class="fas fa-bell" style="font-size: 3rem; color: #dc3545; margin-bottom: 1rem;"></i>
                    <h4>Email Notifications</h4>
                    <p>Get notified instantly about quotation updates</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js_path = 'js/';
require_once 'includes/footer.php';
?>