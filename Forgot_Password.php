<?php
// Start session
session_start();

// Database connection parameters
$db_host = "localhost";
$db_user = "farmville_user";
$db_pass = "your_secure_password";
$db_name = "farmville_db";

// Function to generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create database connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    
    // Check if identifier is email or phone
    $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    if ($is_email) {
        // Search by email
        $sql = "SELECT id, first_name, email FROM farmers WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $identifier);
    } else {
        // Search by phone
        $sql = "SELECT id, first_name, email FROM farmers WHERE phone = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $identifier);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user['id'], $token, $expires);
        $stmt->execute();
        
        // Send email with reset link
        $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
        $to = $user['email'];
        $subject = "FarmVille - Reset Your Password";
        $message = "
        <html>
        <head>
            <title>Reset Your FarmVille Password</title>
        </head>
        <body>
            <p>Hello " . htmlspecialchars($user['first_name']) . ",</p>
            <p>We received a request to reset your FarmVille account password.</p>
            <p>Please click the link below to reset your password:</p>
            <p><a href=\"" . $reset_link . "\">Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not request a password reset, please ignore this email.</p>
            <p>Thank you,<br>The FarmVille Team</p>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@farmville.com" . "\r\n";
        
        // Send email
        if(mail($to, $subject, $message, $headers)) {
            $_SESSION['message'] = "Password reset instructions have been sent to your email.";
        } else {
            $_SESSION['error'] = "Failed to send password reset email. Please try again.";
        }
    } else {
        $_SESSION['error'] = "No account found with that email/phone.";
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirect back to login page
    header("Location: index.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmVille - Forgot Password</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.3)), url('/api/placeholder/1920/1080');
      background-size: cover;
      background-position: center;
    }

    .forgot-container {
      background-color: white;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 420px;
    }

    .forgot-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .forgot-header h1 {
      color: #4088f5;
      font-size: 28px;
      margin-bottom: 10px;
    }

    .forgot-header p {
      color: #666;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: bold;
      margin-bottom: 8px;
      color: #555;
    }

    input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 15px;
    }

    input:focus {
      outline: none;
      border-color: #4088f5;
      box-shadow: 0 0 0 2px rgba(64, 136, 245, 0.2);
    }

    .btn {
      padding: 12px 20px;
      background-color: #4088f5;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      transition: background-color 0.3s;
      font-weight: bold;
    }

    .btn:hover {
      background-color: #3373d9;
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      color: #4088f5;
      text-decoration: none;
    }

    .back-link a:hover {
      text-decoration: underline;
    }

    .logo {
      width: 80px;
      height: 80px;
      margin: 0 auto 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #4088f5;
      border-radius: 50%;
      color: white;
      font-size: 36px;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="forgot-container">
    <div class="forgot-header">
      <div class="logo">🌾</div>
      <h1>Forgot Password</h1>
      <p>Enter your email or phone number to reset your password</p>
    </div>

    <form action="forgot_password.php" method="post">
      <div class="form-group">
        <label for="identifier">Email or Phone Number</label>
        <input type="text" id="identifier" name="identifier" placeholder="Enter your email or phone number" required>
      </div>

      <button type="submit" class="btn">Send Reset Link</button>
    </form>

    <div class="back-link">
      <a href="index.html">← Back to Login</a>
    </div>
  </div>
</body>
</html>