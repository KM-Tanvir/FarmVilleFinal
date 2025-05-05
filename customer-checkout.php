<?php
require_once 'config.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['customer_id'])) {
    header("Location: Login_Page.php");
    exit();
}

// Initialize database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input data
    $firstName = $conn->real_escape_string($_POST['firstName'] ?? '');
    $lastName = $conn->real_escape_string($_POST['lastName'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $houseNo = $conn->real_escape_string($_POST['houseNo'] ?? '');
    $street = $conn->real_escape_string($_POST['street'] ?? '');
    $postOffice = $conn->real_escape_string($_POST['postOffice'] ?? '');
    $zipCode = $conn->real_escape_string($_POST['zipCode'] ?? '');
    $district = $conn->real_escape_string($_POST['district'] ?? '');
    $division = $conn->real_escape_string($_POST['division'] ?? '');
    $paymentMethod = $conn->real_escape_string($_POST['paymentMethod'] ?? '');
    $deliveryMethod = $conn->real_escape_string($_POST['deliveryMethod'] ?? '');
    $deliveryNotes = $conn->real_escape_string($_POST['deliveryNotes'] ?? '');
    $orderNotes = $conn->real_escape_string($_POST['orderNotes'] ?? '');
    $farmUpdates = isset($_POST['farmUpdates']) ? 1 : 0;
    
    // Calculate delivery fee based on method
    $deliveryFee = 80; // Default standard delivery
    switch ($deliveryMethod) {
        case 'express':
            $deliveryFee = 150;
            break;
        case 'pickup':
            $deliveryFee = 0;
            break;
    }
    
    // Get cart items from session
    $cart = $_SESSION['cart'] ?? [];
    
    if (empty($cart)) {
        die("Your cart is empty. Please add items before checking out.");
    }
    
    // Calculate subtotal
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += (float)$item['price'] * (float)$item['quantity'];
    }
    
    $total = $subtotal + $deliveryFee;
    
    // Create delivery address string
    $deliveryAddress = "$houseNo, $street, $postOffice, $district, $division - $zipCode";
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert order into database
        $stmt = $conn->prepare("INSERT INTO order_t (CustomerID, total_amount, delivery_fee, payment_method, delivery_address, order_status, order_date) 
                               VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("iddss", $_SESSION['customer_id'], $total, $deliveryFee, $paymentMethod, $deliveryAddress);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();
        
        // Insert order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty_kg, price_per_kg) 
                               VALUES (?, ?, ?, ?)");
        
        foreach ($cart as $item) {
            // Get product ID from product name
            $productId = 0;
            $productQuery = $conn->prepare("SELECT product_id FROM product_t WHERE product_name = ? LIMIT 1");
            $productQuery->bind_param("s", $item['crop']);
            $productQuery->execute();
            $productResult = $productQuery->get_result();
            if ($productResult->num_rows > 0) {
                $productRow = $productResult->fetch_assoc();
                $productId = $productRow['product_id'];
            }
            $productQuery->close();
            
            $stmt->bind_param("iidd", $orderId, $productId, $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart from both session and localStorage
        $_SESSION['cart'] = [];
        
        // Redirect to order confirmation with success message
        header("Location: customer-order-history.php?order_id=$orderId&order_success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        die("Error processing your order: " . $e->getMessage());
    }
}

// Get cart items for display
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float)$item['price'] * (float)$item['quantity'];
}
$deliveryFee = 80; // Default standard delivery
$total = $subtotal + $deliveryFee;

// Get customer details if available
$customerDetails = [];
if (isset($_SESSION['customer_id'])) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone, address_line1, address_line2, city 
                           FROM customer_t WHERE CustomerID = ?");
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $customerDetails = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout — FarmVille</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
/* ===== GLOBAL STYLES ===== */
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

/* ===== HEADER & NAVIGATION ===== */
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
  width: 28px;
  height: 28px;
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

/* ===== SIDEBAR STYLES ===== */
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
  padding: 20px 5%;
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

/* ===== CONTENT STYLES ===== */
.hero-section {
  text-align: center;
  padding: 80px 20px;
  background: linear-gradient(135deg, rgba(44,141,173,0.9) 0%, rgba(31,64,142,0.9) 100%), url('bg-farm.jpg') no-repeat center center/cover;
  color: white;
  font-size: 1.3rem;
  margin: 30px auto;
  width: 100%;
  max-width: 1200px;
  border-radius: 15px;
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.hero-section::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 L0,100 Z" fill="none" stroke="white" stroke-width="0.5" stroke-dasharray="5,5" opacity="0.3"/></svg>');
  background-size: 20px 20px;
  pointer-events: none;
}

.hero-section h1 {
  font-size: 2.5rem;
  margin-bottom: 20px;
  position: relative;
  display: inline-block;
}

.hero-section h1::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background: white;
  border-radius: 3px;
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

/* ===== FORM STYLES ===== */
.form-section {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.form-section h3 {
  color: var(--dark);
  margin-bottom: 20px;
  font-size: 1.2rem;
  position: relative;
  display: inline-block;
}

.form-section h3::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 0;
  width: 40px;
  height: 2px;
  background: linear-gradient(to right, var(--primary), var(--secondary));
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--dark);
}

