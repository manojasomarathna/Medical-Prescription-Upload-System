<?php
$page_title = 'Pharmacy Login';
require_once '../includes/header.php';

// If already logged in, redirect
if (isLoggedIn()) {
    if (isPharmacy()) {
        redirect('dashboard.php');
    } else {
        redirect('../user/dashboard.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $errors[] = 'Please enter both email and password';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ? AND user_type = 'pharmacy'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $errors[] = 'Login failed. Please try again.';
        }
    }
}
?>

<div style="max-width: 500px; margin: 2rem auto;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-store"></i> Pharmacy Login</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Pharmacy Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? sanitize($email) : 'pharmacy@citymed.lk'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" value="password" required>
                </div>
                
                <div style="background-color: #cce7ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <small><i class="fas fa-info-circle"></i> <strong>Demo Credentials:</strong><br>
                    Email: pharmacy@citymed.lk<br>
                    Password: password</small>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-sign-in-alt"></i> Login to Pharmacy Panel
                    </button>
                    
                    <p><a href="../user/login.php">Login as Patient</a></p>
                    <p><a href="../index.php">Back to Home</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>