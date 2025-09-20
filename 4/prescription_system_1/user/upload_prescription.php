<?php
$page_title = 'Upload Prescription';
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn() || !isUser()) {
    redirect('login.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $note = trim($_POST['note']);
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_time = $_POST['delivery_time'];
    
    // Validation
    if (empty($delivery_address)) $errors[] = 'Delivery address is required';
    if (empty($delivery_time)) $errors[] = 'Delivery time is required';
    
    // Check if images are uploaded
    if (!isset($_FILES['prescription_images']) || empty($_FILES['prescription_images']['name'][0])) {
        $errors[] = 'At least one prescription image is required';
    }
    
    // Validate uploaded images
    $uploadedImages = [];
    if (isset($_FILES['prescription_images'])) {
        $totalFiles = count($_FILES['prescription_images']['name']);
        
        if ($totalFiles > 5) {
            $errors[] = 'Maximum 5 images allowed';
        }
        
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['prescription_images']['error'][$i] == UPLOAD_ERR_OK) {
                $fileName = $_FILES['prescription_images']['name'][$i];
                $fileSize = $_FILES['prescription_images']['size'][$i];
                $fileTmp = $_FILES['prescription_images']['tmp_name'][$i];
                $fileType = $_FILES['prescription_images']['type'][$i];
                
                // Check file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "File $fileName must be JPG, JPEG, or PNG";
                    continue;
                }
                
                // Check file size (5MB max)
                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = "File $fileName is too large (max 5MB)";
                    continue;
                }
                
                $uploadedImages[] = [
                    'name' => $fileName,
                    'tmp_name' => $fileTmp,
                    'size' => $fileSize,
                    'type' => $fileType
                ];
            }
        }
    }
    
    // If no errors, process the upload
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert prescription record
            $stmt = $pdo->prepare("INSERT INTO prescriptions (user_id, note, delivery_address, delivery_time, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $note, $delivery_address, $delivery_time]);
            $prescriptionId = $pdo->lastInsertId();
            
            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/prescriptions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Upload and save image paths
            foreach ($uploadedImages as $image) {
                $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
                $newFileName = $prescriptionId . '_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                    // Save to database
                    $stmt = $pdo->prepare("INSERT INTO prescription_images (prescription_id, image_path) VALUES (?, ?)");
                    $stmt->execute([$prescriptionId, $newFileName]);
                } else {
                    throw new Exception("Failed to upload image: " . $image['name']);
                }
            }
            
            $pdo->commit();
            $success = 'Prescription uploaded successfully! Pharmacies will review and send quotations soon.';
            
            // Clear form data
            $note = $delivery_address = $delivery_time = '';
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Upload failed: ' . $e->getMessage();
        }
    }
}

// Generate time slots for delivery
$timeSlots = [];
for ($hour = 8; $hour <= 20; $hour += 2) {
    $startTime = sprintf('%02d:00', $hour);
    $endTime = sprintf('%02d:00', $hour + 2);
    $timeSlots[] = $startTime . ' - ' . $endTime;
}
?>

<h1><i class="fas fa-upload"></i> Upload Prescription</h1>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-prescription"></i> New Prescription Upload</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> Upload Errors:</h4>
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
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="upload-form">
            <!-- Image Upload Section -->
            <div class="form-group">
                <label><i class="fas fa-images"></i> Prescription Images * (Max 5 images, 5MB each)</label>
                <div id="drop-area" class="file-upload-area">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                    <p>Drop prescription images here or click to select</p>
                    <input type="file" id="prescription_images" name="prescription_images[]" 
                           accept="image/jpeg,image/jpg,image/png" multiple required style="display: none;">
                    <button type="button" onclick="document.getElementById('prescription_images').click()" class="btn btn-secondary">
                        <i class="fas fa-folder-open"></i> Choose Images
                    </button>
                </div>
                <div id="image-preview" class="image-preview"></div>
            </div>
            
            <!-- Note Section -->
            <div class="form-group">
                <label for="note"><i class="fas fa-sticky-note"></i> Additional Notes (Optional)</label>
                <textarea id="note" name="note" class="form-control" rows="4" 
                          placeholder="Any special instructions or notes for the pharmacy..."><?php echo isset($note) ? sanitize($note) : ''; ?></textarea>
            </div>
            
            <!-- Delivery Address -->
            <div class="form-group">
                <label for="delivery_address"><i class="fas fa-map-marker-alt"></i> Delivery Address *</label>
                <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" 
                          placeholder="Enter complete delivery address with landmarks" required><?php echo isset($delivery_address) ? sanitize($delivery_address) : ''; ?></textarea>
            </div>
            
            <!-- Delivery Time -->
            <div class="form-group">
                <label for="delivery_time"><i class="fas fa-clock"></i> Preferred Delivery Time *</label>
                <select id="delivery_time" name="delivery_time" class="form-control" required>
                    <option value="">Select time slot</option>
                    <?php foreach ($timeSlots as $slot): ?>
                        <option value="<?php echo $slot; ?>" 
                                <?php echo (isset($delivery_time) && $delivery_time == $slot) ? 'selected' : ''; ?>>
                            <?php echo $slot; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #666;">Choose your preferred 2-hour delivery window</small>
            </div>
            
            <!-- Upload Instructions -->
            <div style="background-color: #e9ecff; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h4><i class="fas fa-info-circle"></i> Upload Guidelines:</h4>
                <ul style="margin: 1rem 0 0 2rem; color: #666;">
                    <li>Upload clear, well-lit images of your prescription</li>
                    <li>Maximum 5 images allowed</li>
                    <li>Supported formats: JPG, JPEG, PNG</li>
                    <li>Maximum file size: 5MB per image</li>
                    <li>Include all pages if multiple pages</li>
                    <li>Make sure text is readable and not blurry</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
                    <i class="fas fa-upload"></i> Upload Prescription
                </button>
                <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 1rem;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Instructions Card -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-question-circle"></i> What happens next?</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: bold;">1</div>
                <h4>Review Process</h4>
                <p>Pharmacies will review your prescription images and prepare accurate quotations</p>
            </div>
            
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: bold;">2</div>
                <h4>Get Quotations</h4>
                <p>You'll receive detailed quotations with medicine prices via email and dashboard</p>
            </div>
            
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: #fd7e14; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem; font-weight: bold;">3</div>
                <h4>Accept & Deliver</h4>
                <p>Choose your preferred quotation and get medicines delivered to your address</p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>