.form-control, .form-select {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
  background-color: rgba(44, 141, 173, 0.05);
}

.form-control:focus, .form-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(44, 141, 173, 0.2);
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.form-row .form-group {
  flex: 1;
  min-width: 200px;
}

.required-field::after {
  content: '*';
  color: var(--danger);
  margin-left: 4px;
}

.input-validation-error {
  border-color: var(--danger) !important;
}

/* ===== CHECKOUT SUMMARY STYLES ===== */
.order-summary {
  background-color: rgba(44, 141, 173, 0.05);
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  padding: 8px 0;
}

.total-row {
  border-top: 2px solid rgba(0, 0, 0, 0.1);
  padding-top: 10px;
  font-weight: 600;
  font-size: 1.1rem;
}

/* ===== CART TABLE STYLES ===== */
.cart-items-summary {
  margin-bottom: 30px;
  overflow-x: auto;
}

.cart-items-summary table {
  width: 100%;
  border-collapse: collapse;
}

.cart-items-summary th {
  background: linear-gradient(to right, var(--secondary), var(--primary));
  color: white;
  text-align: left;
  padding: 15px;
  font-weight: 500;
}

.cart-items-summary td {
  padding: 15px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.cart-items-summary tr:nth-child(even) {
  background-color: rgba(44, 141, 173, 0.03);
}

.cart-items-summary tr:hover {
  background-color: rgba(44, 141, 173, 0.08);
}

/* ===== PAYMENT METHOD STYLES ===== */
.payment-options {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
}

.payment-option {
  flex: 1 1 200px;
  padding: 15px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.payment-option:hover, .payment-option.selected {
  border-color: var(--primary);
  background-color: rgba(44, 141, 173, 0.05);
}

.payment-option.selected {
  box-shadow: 0 0 0 2px var(--primary);
}

.payment-option input[type="radio"] {
  margin-right: 10px;
}

.payment-details {
  margin-top: 15px;
  padding: 15px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  display: none;
  background-color: rgba(44, 141, 173, 0.03);
}

/* ===== BUTTON STYLES ===== */
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* ===== CHECKBOX STYLES ===== */
.checkbox-group {
  margin-bottom: 20px;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  cursor: pointer;
  margin-bottom: 10px;
}

.checkbox-group input[type="checkbox"] {
  margin-right: 10px;
  width: 18px;
  height: 18px;
  accent-color: var(--primary);
}

/* ===== ALERT STYLES ===== */
.alert {
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid var(--info);
  background-color: rgba(23, 162, 184, 0.1);
  color: var(--dark);
}

/* ===== FOOTER STYLES ===== */
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

/* ===== RESPONSIVE STYLES ===== */
@media (max-width: 992px) {
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
  .navbar {
    padding: 15px;
  }
  
  .logo-img {
    height: 60px;
  }
  
  .header-right {
    gap: 15px;
  }
  
  .sidebar {
    padding-top: 100px;
  }
  
  .sidebar-toggle {
    top: 100px;
  }
  
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .payment-options {
    flex-direction: column;
  }
  
  .cart-items-summary table, 
  .cart-items-summary td {
    display: block;
  }
  
  .cart-items-summary td {
    text-align: right;
    padding-left: 50%;
    position: relative;
  }
  
  .cart-items-summary td::before {
    content: attr(data-label);
    position: absolute;
    left: 15px;
    width: 45%;
    padding-right: 10px;
    text-align: left;
    font-weight: bold;
  }
  
  .cart-items-summary th {
    display: none;
  }
}

@media (max-width: 576px) {
  .btn {
    padding: 10px 20px;
    font-size: 15px;
  }
}
  </style>
</head>
<body>

<header class="navbar">
  <div class="logo">
    <img src="farmville-logo.png" alt="FarmVille Logo" class="logo-img" />
  </div>
  
  <div class="header-right">
    <div class="cart-icon">
      <a href="customer-order-history.php" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
        <span class="cart-count" id="cartCount"><?php echo array_reduce($cart, function($carry, $item) { return $carry + $item['quantity']; }, 0); ?></span>
      </a>
    </div>
    
    <a href="customer-info.php" title="My Account">
      <img src="account-icon.png" alt="My Account" class="account-icon">
    </a>
  </div>
</header>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
  <a href="customer-homepage.php"><i class="fas fa-home"></i> Home</a>
  <a href="customer-product-catalog.php"><i class="fas fa-shopping-basket"></i> Browse Products</a>
  <a href="customer-order-history.php"><i class="fas fa-history"></i> My Orders</a>
  <a href="customer-feedback.html"><i class="fas fa-comment-alt"></i> Feedbacks</a>
  <a href="Login_Page.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Sidebar Toggle Button -->
<div class="sidebar-toggle" id="sidebarToggle">
  <i class="fas fa-bars"></i>
</div>

<!-- Main Content Wrapper -->
<div class="main-content">
  <section class="hero-section">
    <div class="hero-content">
      <h1>Complete Your Order</h1>
      <p>Review your items and enter your details to complete your purchase</p>
    </div>
  </section>

  <section class="content-box">
    <form id="orderForm" method="POST">
      <!-- Order Summary -->
      <div class="form-section">
        <h3><i class="fas fa-shopping-cart"></i> Order Summary</h3>
        
        <!-- Empty cart message (initially hidden) -->
        <div id="emptyCartMessage" style="display: <?php echo empty($cart) ? 'block' : 'none'; ?>;">
          <p>Your cart is empty. <a href="customer-product-catalog.php">Browse products</a> to add items to your cart.</p>
        </div>
        
        <!-- Cart items table (will be populated from cart data) -->
        <div class="cart-items-summary" id="cartItemsSummary" style="display: <?php echo empty($cart) ? 'none' : 'block'; ?>;">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody id="cartItemsList">
              <?php foreach ($cart as $item): ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['crop']); ?></td>
                  <td><?php echo htmlspecialchars($item['price']); ?> ৳</td>
                  <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                  <td><?php echo (float)$item['price'] * (float)$item['quantity']; ?> ৳</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Order summary totals -->
        <div class="order-summary" id="orderSummarySection" style="display: <?php echo empty($cart) ? 'none' : 'block'; ?>;">
          <div class="summary-row">
            <span>Subtotal</span>
            <span id="subtotal"><?php echo $subtotal; ?> ৳</span>
          </div>
          <div class="summary-row">
            <span>Shipping</span>
            <span id="deliveryFee"><?php echo $deliveryFee; ?> ৳</span>
          </div>
          <div class="summary-row total-row">
            <span>Total</span>
            <span id="total"><?php echo $total; ?> ৳</span>
          </div>
        </div>
      </div>

      <!-- Personal Information -->
      <div class="form-section">
        <h3><i class="fas fa-user"></i> Personal Information</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="firstName" class="form-label required-field">First Name</label>
            <input type="text" id="firstName" name="firstName" class="form-control" required 
                   value="<?php echo isset($customerDetails['first_name']) ? htmlspecialchars($customerDetails['first_name']) : ''; ?>">
          </div>
          <div class="form-group">
            <label for="lastName" class="form-label required-field">Last Name</label>
            <input type="text" id="lastName" name="lastName" class="form-control" required
                   value="<?php echo isset($customerDetails['last_name']) ? htmlspecialchars($customerDetails['last_name']) : ''; ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="email" class="form-label required-field">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?php echo isset($customerDetails['email']) ? htmlspecialchars($customerDetails['email']) : ''; ?>">
          </div>
          <div class="form-group">
            <label for="phone" class="form-label required-field">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control" required
                   value="<?php echo isset($customerDetails['phone']) ? htmlspecialchars($customerDetails['phone']) : ''; ?>">
          </div>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="form-section">
        <h3><i class="fas fa-truck"></i> Shipping Address</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="houseNo" class="form-label required-field">House Number</label>
            <input type="text" id="houseNo" name="houseNo" class="form-control" required
                   value="<?php echo isset($customerDetails['address_line1']) ? htmlspecialchars($customerDetails['address_line1']) : ''; ?>">
          </div>
          <div class="form-group">
            <label for="street" class="form-label">Street</label>
            <input type="text" id="street" name="street" class="form-control"
                   value="<?php echo isset($customerDetails['address_line2']) ? htmlspecialchars($customerDetails['address_line2']) : ''; ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="postOffice" class="form-label">Post Office</label>
          <input type="text" id="postOffice" name="postOffice" class="form-control">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="zipCode" class="form-label required-field">ZIP/Postal Code</label>
            <input type="text" id="zipCode" name="zipCode" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="district" class="form-label required-field">District</label>
            <input type="text" id="district" name="district" class="form-control" required
                   value="<?php echo isset($customerDetails['city']) ? htmlspecialchars($customerDetails['city']) : ''; ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="division" class="form-label required-field">Division</label>
          <select id="division" name="division" class="form-select" required>
            <option value="">Select Division</option>
            <option value="dhaka">Dhaka</option>
            <option value="chattogram">Chattogram</option>
            <option value="khulna">Khulna</option>
            <option value="barishal">Barishal</option>
            <option value="rajshahi">Rajshahi</option>
            <option value="rangpur">Rangpur</option>
            <option value="mymensingh">Mymensingh</option>
            <option value="sylhet">Sylhet</option>
          </select>
        </div>
      </div>

      <!-- Payment Information -->
      <div class="form-section">
        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
        <div class="payment-options">
          <div class="payment-option selected" onclick="document.getElementById('creditCard').click()">
            <input type="radio" id="creditCard" name="paymentMethod" value="Credit Card" checked onclick="showPaymentDetails('creditCard')">
            <label for="creditCard">Credit Card</label>
          </div>
          <div class="payment-option" onclick="document.getElementById('mobileMoney').click()">
            <input type="radio" id="mobileMoney" name="paymentMethod" value="Mobile Money" onclick="showPaymentDetails('mobileMoney')">
            <label for="mobileMoney">Mobile Money</label>
          </div>
          <div class="payment-option" onclick="document.getElementById('cashOnDelivery').click()">
            <input type="radio" id="cashOnDelivery" name="paymentMethod" value="Cash on Delivery" onclick="showPaymentDetails('cashOnDelivery')">
            <label for="cashOnDelivery">Cash on Delivery</label>
          </div>
        </div>
        
        <!-- Credit Card Details (shown by default) -->
        <div id="creditCardDetails" class="payment-details" style="display: block;">
          <div class="form-row">
            <div class="form-group">
              <label for="card-type">Select Card Type:</label>
              <select id="card-type" name="card-type" class="form-select">
                <option value="">-- Choose your card --</option>
                <option value="visa">Visa</option>
                <option value="mastercard">MasterCard</option>
                <option value="amex">American Express</option>
                <option value="discover">Discover</option>
              </select>                            
            </div>
            <div class="form-group">
              <label for="cardName" class="form-label required-field">Name on Card</label>
              <input type="text" id="cardName" name="cardName" class="form-control">
            </div>
            <div class="form-group">
              <label for="cardNumber" class="form-label required-field">Card Number</label>
              <input type="text" id="cardNumber" name="cardNumber" class="form-control" placeholder="XXXX XXXX XXXX XXXX">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="expiryDate" class="form-label required-field">Expiry Date</label>
              <input type="text" id="expiryDate" name="expiryDate" class="form-control" placeholder="MM/YY">
            </div>
            <div class="form-group">
              <label for="cvv" class="form-label required-field">CVV</label>
              <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123">
            </div>
          </div>
        </div>
        
        <!-- Mobile Money Details (initially hidden) -->
        <div id="mobileMoneyDetails" class="payment-details">
          <div class="form-row">
            <div class="form-group">
              <label for="mobileProvider" class="form-label required-field">Mobile Payment Provider</label>
              <select id="mobileProvider" name="mobileProvider" class="form-select">
                <option value="">Select Provider</option>
                <option value="bkash">bKash</option>
                <option value="nagad">Nagad</option>
                <option value="rocket">Rocket</option>
              </select>
            </div>
            <div class="form-group">
              <label for="mobileNumber" class="form-label required-field">Mobile Number</label>
              <input type="tel" id="mobileNumber" name="mobileNumber" class="form-control" placeholder="01XXXXXXXXX">
            </div>
          </div>
        </div>
        
        <!-- Cash on Delivery Details (initially hidden) -->
        <div id="cashOnDeliveryDetails" class="payment-details">
          <div class="alert">
            <strong>Cash on Delivery:</strong> Pay in cash when your order arrives. Please have the exact amount ready.
          </div>
        </div>
      </div>
    
      <!-- Delivery Options -->
      <div class="form-section">
        <h3><i class="fas fa-shipping-fast"></i> Delivery Options</h3>
        <div class="form-group">
          <label for="deliveryMethod" class="form-label required-field">Choose Delivery Method</label>
          <select id="deliveryMethod" name="deliveryMethod" class="form-select" required>
            <option value="">Select an option</option>
            <option value="standard" selected>Standard Delivery (2-3 business days) - 80 ৳</option>
            <option value="express">Express Delivery (1-2 business days) - 150 ৳</option>
            <option value="pickup">Local Pickup (Free)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="deliveryNotes" class="form-label">Delivery Instructions (Optional)</label>
          <textarea id="deliveryNotes" name="deliveryNotes" class="form-control" rows="3" placeholder="Special instructions for delivery..."></textarea>
        </div>
      </div>

      <!-- Additional Information -->
      <div class="form-section">
        <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
        <div class="checkbox-group">
          <label>
            <input type="checkbox" id="farmUpdates" name="farmUpdates" value="1">
            Receive updates about our local farm events and activities
          </label>
          <label>
            <input type="checkbox" id="termsAndConditions" name="termsAndConditions" required>
            I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>
          </label>
        </div>
        <div class="form-group">
          <label for="orderNotes" class="form-label">Order Notes (Optional)</label>
          <textarea id="orderNotes" name="orderNotes" class="form-control" rows="3" placeholder="Any additional notes about your order..."></textarea>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="text-center">
        <button type="submit" class="btn" id="submitOrder" <?php echo empty($cart) ? 'disabled' : ''; ?>>Place Order</button>
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
        <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>
    <div class="footer-section">
      <h4><i class="fas fa-headset"></i> Support</h4>
      <p>Email: <a href="mailto:support@farmville.com">support@farmville.com</a></p>
      <p>Phone: +880 1234-567890</p>
      <p>Hours: Mon–Fri, 9AM–6PM</p>
    </div>
    <div class="footer-section">
      <h4><i class="fas fa-link"></i> Quick Links</h4>
      <a href="#">Privacy Policy</a>
      <a href="#">Terms & Conditions</a>
      <a href="#">Help Center</a>
    </div>
    <div class="footer-section">
      <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
      <p>FarmVille Headquarters</p>
      <p>123 Agriculture Road</p>
      <p>Farmers District, BD 1207</p>
    </div>
  </div>
</footer>

<script>
  // Sidebar functionality
  document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const body = document.body;
    let sidebarTimeout;
    
    // Open sidebar when hovering over toggle button
    if (sidebarToggle) {
      sidebarToggle.addEventListener('mouseenter', () => {
        clearTimeout(sidebarTimeout);
        body.classList.add('sidebar-open');
      });
    }
    
    // Keep sidebar open when mouse enters the sidebar
    if (sidebar) {
      sidebar.addEventListener('mouseenter', () => {
        clearTimeout(sidebarTimeout);
      });
    }
    
    // Schedule closing sidebar when mouse leaves sidebar or toggle
    if (sidebar) {
      sidebar.addEventListener('mouseleave', () => {
        sidebarTimeout = setTimeout(() => {
          body.classList.remove('sidebar-open');
        }, 300);
      });
    }
    
    if (sidebarToggle) {
      sidebarToggle.addEventListener('mouseleave', (e) => {
        // Only close if we're not moving into the sidebar
        if (e.relatedTarget !== sidebar) {
          sidebarTimeout = setTimeout(() => {
            body.classList.remove('sidebar-open');
          }, 300);
        }
      });
    }
    
    // Click toggle for mobile devices
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', () => {
        body.classList.toggle('sidebar-open');
      });
    }

    // Payment method selection
    function showPaymentDetails(paymentMethod) {
      // Hide all payment details sections first
      const paymentDetails = document.querySelectorAll('.payment-details');
      paymentDetails.forEach(detail => {
        detail.style.display = 'none';
      });
      
      // Update selected state for payment options
      const paymentOptions = document.querySelectorAll('.payment-option');
      paymentOptions.forEach(option => {
        option.classList.remove('selected');
      });
      
      // Show only the selected payment method details
      const selectedDetails = document.getElementById(paymentMethod + 'Details');
      if (selectedDetails) {
        selectedDetails.style.display = 'block';
      }
      
      const selectedOption = document.querySelector('#' + paymentMethod).parentElement;
      if (selectedOption) {
        selectedOption.classList.add('selected');
      }
    }

    // Initialize payment method display
    const defaultPayment = document.querySelector('input[name="paymentMethod"]:checked');
    if (defaultPayment) {
      showPaymentDetails(defaultPayment.id);
    }

    // Update order total when delivery method changes
    const deliveryMethodSelect = document.getElementById('deliveryMethod');
    if (deliveryMethodSelect) {
      deliveryMethodSelect.addEventListener('change', function() {
        const deliveryMethod = this.value;
        let deliveryFee = 80; // Default standard delivery
        
        switch (deliveryMethod) {
          case 'express':
            deliveryFee = 150;
            break;
          case 'pickup':
            deliveryFee = 0;
            break;
        }
        
        // Update delivery fee display
        const deliveryFeeElement = document.getElementById('deliveryFee');
        if (deliveryFeeElement) {
          deliveryFeeElement.textContent = deliveryFee.toFixed(2) + ' ৳';
        }
        
        // Calculate and update total
        const subtotalElement = document.getElementById('subtotal');
        const totalElement = document.getElementById('total');
        
        if (subtotalElement && totalElement) {
          const subtotalText = subtotalElement.textContent;
          const subtotal = parseFloat(subtotalText.replace(/[^\d.]/g, ''));
          const total = subtotal + deliveryFee;
          totalElement.textContent = total.toFixed(2) + ' ৳';
        }
      });
    }

    // Form validation
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
      orderForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate required fields
        const requiredFields = this.querySelectorAll('[required]');
        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            field.classList.add('input-validation-error');
            isValid = false;
          } else {
            field.classList.remove('input-validation-error');
          }
        });
        
        // Validate payment method specific fields
        const selectedPayment = document.querySelector('input[name="paymentMethod"]:checked');
        if (selectedPayment) {
          if (selectedPayment.value === 'Credit Card') {
            const cardFields = ['cardName', 'cardNumber', 'expiryDate', 'cvv'];
            cardFields.forEach(fieldId => {
              const field = document.getElementById(fieldId);
              if (field && !field.value.trim()) {
                field.classList.add('input-validation-error');
                isValid = false;
              } else if (field) {
                field.classList.remove('input-validation-error');
              }
            });
          } else if (selectedPayment.value === 'Mobile Money') {
            const mobileFields = ['mobileProvider', 'mobileNumber'];
            mobileFields.forEach(fieldId => {
              const field = document.getElementById(fieldId);
              if (field && !field.value.trim()) {
                field.classList.add('input-validation-error');
                isValid = false;
              } else if (field) {
                field.classList.remove('input-validation-error');
              }
            });
          }
        }
        
        // Validate terms checkbox
        const termsCheckbox = document.getElementById('termsAndConditions');
        if (termsCheckbox && !termsCheckbox.checked) {
          termsCheckbox.classList.add('input-validation-error');
          isValid = false;
        } else if (termsCheckbox) {
          termsCheckbox.classList.remove('input-validation-error');
        }
        
        if (!isValid) {
          e.preventDefault();
          alert('Please fill in all required fields correctly.');
        }
      });
    }

    // Payment method radio button click handlers
    const paymentRadios = document.querySelectorAll('input[name="paymentMethod"]');
    paymentRadios.forEach(radio => {
      radio.addEventListener('click', function() {
        showPaymentDetails(this.id);
      });
    });
  });
</script>
</body>
</html>