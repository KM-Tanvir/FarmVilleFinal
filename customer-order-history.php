<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Synchronize localStorage cart with session cart
if (!empty($_POST['cart_data'])) {
    $cartData = json_decode($_POST['cart_data'], true);
    if (is_array($cartData)) {
        $_SESSION['cart'] = $cartData;
    }
}

// Get customer ID from session (simulated for demo)
$customerId = $_SESSION['customer_id'] ?? 1; // Default customer for demo

// Get customer orders with proper price data
$sql = "SELECT o.order_id, o.order_date, o.total_amount, o.delivery_fee, o.payment_method, 
               o.delivery_address, o.order_status
        FROM order_t o
        WHERE o.CustomerID = ?
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update_quantity':
            $index = (int)$_POST['index'];
            $change = isset($_POST['change']) ? (float)$_POST['change'] : 0;
            $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
            
            if (isset($_SESSION['cart'][$index])) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$index]['quantity'] = $quantity;
                } else {
                    $_SESSION['cart'][$index]['quantity'] += $change;
                    if ($_SESSION['cart'][$index]['quantity'] < 1) {
                        $_SESSION['cart'][$index]['quantity'] = 1;
                    }
                }
            }
            break;
            
        case 'remove_item':
            $index = (int)$_POST['index'];
            if (isset($_SESSION['cart'][$index])) {
                array_splice($_SESSION['cart'], $index, 1);
            }
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            break;
            
        case 'sync_cart':
            if (!empty($_POST['cart_data'])) {
                $cartData = json_decode($_POST['cart_data'], true);
                if (is_array($cartData)) {
                    $_SESSION['cart'] = $cartData;
                }
            }
            break;
    }
    
    // Return JSON response for AJAX calls
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
    exit();
}

// Calculate cart totals with proper type checking
$cartCount = array_reduce($_SESSION['cart'], function($carry, $item) {
    return $carry + (isset($item['quantity']) ? (float)$item['quantity'] : 0);
}, 0);

$subtotal = array_reduce($_SESSION['cart'], function($carry, $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0;
    return $carry + ($price * $quantity);
}, 0);

