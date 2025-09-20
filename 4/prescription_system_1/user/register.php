<?php
$page_title = 'User Registration';
require_once '../includes/header.php';

// If already logged in, redirect
if (isLoggedIn()) {
    if (isUser()) {
        redirect('dashboard.php');
    } else {
        redirect('../pharmacy/dashboard.php');
    }
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = trim($_POST['address']);
    $contact_no = trim($_POST['contact_no']);
    $dob = $_POST['dob'];
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($contact_no) || !preg_match('/^[0-9+\-\s()]{10,15}$/', $contact_no)) $errors[] = 'Valid contact number is required';
    if (empty($dob)) $errors[] = 'Date of birth is required';
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered';
        }
    }
    
    // Insert user if no errors
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, address, contact_no, dob, user_type) VALUES (?, ?, ?, ?, ?, ?, 'user')");
            $stmt->execute([$name, $email, $hashed_password, $address, $contact_no, $dob]);
            
            $success = 'Registration successful! You can now login.';
            
            // Clear form data
            $name = $email = $address = $contact_no = $dob = '';
        } catch (Exception $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-plus"></i> User Registration</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> Registration Errors:</h4>
                <ul style="margin: 0.5rem 0 0 2rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo sanitize($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Success!</h4>
                <p><?php echo sanitize($success); ?></p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? sanitize($name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? sanitize($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_no">Contact Number *</label>
                        <input type="tel" id="contact_no" name="contact_no" class="form-control" placeholder="0771234567" value="<?php echo isset($contact_no) ? sanitize($contact_no) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth *</label>
                        <input type="date" id="dob" name="dob" class="form-control" value="<?php echo isset($dob) ? sanitize($dob) : ''; ?>" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label for="address">Full Address *</label>
                        <textarea id="address" name="address" class="form-control" rows="3" placeholder="Enter your complete address" required><?php echo isset($address) ? sanitize($address) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                        <small style="color: #666;">Must be at least 6 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="margin-right: 1rem;">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Already have an account?
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>