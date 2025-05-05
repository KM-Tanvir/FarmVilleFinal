<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For demo purposes, set customer ID to 1 if not logged in
if (!isset($_SESSION['CustomerID']) && !isset($_SESSION['customer_id'])) {
    $_SESSION['CustomerID'] = 1; // Default customer for demo
    $_SESSION['customer_id'] = 1; // Set both for compatibility
}

// Get customer ID from session
$customerId = $_SESSION['CustomerID'] ?? $_SESSION['customer_id'] ?? 1;

// Fetch customer data
try {
    $stmt = $pdo->prepare("SELECT * FROM customer_t WHERE CustomerID = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        die("Customer not found");
    }

    // Count orders
    $order_count = 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_t WHERE CustomerID = ?");
    $stmt->execute([$customerId]);
    $order_count = $stmt->fetchColumn();

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_profile'])) {
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $phone = $_POST['phone'];
            
            $stmt = $pdo->prepare("UPDATE customer_t SET first_name = ?, last_name = ?, phone = ? WHERE CustomerID = ?");
            $stmt->execute([$first_name, $last_name, $phone, $customerId]);
            
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            // Refresh customer data
            $stmt = $pdo->prepare("SELECT * FROM customer_t WHERE CustomerID = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $success_message = "Profile updated successfully!";
        } 
        elseif (isset($_POST['update_address'])) {
            $address_line1 = $_POST['address_line1'];
            $address_line2 = $_POST['address_line2'];
            $city = $_POST['city'];
            
            $stmt = $pdo->prepare("UPDATE customer_t SET address_line1 = ?, address_line2 = ?, city = ? WHERE CustomerID = ?");
            $stmt->execute([$address_line1, $address_line2, $city, $customerId]);
            
            // Refresh customer data
            $stmt = $pdo->prepare("SELECT * FROM customer_t WHERE CustomerID = ?");
            $stmt->execute([$customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $success_message = "Address updated successfully!";
        } 
        elseif (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (password_verify($current_password, $customer['password_hash'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE customer_t SET password_hash = ? WHERE CustomerID = ?");
                    $stmt->execute([$hashed_password, $customerId]);
                    $success_message = "Password changed successfully!";
                    
                    // Refresh customer data
                    $stmt = $pdo->prepare("SELECT * FROM customer_t WHERE CustomerID = ?");
                    $stmt->execute([$customerId]);
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "New passwords do not match!";
                }
            } else {
                $error_message = "Current password is incorrect!";
            }
        }
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}


function generateInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
    }
    return $initials;
}

$initials = generateInitials($customer['first_name'] . ' ' . $customer['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FarmVille — My Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --primary: #2c8dad;
      --secondary: #1f408e;
      --dark: #120854;
      --light: #f8f9fa;
      --success: #28a745;
      --warning: #ffc107;
      --danger: #dc3545;
      --info: #17a2b8;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      color: #2c3e50;
      line-height: 1.6;
      font-size: 16px;
      overflow-x: hidden;
      min-height: 100vh;
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(to right, var(--dark), var(--secondary));
      color: white;
      padding: 15px 5%;
      flex-wrap: wrap;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      position: relative;
      z-index: 100;
    }

    .logo-img {
      height: 150px;
      transition: transform 0.3s ease;
    }

    .logo-img:hover {
      transform: scale(1.05);
    }

    .search-bar {
      display: flex;
      flex: 1;
      max-width: 500px;
      margin: 0 30px;
      position: relative;
    }

    .search-bar input {
      flex: 1;
      padding: 12px 20px;
      border-radius: 30px;
      border: none;
      font-size: 16px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .search-bar input:focus {
      outline: none;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .search-bar button {
      position: absolute;
      right: 5px;
      top: 5px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .search-bar button:hover {
      transform: scale(1.05);
      background: linear-gradient(to right, var(--secondary), var(--dark));
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 25px;
    }

    .cart-icon {
      position: relative;
      display: flex;
      align-items: center;
    }

    .cart-icon a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      transition: transform 0.3s ease;
    }

    .cart-icon a:hover {
      transform: translateY(-3px);
    }

    .cart-icon svg {
      width: 24px;
      height: 24px;
      fill: white;
    }

    .cart-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: var(--danger);
      color: white;
      border-radius: 50%;
      padding: 3px 8px;
      font-size: 0.8rem;
      font-weight: bold;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .account-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 2px solid rgba(255,255,255,0.3);
    }

    .account-icon:hover {
      transform: scale(1.1);
      box-shadow: 0 0 15px rgba(255,255,255,0.4);
    }

    .sidebar {
      height: 100%;
      width: 0;
      position: fixed;
      z-index: 1000;
      top: 0;
      left: 0;
      background: linear-gradient(to bottom, var(--dark), var(--secondary));
      overflow-x: hidden;
      transition: 0.4s;
      padding-top: 120px;
      box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2);
    }

    .sidebar a {
      padding: 15px 25px;
      text-decoration: none;
      font-size: 18px;
      color: white;
      display: block;
      transition: 0.3s;
      position: relative;
      overflow: hidden;
    }

    .sidebar a::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: 0.5s;
    }

    .sidebar a:hover::before {
      left: 100%;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .sidebar a.active {
      font-weight: 600;
      background-color: rgba(255, 255, 255, 0.15);
      border-left: 4px solid var(--primary);
    }

    .sidebar a i {
      margin-right: 10px;
      width: 25px;
      text-align: center;
    }

    .sidebar-toggle {
      position: fixed;
      top: 120px;
      left: 0;
      width: 45px;
      height: 45px;
      background-color: var(--secondary);
      color: white;
      border-radius: 0 8px 8px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 999;
      box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
      transition: all 0.4s ease;
      font-size: 1.5rem;
    }

    .sidebar-toggle:hover {
      background-color: var(--dark);
      transform: translateX(5px);
    }

    .main-content {
      transition: margin-left 0.4s;
      padding: 30px 5%;
      position: relative;
    }

    body.sidebar-open .main-content {
      margin-left: 280px;
    }

    body.sidebar-open .sidebar {
      width: 280px;
    }

    body.sidebar-open .sidebar-toggle {
      left: 280px;
    }

    .content-box {
      background-color: white;
      margin: 30px auto;
      padding: 30px;
      width: 100%;
      max-width: 1200px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .content-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(to bottom, var(--primary), var(--secondary));
      transition: width 0.3s ease;
    }

    .content-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .content-box:hover::before {
      width: 8px;
    }

    .content-box h2 {
      margin-bottom: 20px;
      color: var(--dark);
      position: relative;
      display: inline-block;
    }

    .content-box h2::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 50px;
      height: 3px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 3px;
    }

    .profile-header {
      display: flex;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .profile-picture {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid rgba(44, 141, 173, 0.1);
      margin-right: 30px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--primary);
      color: white;
      font-size: 2.5rem;
      font-weight: bold;
    }

    .profile-picture:hover {
      transform: scale(1.05);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .profile-info h3 {
      font-size: 1.5rem;
      color: var(--dark);
      margin-bottom: 5px;
    }

    .profile-info p {
      color: #666;
      margin-bottom: 10px;
    }

    .profile-stats {
      display: flex;
      gap: 20px;
      margin-top: 10px;
    }

    .stat-item {
      text-align: center;
    }

    .stat-value {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--primary);
    }

    .stat-label {
      font-size: 0.9rem;
      color: #666;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-col {
      flex: 1 1 300px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(44, 141, 173, 0.2);
    }

    .form-control[readonly] {
      background-color: #f8f9fa;
      border-color: #eee;
      cursor: not-allowed;
    }

    .btn {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      padding: 12px 25px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: inline-block;
      transition: all 0.3s ease;
      cursor: pointer;
      border: none;
      font-size: 16px;
      box-shadow: 0 4px 15px rgba(44, 141, 173, 0.3);
      position: relative;
      overflow: hidden;
    }

    .btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transform: translateX(-100%);
      transition: transform 0.5s ease;
    }

    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(44, 141, 173, 0.4);
    }

    .btn:hover::after {
      transform: translateX(100%);
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--primary);
      color: var(--primary);
      box-shadow: none;
    }

    .btn-outline:hover {
      background: var(--primary);
      color: white;
    }

    .btn-danger {
      background: linear-gradient(to right, var(--danger), #d9534f);
    }

    .btn-danger:hover {
      background: linear-gradient(to right, #d9534f, var(--danger));
    }

    .button-group {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 30px;
    }

    .address-card {
      background-color: #fcfcff;
      border: 1px solid rgba(0, 0, 0, 0.03);
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      transition: all 0.3s ease;
      position: relative;
    }

    .address-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .address-card h3 {
      margin-bottom: 15px;
      color: var(--dark);
      font-size: 1.1rem;
      position: relative;
    }

    .address-card h3::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 30px;
      height: 2px;
      background: var(--primary);
    }

    .address-card p {
      margin: 8px 0;
      color: #555;
    }

    .address-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .default-badge {
      display: inline-block;
      padding: 5px 10px;
      background-color: rgba(40, 167, 69, 0.1);
      color: var(--success);
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
      margin-left: 10px;
    }

    .section-header {
      margin: 30px 0 20px;
      color: var(--dark);
      position: relative;
      padding-bottom: 10px;
    }

    .section-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 3px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 3px;
    }

    footer {
      background: linear-gradient(to right, var(--dark), var(--secondary));
      color: white;
      padding: 40px 5%;
      font-size: 14px;
      margin-top: 60px;
      position: relative;
    }

    footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(to right, var(--primary), #4ecdc4);
    }

    .footer-content {
      max-width: 1200px;
      margin: auto;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 40px;
      text-align: left;
    }

    .footer-section {
      flex: 1 1 200px;
      min-width: 200px;
    }

    .footer-section h4 {
      font-weight: 600;
      margin-bottom: 15px;
      color: white;
      font-size: 1.1rem;
      position: relative;
      display: inline-block;
    }

    .footer-section h4::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 40px;
      height: 2px;
      background: var(--primary);
    }

    .footer-content p,
    .footer-content a {
      color: rgba(255, 255, 255, 0.8);
      margin: 8px 0;
      text-decoration: none;
      transition: all 0.3s ease;
      display: block;
    }

    .footer-content a:hover {
      color: white;
      transform: translateX(5px);
    }

    .social-icons {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }

    .social-icons a {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .social-icons a:hover {
      background: var(--primary);
      transform: translateY(-3px);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    section, button, a.btn {
      animation: fadeIn 0.8s ease-out forwards;
    }

    @media (max-width: 992px) {
      .navbar {
        flex-direction: column;
        padding: 15px;
      }
      
      .search-bar {
        margin: 15px 0;
        width: 100%;
        max-width: 100%;
      }
      
      .header-right {
        margin-top: 15px;
      }
      
      body.sidebar-open .main-content {
        margin-left: 0;
      }
      
      body.sidebar-open .sidebar {
        width: 250px;
      }
      
      body.sidebar-open .sidebar-toggle {
        left: 250px;
      }
    }

    @media (max-width: 768px) {
      .profile-header {
        flex-direction: column;
        text-align: center;
      }
      
      .profile-picture {
        margin-right: 0;
        margin-bottom: 20px;
      }
      
      .profile-stats {
        justify-content: center;
      }
      
      .sidebar {
        padding-top: 100px;
      }
      
      .sidebar-toggle {
        top: 100px;
      }
      
      .address-actions {
        flex-direction: column;
      }
      
      .address-actions .btn {
        width: 100%;
      }
    }

    @media (max-width: 576px) {
      .button-group {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        text-align: center;
      }
    }

    /* Notification styles */
    .notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--success);
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      z-index: 1000;
      display: flex;
      align-items: center;
    }
    
    .notification.error {
      background: var(--danger);
    }
    
    .notification i {
      margin-right: 10px;
    }
  </style>
