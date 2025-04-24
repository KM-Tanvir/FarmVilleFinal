<?php
// Start the session
session_start();

// Database connection parameters
$host = "localhost";
$dbname = "farmville_db";
$username = "farmville_user";
$password = "your_secure_password";

// Function to sanitize user inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate random reset token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    if (!empty($_POST["email"])) {
        $identifier = sanitize_input($_POST["email"]);
        $identifier_type = "email";
    } else if (!empty($_POST["phone"])) {
        $identifier = sanitize_input($_POST["phone"]);
        $identifier_type = "phone";
    } else {
        $_SESSION["reset_error"] = "Please provide either email or phone number.";
        header("Location: forgot_password.php");
        exit();
    }
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        
        // Set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if user exists
        $sql = "SELECT * FROM users WHERE $identifier_type = :identifier";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":identifier", $identifier);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $reset_token = generate_token();
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $sql = "INSERT INTO password_resets (user_id, reset_token, expiry) 
                    VALUES (:user_id, :reset_token, :expiry)
                    ON DUPLICATE KEY UPDATE 
                    reset_token = :reset_token, 
                    expiry = :expiry";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":user_id", $user["user_id"]);
            $stmt->bindParam(":reset_token", $reset_token);
            $stmt->bindParam(":expiry", $token_expiry);
            $stmt->execute();
            
            // Send reset email or SMS
            if ($identifier_type == "email") {
                // Email reset link
                $reset_link = "https://farmville.com/reset_password.php?token=" . $reset_token;
                $message = "Dear " . $user["first_name"] . ",\n\n";
                $message .= "You requested a password reset. Please click on the link below to reset your password:\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you did not request this, please ignore this email.\n\n";
                $message .= "Regards,\nFarmVille Team";
                
                $subject = "FarmVille Password Reset";
                $headers = "From: noreply@farmville.com";
                
                mail($identifier, $subject, $message, $headers);
            } else {
                // SMS reset code
                $reset_code = substr($reset_token, 0, 6);
                // Implement SMS sending here
                // This is just a placeholder
                $sms_message = "Your FarmVille password reset code is: " . $reset_code;
                // send_sms($identifier, $sms_message);
            }
            
            $_SESSION["reset_success"] = "Password reset instructions have been sent to your " . $identifier_type . ".";
            header("Location: reset_confirmation.php");
            exit();
        } else {
            $_SESSION["reset_error"] = "No user found with that " . $identifier_type . ".";
            header("Location: forgot_password.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION["reset_error"] = "Connection failed: " . $e->getMessage();
        header("Location: forgot_password.php");
        exit();
    }
    
    // Close connection
    $pdo = null;
} else {
    // Display forgot password form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmVille - Forgot Password</title>
    <style>
        /* Copy the same styles from the login page for consistency */
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

        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #4088f5;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
        }

        .form-group input:focus {
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #4088f5;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 15px;
            opacity: 0.7;
            position: relative;
        }

        .tab.active {
            opacity: 1;
            font-weight: bold;
            color: #4088f5;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #4088f5;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .error-message {
            color: #d9534f;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
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
    <div class="container">
        <div class="header">
            <div class="logo">🌾</div>
            <h1>Forgot Password</h1>
            <p>Enter your email or phone to reset your password</p>
        </div>

        <?php if (isset($_SESSION["reset_error"])): ?>
        <div class="error-message">
            <?php echo $_SESSION["reset_error"]; unset($_SESSION["reset_error"]); ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="tabs">
                <button type="button" class="tab active" data-tab="email-tab">Email</button>
                <button type="button" class="tab" data-tab="phone-tab">Phone</button>
            </div>

            <div id="email-tab" class="tab-content active">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email">
                </div>
            </div>

            <div id="phone-tab" class="tab-content">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number">
                </div>
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <div class="login-link">
            <a href="index.html">Back to Login</a>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current tab and corresponding content
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Update form inputs based on tab
                if (tabId === 'email-tab') {
                    document.getElementById('email').setAttribute('required', '');
                    document.getElementById('phone').removeAttribute('required');
                } else {
                    document.getElementById('phone').setAttribute('required', '');
                    document.getElementById('email').removeAttribute('required');
                }
            });
        });
    </script>
</body>
</html>
<?php
}
?>