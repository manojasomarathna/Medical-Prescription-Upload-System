// Main JavaScript file for Medical Prescription System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initFileUpload();
    initFormValidation();
    initQuotationCalculator();
    initImagePreview();
    initNotifications();
});

// File Upload Functionality
function initFileUpload() {
    const fileInput = document.getElementById('prescription_images');
    const dropArea = document.getElementById('drop-area');
    const previewArea = document.getElementById('image-preview');
    
    if (!fileInput || !dropArea) return;
    
    let uploadedFiles = [];
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);
    
    // Handle file input change
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight(e) {
        dropArea.classList.add('dragover');
    }
    
    function unhighlight(e) {
        dropArea.classList.remove('dragover');
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }
    
    function handleFiles(files) {
        const maxFiles = 5;
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        // Convert FileList to Array
        const fileArray = Array.from(files);
        
        // Check file count
        if (uploadedFiles.length + fileArray.length > maxFiles) {
            showNotification('You can upload maximum 5 images', 'error');
            return;
        }
        
        fileArray.forEach(file => {
            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                showNotification(`${file.name} is not a valid image file`, 'error');
                return;
            }
            
            // Validate file size
            if (file.size > maxSize) {
                showNotification(`${file.name} is too large. Maximum size is 5MB`, 'error');
                return;
            }
            
            uploadedFiles.push(file);
            previewFile(file);
        });
        
        updateFileInput();
        updateDropAreaText();
    }
    
    function previewFile(file) {
        if (!previewArea) return;
        
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const div = document.createElement('div');
            div.className = 'image-preview-item';
            div.style.position = 'relative';
            div.style.display = 'inline-block';
            
            div.innerHTML = `
                <img src="${reader.result}" alt="${file.name}" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <button type="button" class="remove-image" onclick="removeImage(this, '${file.name}')" 
                        style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 12px;">×</button>
                <div style="text-align: center; font-size: 0.8rem; margin-top: 4px; color: #666; word-break: break-all;">${file.name}</div>
            `;
            
            previewArea.appendChild(div);
        }
    }
    
    function updateFileInput() {
        // Create new DataTransfer object to update file input
        const dt = new DataTransfer();
        uploadedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    function updateDropAreaText() {
        const remainingFiles = 5 - uploadedFiles.length;
        const dropText = dropArea.querySelector('p');
        if (dropText) {
            if (remainingFiles > 0) {
                dropText.textContent = `Drop prescription images here or click to select (${remainingFiles} more allowed)`;
            } else {
                dropText.textContent = 'Maximum 5 images uploaded';
            }
        }
    }
    
    // Global function to remove images
    window.removeImage = function(button, fileName) {
        // Remove from uploadedFiles array
        uploadedFiles = uploadedFiles.filter(file => file.name !== fileName);
        
        // Remove preview element
        button.parentElement.remove();
        
        // Update file input
        updateFileInput();
        updateDropAreaText();
    }
}

// Image Preview for existing images
function initImagePreview() {
    const imageElements = document.querySelectorAll('.prescription-image');
    
    imageElements.forEach(img => {
        img.addEventListener('click', function() {
            showImageModal(this.src);
        });
        img.style.cursor = 'pointer';
    });
}

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

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let hasErrors = false;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    hasErrors = true;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !emailRegex.test(field.value)) {
                    field.classList.add('is-invalid');
                    hasErrors = true;
                }
            });
            
            // Password confirmation
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                hasErrors = true;
                showNotification('Passwords do not match', 'error');
            }
            
            if (hasErrors) {
                e.preventDefault();
                showNotification('Please fix the errors and try again', 'error');
            }
        });
    });
}

// Quotation Calculator
function initQuotationCalculator() {
    const quotationForm = document.getElementById('quotation-form');
    if (!quotationForm) return;
    
    let itemCount = 1;
    
    // Add new item button
    document.getElementById('add-item')?.addEventListener('click', function() {
        if (itemCount >= 10) {
            showNotification('Maximum 10 items allowed', 'error');
            return;
        }
        
        addQuotationItem();
        itemCount++;
    });
    
    function addQuotationItem() {
        const itemsContainer = document.getElementById('quotation-items');
        const newItem = document.createElement('div');
        newItem.className = 'quotation-item';
        newItem.innerHTML = `
            <div class="form-group" style="flex: 2;">
                <label>Drug Name</label>
                <input type="text" name="drug_name[]" class="form-control" required>
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
        `;
        
        itemsContainer.appendChild(newItem);
    }
    
    // Global functions for quotation
    window.calculateItemTotal = function(element) {
        const item = element.closest('.quotation-item');
        const quantity = item.querySelector('input[name="quantity[]"]').value;
        const unitPrice = item.querySelector('input[name="unit_price[]"]').value;
        const totalField = item.querySelector('input[name="item_total[]"]');
        
        const total = quantity * unitPrice;
        totalField.value = total.toFixed(2);
        
        calculateGrandTotal();
    }
    
    window.removeQuotationItem = function(button) {
        button.closest('.quotation-item').remove();
        itemCount--;
        calculateGrandTotal();
    }
    
    function calculateGrandTotal() {
        const itemTotals = document.querySelectorAll('input[name="item_total[]"]');
        let grandTotal = 0;
        
        itemTotals.forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        
        const grandTotalField = document.getElementById('grand-total');
        if (grandTotalField) {
            grandTotalField.textContent = `LKR ${grandTotal.toFixed(2)}`;
        }
        
        const hiddenTotal = document.querySelector('input[name="total_amount"]');
        if (hiddenTotal) {
            hiddenTotal.value = grandTotal.toFixed(2);
        }
    }
}

// Notifications
function initNotifications() {
    // Auto hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                      type === 'success' ? 'alert-success' : 'alert-info';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass}`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    alert.innerHTML = `
        <strong>${type === 'error' ? 'Error!' : type === 'success' ? 'Success!' : 'Info!'}</strong> ${message}
        <button type="button" style="float: right; background: none; border: none; font-size: 1.2rem;" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);