</head>
<body>

<?php if (isset($success_message)): ?>
<div class="notification" id="notification">
  <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
</div>
<script>
  setTimeout(() => document.getElementById('notification').remove(), 3000);
</script>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="notification error" id="error-notification">
  <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
</div>
<script>
  setTimeout(() => document.getElementById('error-notification').remove(), 3000);
</script>
<?php endif; ?>

<header class="navbar">
  <div class="logo">
    <img src="farmville-logo.png" alt="FarmVille Logo" class="logo-img" />
  </div>

  <div class="header-right">
    <div class="cart-icon">
      <a href="customer-order-history.php#cart" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
        <span class="cart-count" id="cartCount">0</span>
      </a>
    </div>

    <a href="customer-info.php" title="My Account">
      <div class="account-icon" style="background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
        <?php echo $initials; ?>
      </div>
    </a>
  </div>
</header>

<div class="sidebar" id="sidebar">
  <a href="customer-homepage.php"><i class="fas fa-home"></i> Home</a>
  <a href="customer-product-catalog.php"><i class="fas fa-shopping-basket"></i> Browse Products</a>
  <a href="customer-order-history.php"><i class="fas fa-history"></i> My Orders</a>
  <a href="customer-feedback.php"><i class="fas fa-comment-alt"></i> Feedbacks</a>
  <a href="Login_Page.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="sidebar-toggle" id="sidebarToggle">
  <i class="fas fa-bars"></i>
