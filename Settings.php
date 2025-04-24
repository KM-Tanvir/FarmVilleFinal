<?php
include "db.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to change settings']);
    exit;
}

// Database connection details
$host = 'localhost';
$dbname = 'farm_management';
$username = 'db_user';
$password = 'db_password';

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Process form based on which tab was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    
    // Process profile settings
    if (isset($_POST['full_name']) && isset($_POST['email'])) {
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $jobTitle = sanitize($_POST['job_title']);
        $bio = sanitize($_POST['bio']);
        
        // Validate email
        if (!validateEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        try {
            // Update profile information
            $stmt = $pdo->prepare("UPDATE users SET 
                full_name = :full_name,
                email = :email,
                phone = :phone,
                job_title = :job_title,
                bio = :bio
                WHERE id = :user_id");
                
            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':job_title' => $jobTitle,
                ':bio' => $bio,
                ':user_id' => $userId
            ]);
            
            // Update session data
            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            
            $response['success'] = true;
        } catch (PDOException $e) {
            $response['message'] = 'Error updating profile: ' . $e->getMessage();
        }
    }
    
    // Process notification preferences
    $notificationSettings = [
        'notify_new_farmer' => isset($_POST['notify_new_farmer']) ? 1 : 0,
        'notify_product_update' => isset($_POST['notify_product_update']) ? 1 : 0,
        'notify_inventory' => isset($_POST['notify_inventory']) ? 1 : 0,
        'notify_market' => isset($_POST['notify_market']) ? 1 : 0,
        'alert_login' => isset($_POST['alert_login']) ? 1 : 0,
        'alert_backup' => isset($_POST['alert_backup']) ? 1 : 0,
        'alert_error' => isset($_POST['alert_error']) ? 1 : 0
    ];
    
    try {
        // Update notification settings
        foreach ($notificationSettings as $setting => $value) {
            $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_name, setting_value) 
                                  VALUES (:user_id, :setting_name, :setting_value)
                                  ON DUPLICATE KEY UPDATE setting_value = :setting_value");
            $stmt->execute([
                ':user_id' => $userId,
                ':setting_name' => $setting,
                ':setting_value' => $value
            ]);
        }
        
        $response['success'] = true;
    } catch (PDOException $e) {
        $response['message'] = 'Error updating notification settings: ' . $e->getMessage();
    }
    
    // Process system settings
    if (isset($_POST['items_per_page']) && isset($_POST['date_format'])) {
        $systemSettings = [
            'items_per_page' => sanitize($_POST['items_per_page']),
            'date_format' => sanitize($_POST['date_format']),
            'backup_frequency' => sanitize($_POST['backup_frequency']),
            'log_retention' => sanitize($_POST['log_retention'])
        ];
        
        try {
            // Update system settings
            foreach ($systemSettings as $setting => $value) {
                $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_name, setting_value) 
                                      VALUES (:user_id, :setting_name, :setting_value)
                                      ON DUPLICATE KEY UPDATE setting_value = :setting_value");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':setting_name' => $setting,
                    ':setting_value' => $value
                ]);
            }
            
            $response['success'] = true;
        } catch (PDOException $e) {
            $response['message'] = 'Error updating system settings: ' . $e->getMessage();
        }
    }
    
    // Process security settings (password change)
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Check if new passwords match
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }
        
        try {
            // Get current password hash
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id");
            $stmt->execute([':password_hash' => $newPasswordHash, ':user_id' => $userId]);
            
            $response['success'] = true;
            $response['message'] = 'Password updated successfully';
        } catch (PDOException $e) {
            $response['message'] = 'Error updating password: ' . $e->getMessage();
        }
    }
    
    // Process 2FA settings
    if (isset($_POST['enable_2fa'])) {
        $enable2fa = isset($_POST['enable_2fa']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, setting_name, setting_value) 
                                  VALUES (:user_id, :setting_name, :setting_value)
                                  ON DUPLICATE KEY UPDATE setting_value = :setting_value");
            $stmt->execute([
                ':user_id' => $userId,
                ':setting_name' => 'enable_2fa',
                ':setting_value' => $enable2fa
            ]);
            
            $response['success'] = true;
        } catch (PDOException $e) {
            $response['message'] = 'Error updating 2FA settings: ' . $e->getMessage();
        }
    }
    
    // Log the settings change
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity_type, details, ip_address) 
                              VALUES (:user_id, :activity_type, :details, :ip_address)");
        $stmt->execute([
            ':user_id' => $userId,
            ':activity_type' => 'settings_update',
            ':details' => 'User updated settings',
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        // Just log error, don't affect response
        error_log('Error logging activity: ' . $e->getMessage());
    }
}

// Return JSON response
echo json_encode($response);
?>