$deliveryFee = 80; // Default delivery fee
$total = $subtotal + $deliveryFee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FarmVille — Order History</title>
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

    /* ===== ORDER HISTORY STYLES ===== */
    .vendor-card {
      padding: 25px;
      border-radius: 12px;
      background-color: white;
      margin-bottom: 30px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      border-left: 4px solid var(--secondary);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .vendor-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .vendor-card::after {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(44,141,173,0.03) 0%, rgba(31,64,142,0.03) 100%);
      z-index: 0;
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
      z-index: 1;
    }

    .order-header h3 {
      color: var(--dark);
      margin-right: 15px;
      font-size: 1.2rem;
    }

    .order-header p {
      color: #666;
    }

    .certified {
      display: inline-block;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 600;
      margin-top: 10px;
      font-size: 0.9rem;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }

    .status-delivered {
      background: linear-gradient(to right, var(--success), #5cb85c);
      color: white;
    }

    .status-pending {
      background: linear-gradient(to right, var(--warning), #f0ad4e);
      color: white;
    }

    .status-cancelled {
      background: linear-gradient(to right, var(--danger), #d9534f);
      color: white;
    }

    /* ===== PRODUCT GRID STYLES ===== */
    .product-grid {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin: 20px 0;
    }

    .product-card {
      background-color: #fcfcff;
      border: 1px solid rgba(0, 0, 0, 0.03);
      padding: 20px;
      border-radius: 10px;
      flex: 1 1 300px;
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
    }

    .product-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .product-info {
      display: flex;
      justify-content: space-between;
    }

    .product-details {
      width: 100%;
    }

    .product-details h3 {
      margin-bottom: 15px;
      color: var(--dark);
      font-size: 1.1rem;
      position: relative;
    }

    .product-details h3::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 30px;
      height: 2px;
      background: var(--primary);
    }

    .product-details p {
      margin: 8px 0;
      color: #555;
    }

    .order-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin: 15px 0;
      padding: 15px 0;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .order-meta p {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #555;
    }

    .order-meta i {
      color: var(--primary);
      width: 20px;
      text-align: center;
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

    .button-container {
      display: flex;
      justify-content: flex-start;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 25px;
    }

    /* ===== CART TABLE STYLES ===== */
    .cart-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin: 25px 0;
      text-align: left;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .cart-table th,
    .cart-table td {
      padding: 15px;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .cart-table th {
      background: linear-gradient(to right, var(--secondary), var(--primary));
      color: white;
      font-weight: 500;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }

    .cart-table tr:nth-child(even) {
      background-color: rgba(44, 141, 173, 0.03);
    }

    .cart-table tr:hover {
      background-color: rgba(44, 141, 173, 0.08);
    }

    .cart-summary {
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      margin-top: 20px;
      width: 300px;
      margin-left: auto;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      border-left: 4px solid var(--primary);
    }

    .cart-summary p {
      margin: 10px 0;
      display: flex;
      justify-content: space-between;
    }

    .cart-summary p strong {
      color: var(--dark);
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      justify-content: flex-start;
    }

    .quantity-controls input {
      width: 60px;
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 8px;
      margin: 0 5px;
      font-size: 14px;
    }

    .quantity-btn {
      background-color: rgba(0, 0, 0, 0.05);
      border: none;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .quantity-btn:hover {
      background-color: rgba(0, 0, 0, 0.1);
    }

    .remove-btn {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--danger);
      border: none;
      border-radius: 6px;
      cursor: pointer;
      padding: 8px 15px;
      transition: all 0.3s ease;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .remove-btn:hover {
      background-color: rgba(220, 53, 69, 0.2);
      transform: translateY(-2px);
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

    /* ===== UTILITY CLASSES ===== */
    .text-center {
      text-align: center;
    }

    .mt-4 {
      margin-top: 20px;
    }

    .mt-5 {
      margin-top: 25px;
    }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    section, button, a.btn, form {
      animation: fadeIn 0.8s ease-out forwards;
    }

    /* ===== ORDER HISTORY SPECIFIC STYLES ===== */
    #orderHistoryContainer {
      animation: fadeIn 0.8s ease-out forwards;
    }

    .loading-message {
      text-align: center;
      padding: 40px;
      color: var(--dark);
      font-size: 1.1rem;
    }

    .loading-message i {
      font-size: 2rem;
      margin-bottom: 15px;
      color: var(--primary);
      display: block;
    }

    .error-message {
      text-align: center;
      padding: 40px;
      color: var(--danger);
      font-size: 1.1rem;
    }

    .error-message i {
      font-size: 2rem;
      margin-bottom: 15px;
      display: block;
    }

    .vendor-card {
      animation: slideIn 0.5s ease-out forwards;
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
      
      .content-box {
        padding: 20px;
      }

      .product-grid {
        flex-direction: column;
      }

      .product-card {
        flex: 1 1 auto;
      }

      .cart-summary {
        width: 100%;
      }

      .button-container {
        flex-direction: column;
      }

      .btn {
        width: 100%;
        text-align: center;
      }
    }

    @media (max-width: 576px) {
      .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      .order-meta {
        flex-direction: column;
        gap: 10px;
      }

      .cart-table,
      .cart-table td {
        display: block;
      }
      
      .cart-table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
      }
      
      .cart-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 45%;
        padding-right: 10px;
        text-align: left;
        font-weight: bold;
      }
      
      .cart-table th {
        display: none;
      }

/* Sidebar base styles */
.sidebar {
  width: 0;
  transform: translateX(-100%);
  transition: transform 0.3s ease, width 0.3s ease;
}

body.sidebar-open .sidebar {
  width: 280px;
  transform: translateX(0);
}

/* Sidebar toggle button positioning */
.sidebar-toggle {
  transition: left 0.3s ease;
}

body.sidebar-open .sidebar-toggle {
  left: 280px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .sidebar {
    width: 280px !important;
  }
  
  body.sidebar-open .main-content {
    margin-left: 0;
    transform: translateX(280px);
  }
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
      <a href="customer-order-history.php#cart" title="Shopping Cart">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
        <span class="cart-count" id="cartCount"><?php echo $cartCount; ?></span>
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
  <a href="customer-order-history.php" class="active"><i class="fas fa-history"></i> My Orders</a>
  <a href="customer-feedback.php"><i class="fas fa-comment-alt"></i> Feedbacks</a>
  <a href="Login_Page.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Sidebar Toggle Button -->
<div class="sidebar-toggle" id="sidebarToggle">
  <i class="fas fa-bars"></i>
</div>

<!-- Main Content Wrapper -->
<div class="main-content">
<!-- Cart Section -->
<section id="cart" class="content-box">
    <h2><i class="fas fa-shopping-cart"></i> My Cart</h2>
    <div id="cartItems">
        <?php if (!empty($_SESSION['cart'])): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $index => $item): 
                        // Get the numeric price value (remove currency symbol if present)
                        $price = isset($item['price']) ? (float)preg_replace('/[^0-9.]/', '', $item['price']) : 0;
                        $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0;
                        $itemSubtotal = $price * $quantity;
                    ?>
                        <tr>
                            <td data-label="Product"><?php echo htmlspecialchars($item['crop']); ?></td>
                            <td data-label="Price"><?php echo number_format($price, 2); ?> ৳</td>
                            <td data-label="Quantity">
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $index; ?>, -1)">-</button>
                                    <input type="number" min="1" value="<?php echo $quantity; ?>" 
                                           onchange="updateQuantity(<?php echo $index; ?>, this.value)">
                                    <button class="quantity-btn" onclick="updateQuantity(<?php echo $index; ?>, 1)">+</button>
                                </div>
                            </td>
                            <td data-label="Subtotal"><?php echo number_format($itemSubtotal, 2); ?> ৳</td>
                            <td data-label="Action">
                                <button class="remove-btn" onclick="removeFromCart(<?php echo $index; ?>)">
                                    <i class="fas fa-trash-alt"></i> Remove
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div id="emptyCartMessage" class="text-center">
                <p>Your cart is empty. <a href="customer-product-catalog.php">Browse products</a> to add items to your cart.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($_SESSION['cart'])): ?>
        <div id="cartSummary" class="cart-summary">
            <p><strong>Total Items:</strong> <span id="totalItems"><?php echo $cartCount; ?></span></p>
            <p><strong>Subtotal:</strong> <span id="subtotal"><?php echo number_format($subtotal, 2); ?> ৳</span></p>
            <p><strong>Delivery Fee:</strong> <span id="deliveryFee"><?php echo number_format($deliveryFee, 2); ?> ৳</span></p>
            <p><strong>Total:</strong> <span id="total"><?php echo number_format($total, 2); ?> ৳</span></p>
        </div>
        
        <div class="button-container">
            <button id="clearCart" class="btn"><i class="fas fa-trash-alt"></i> Clear Cart</button>
            <button id="checkoutBtn" class="btn"><i class="fas fa-credit-card"></i> Proceed to Checkout</button>
        </div>
    <?php endif; ?>
</section>

  <section class="content-box">
    <h2><i class="fas fa-history"></i> Order History</h2>
    <div id="orderHistoryContainer">
      <?php if (empty($orders)): ?>
        <p>You have no orders yet. <a href="customer-product-catalog.php">Browse products</a> to place your first order.</p>
      <?php else: ?>
        <?php foreach ($orders as $order): ?>
          <div class="vendor-card">
            <div class="order-header">
              <h3>Order ID: #FV<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></h3>
              <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
              <span class="certified status-<?php echo strtolower($order['order_status']); ?>">
                <i class="fas fa-<?php 
                  echo $order['order_status'] == 'Delivered' ? 'check-circle' : 
                  ($order['order_status'] == 'Pending' ? 'clock' : 'times-circle'); 
                ?>"></i> 
                <?php echo $order['order_status']; ?>
              </span>
            </div>
            
            <div class="product-grid">
              <?php 
              // Get order items details with proper price data
              $itemsSql = "SELECT p.product_name, p.product_id, oi.qty_kg, oi.price_per_kg 
                           FROM order_items oi
                           JOIN product_t p ON oi.product_id = p.product_id
                           WHERE oi.order_id = ?";
              $itemsStmt = $conn->prepare($itemsSql);
              $itemsStmt->bind_param("i", $order['order_id']);
              $itemsStmt->execute();
              $itemsResult = $itemsStmt->get_result();
              $items = $itemsResult->fetch_all(MYSQLI_ASSOC);
              $itemsStmt->close();
              
              foreach ($items as $item): 
                  $itemSubtotal = $item['qty_kg'] * $item['price_per_kg'];
              ?>
                <div class="product-card">
                  <div class="product-info">
                    <div class="product-details">
                      <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                      <p><strong>Product ID:</strong> <?php echo $item['product_id']; ?></p>
                      <p><strong>Quantity:</strong> <?php echo number_format($item['qty_kg'], 2); ?> kg</p>
                      <p><strong>Price:</strong> <?php echo number_format($item['price_per_kg'], 2); ?> ৳/kg</p>
                      <p><strong>Subtotal:</strong> <?php echo number_format($itemSubtotal, 2); ?> ৳</p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <div class="order-meta">
              <p><i class="fas fa-money-bill-wave"></i> <strong>Total Amount:</strong> <?php echo number_format($order['total_amount'], 2); ?> ৳ (incl. delivery)</p>
              <p><i class="fas fa-map-marker-alt"></i> <strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
              <p><i class="fas fa-credit-card"></i> <strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
              <p><i class="fas fa-clock"></i> <strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
            </div>
            
            <div class="button-container">
              <a href="customer-product-catalog.php" class="btn"><i class="fas fa-redo"></i> Reorder</a>
              <a href="customer-feedback.php?order_id=<?php echo $order['order_id']; ?>" class="btn"><i class="fas fa-star"></i> Leave Review</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
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
  // Function to get cart from localStorage
  function getLocalStorageCart() {
    const cart = localStorage.getItem('farmvilleCart');
    return cart ? JSON.parse(cart) : [];
  }

  // Function to sync localStorage cart with server session
  function syncCartWithServer() {
    const localStorageCart = getLocalStorageCart();
    if (localStorageCart.length > 0) {
      const formData = new FormData();
      formData.append('action', 'sync_cart');
      formData.append('cart_data', JSON.stringify(localStorageCart));
      
      fetch('customer-order-history.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update cart count without reloading the page
          updateCartCount(data.cart);
        }
      });
    }
  }

  // Function to update cart count without page reload
  function updateCartCount(cart) {
    const count = cart.reduce((total, item) => total + (item.quantity || 0), 0);
    document.getElementById('cartCount').textContent = count;
  }

  // Cart functionality
  function updateQuantity(index, change) {
      const formData = new FormData();
      formData.append('action', 'update_quantity');
      formData.append('index', index);
      
      if (typeof change === 'number') {
          formData.append('change', change);
      } else {
          formData.append('quantity', parseFloat(change));
      }
      
      fetch('customer-order-history.php', {
          method: 'POST',
          body: formData
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Update localStorage as well
              const cart = getLocalStorageCart();
              if (cart[index]) {
                  if (typeof change === 'number') {
                      cart[index].quantity = parseFloat(cart[index].quantity) + change;
                      if (cart[index].quantity < 1) cart[index].quantity = 1;
                  } else {
                      cart[index].quantity = parseFloat(change);
                  }
                  localStorage.setItem('farmvilleCart', JSON.stringify(cart));
              }
              updateCartCount(data.cart);
              location.reload(); // Only reload if necessary for other updates
          }
      });
  }
  
  function removeFromCart(index) {
      if (confirm('Are you sure you want to remove this item?')) {
          const formData = new FormData();
          formData.append('action', 'remove_item');
          formData.append('index', index);
          
          fetch('customer-order-history.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // Update localStorage as well
                  const cart = getLocalStorageCart();
                  cart.splice(index, 1);
                  localStorage.setItem('farmvilleCart', JSON.stringify(cart));
                  updateCartCount(data.cart);
                  location.reload(); // Only reload if necessary for other updates
              }
          });
      }
  }
  
  document.getElementById('clearCart').addEventListener('click', function() {
      if (confirm('Are you sure you want to clear your cart?')) {
          const formData = new FormData();
          formData.append('action', 'clear_cart');
          
          fetch('customer-order-history.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // Clear localStorage as well
                  localStorage.removeItem('farmvilleCart');
                  updateCartCount([]);
                  location.reload(); // Only reload if necessary for other updates
              }
          });
      }
  });
  
  document.getElementById('checkoutBtn').addEventListener('click', function() {
      window.location.href = 'customer-checkout.php';
  });

// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const body = document.body;

  // First, ensure the sidebar is closed by default
  body.classList.remove('sidebar-open');

  // Simple toggle function
  function toggleSidebar() {
    body.classList.toggle('sidebar-open');
  }

  // Click handler for the toggle button
  sidebarToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleSidebar();
  });

  // Close sidebar when clicking outside
  document.addEventListener('click', function(e) {
    if (body.classList.contains('sidebar-open') && 
        !sidebar.contains(e.target) && 
        !sidebarToggle.contains(e.target)) {
      body.classList.remove('sidebar-open');
    }
  });

  // Prevent clicks inside sidebar from closing it
  sidebar.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  // For desktop hover effect (optional)
  if (window.matchMedia("(min-width: 769px)").matches) {
    let hoverTimeout;

    sidebarToggle.addEventListener('mouseenter', function() {
      clearTimeout(hoverTimeout);
      body.classList.add('sidebar-open');
    });

    sidebar.addEventListener('mouseleave', function() {
      hoverTimeout = setTimeout(function() {
        body.classList.remove('sidebar-open');
      }, 300);
    });

    sidebar.addEventListener('mouseenter', function() {
      clearTimeout(hoverTimeout);
    });
  }
});

  // Sync cart on page load
  document.addEventListener('DOMContentLoaded', function() {
    syncCartWithServer();
  });
</script>
</body>
</html>
<?php
$conn->close();
?>