</div>

<div class="main-content">
  <section class="content-box">
    <h2><i class="fas fa-user-circle"></i> My Account</h2>
    
    <div class="profile-header">
      <div class="profile-picture" id="profileInitials"><?php echo $initials; ?></div>
      <div class="profile-info">
        <h3 id="userName"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h3>
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?></p>
        <p><i class="fas fa-map-marker-alt"></i> <?php 
          echo htmlspecialchars($customer['city'] ?? 'Location not set');
          if (!empty($customer['address_line1'])) {
            echo ', ' . htmlspecialchars($customer['address_line1']);
          }
        ?></p>
        
        <div class="profile-stats">
          <div class="stat-item">
            <div class="stat-value"><?php echo $order_count; ?></div>
            <div class="stat-label">Orders</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">5</div>
            <div class="stat-label">Reviews</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="button-group">
      <button type="button" class="btn btn-outline" id="editBtn">Edit Profile</button>
      <button type="submit" form="profileForm" class="btn" id="saveBtn" style="display: none;" name="update_profile">Save Changes</button>
      <button type="button" class="btn btn-outline" id="cancelBtn" style="display: none;">Cancel</button>
    </div>
    
    <!-- Personal Info Section -->
    <h3 class="section-header">Personal Information</h3>
    <form id="profileForm" method="POST">
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="first_name" class="form-control" value="<?php echo htmlspecialchars($customer['first_name']); ?>" readonly>
          </div>
        </div>
        <div class="form-col">
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="last_name" class="form-control" value="<?php echo htmlspecialchars($customer['last_name']); ?>" readonly>
          </div>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" readonly>
          </div>
        </div>
        <div class="form-col">
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" readonly>
          </div>
        </div>
      </div>
    </form>
    
    <!-- Address Section -->
    <h3 class="section-header">My Address</h3>
    <div class="address-card" id="addressCard">
      <h3>Home Address</h3>
      <p id="addressLine1"><?php echo htmlspecialchars($customer['address_line1'] ?? 'Address not set'); ?></p>
      <p id="addressLine2"><?php echo htmlspecialchars($customer['address_line2'] ?? ''); ?></p>
      <p id="addressCountry"><?php echo htmlspecialchars($customer['city'] ?? ''); ?></p>
      <div class="address-actions">
        <button class="btn" id="editAddressBtn">Edit Address</button>
      </div>
    </div>

    <!-- Address Edit Form (Initially Hidden) -->
    <div id="addressEditForm" style="display: none;">
      <h3 class="section-header">Edit Address</h3>
      <form id="addressForm" method="POST">
        <div class="form-group">
          <label for="editAddressLine1">House/Street</label>
          <input type="text" id="editAddressLine1" name="address_line1" class="form-control" value="<?php echo htmlspecialchars($customer['address_line1'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="editAddressLine2">Area/Post Office</label>
          <input type="text" id="editAddressLine2" name="address_line2" class="form-control" value="<?php echo htmlspecialchars($customer['address_line2'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label for="editAddressCountry">City</label>
          <input type="text" id="editAddressCountry" name="city" class="form-control" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
        </div>
        <div class="button-group">
          <button type="submit" class="btn" id="saveAddressBtn" name="update_address">Save Address</button>
          <button type="button" class="btn btn-outline" id="cancelAddressBtn">Cancel</button>
        </div>
      </form>
    </div>
    
    <!-- Password Section -->
    <h3 class="section-header">Change Password</h3>
    <form id="securityForm" method="POST">
      <div class="form-group">
        <label for="currentPassword">Current Password</label>
        <input type="password" id="currentPassword" name="current_password" class="form-control" required>
      </div>
      
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input type="password" id="newPassword" name="new_password" class="form-control" required>
          </div>
        </div>
        <div class="form-col">
          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required>
          </div>
        </div>
      </div>
      
      <div class="button-group">
        <button type="submit" class="btn" id="changePasswordBtn" name="change_password">Change Password</button>
      </div>
    </form>
  </section>
</div>

<footer>
  <div class="footer-content">
      <div class="footer-section">
          <p>&copy; 2025 FarmVille | Market Intelligence for Smarter Farming Decisions</p>
          <div class="social-icons">
              <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
              <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
              <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
          </div>
      </div>
      <div class="footer-section">
          <h4><i class="fas fa-headset"></i> Support</h4>
          <p>Email: <a href="mailto:support@farmville.com">support@farmville.com</a></p>
          <p>Phone: +880 1234-567890</p>
      </div>
      <div class="footer-section">
          <h4><i class="fas fa-link"></i> Quick Links</h4>
          <a href="privacy-policy.html">Privacy Policy</a>
          <a href="terms.html">Terms & Conditions</a>
      </div>
  </div>
</footer>

<script>
  // Sidebar functionality
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const body = document.body;
  let sidebarTimeout;

  // Open sidebar when hovering over toggle button
  sidebarToggle.addEventListener('mouseenter', () => {
    clearTimeout(sidebarTimeout);
    body.classList.add('sidebar-open');
  });

  // Keep sidebar open when mouse enters the sidebar
  sidebar.addEventListener('mouseenter', () => {
    clearTimeout(sidebarTimeout);
  });

  // Schedule closing sidebar when mouse leaves sidebar or toggle
  sidebar.addEventListener('mouseleave', () => {
    sidebarTimeout = setTimeout(() => {
      body.classList.remove('sidebar-open');
    }, 300);
  });

  sidebarToggle.addEventListener('mouseleave', (e) => {
    if (e.relatedTarget !== sidebar) {
      sidebarTimeout = setTimeout(() => {
        body.classList.remove('sidebar-open');
      }, 300);
    }
  });

  // Click toggle for mobile devices
  sidebarToggle.addEventListener('click', () => {
    body.classList.toggle('sidebar-open');
  });

  // Edit profile functionality
  const editBtn = document.getElementById('editBtn');
  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const formControls = document.querySelectorAll('#profileForm .form-control');

  if (editBtn && saveBtn && cancelBtn) {
    editBtn.addEventListener('click', function() {
      formControls.forEach(control => {
        if (control.id !== 'email') { // Don't allow editing email
          control.removeAttribute('readonly');
        }
      });
      editBtn.style.display = 'none';
      saveBtn.style.display = 'inline-block';
      cancelBtn.style.display = 'inline-block';
    });

    cancelBtn.addEventListener('click', function() {
      formControls.forEach(control => {
        control.setAttribute('readonly', true);
      });
      // Reset values to original
      document.getElementById('firstName').value = '<?php echo htmlspecialchars($customer['first_name']); ?>';
      document.getElementById('lastName').value = '<?php echo htmlspecialchars($customer['last_name']); ?>';
      document.getElementById('phone').value = '<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>';
      editBtn.style.display = 'inline-block';
      saveBtn.style.display = 'none';
      cancelBtn.style.display = 'none';
    });
  }

  // Address edit functionality
  const editAddressBtn = document.getElementById('editAddressBtn');
  const addressCard = document.getElementById('addressCard');
  const addressEditForm = document.getElementById('addressEditForm');
  const saveAddressBtn = document.getElementById('saveAddressBtn');
  const cancelAddressBtn = document.getElementById('cancelAddressBtn');

  if (editAddressBtn) {
    editAddressBtn.addEventListener('click', function() {
      addressCard.style.display = 'none';
      addressEditForm.style.display = 'block';
    });
  }

  if (cancelAddressBtn) {
    cancelAddressBtn.addEventListener('click', function() {
      addressCard.style.display = 'block';
      addressEditForm.style.display = 'none';
    });
  }

  // Update cart count on page load
  updateCartCount();

  // Update cart count
  function updateCartCount() {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
      const cart = JSON.parse(localStorage.getItem('farmvilleCart')) || [];
      const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
      cartCountElement.innerText = totalItems;
    }
  }
</script>
</body>
</html>
<?php
$conn->close();
?>