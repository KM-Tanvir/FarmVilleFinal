<?php
require_once 'config.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmville";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the first two products for the "Fresh Picks" section
$sql = "SELECT product_id, product_name, product_type, current_price, stock_status, seasonality 
        FROM product_t 
        LIMIT 2";
$result = $conn->query($sql);

$freshPicks = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $freshPicks[] = $row;
    }
}

// Get all products for the chart
$chartSql = "SELECT product_name, current_price, stock_status FROM product_t";
$chartResult = $conn->query($chartSql);

$chartData = [];
if ($chartResult->num_rows > 0) {
    while($row = $chartResult->fetch_assoc()) {
        $chartData[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FarmVille — Your Agri Market Partner</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

/* ===== PRODUCT STYLES ===== */
.product-highlight-card {
  background: white;
  border-radius: 15px;
  padding: 0;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(44, 141, 173, 0.1);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 0;
}

.highlight-product {
  display: flex;
  position: relative;
  border-bottom: 1px solid rgba(44, 141, 173, 0.1);
}

.highlight-product:last-child {
  border-bottom: none;
}

.product-image {
  width: 120px;
  background-size: cover;
  background-position: center;
  transition: all 0.3s ease;
}

.product-content {
  flex: 1;
  padding: 20px;
}

.product-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.in-season-badge {
  background: rgba(40, 167, 69, 0.15);
  color: var(--success);
  border: 1px solid rgba(40, 167, 69, 0.3);
}

.limited-badge {
  background: rgba(255, 193, 7, 0.15);
  color: var(--warning);
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.product-content h3 {
  color: var(--dark);
  margin-bottom: 10px;
  font-size: 1.2rem;
  position: relative;
  padding-bottom: 8px;
}

.product-content h3::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 40px;
  height: 2px;
  background: linear-gradient(to right, var(--primary), var(--secondary));
}

.product-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
}

.price-tag {
  font-weight: 700;
  color: var(--dark);
  font-size: 1.1rem;
}

.stock-status {
  font-size: 0.85rem;
  padding: 3px 8px;
  border-radius: 4px;
}

.in-stock {
  background: rgba(40, 167, 69, 0.1);
  color: var(--success);
}

.low-stock {
  background: rgba(255, 193, 7, 0.1);
  color: var(--warning);
}

.product-description p {
  color: #555;
  font-size: 0.9rem;
  margin-bottom: 15px;
}

.product-actions {
  display: flex;
  justify-content: flex-end;
}

.view-details {
  color: var(--secondary);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.view-details:hover {
  color: var(--dark);
  transform: translateX(5px);
}

.view-details i {
  font-size: 0.8rem;
  transition: all 0.3s ease;
}

.view-details:hover i {
  transform: translateX(3px);
}

/* Chart container styles */
.chart-container {
  margin-top: 30px;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  position: relative;
  height: 400px;
}

/* Updated button styling to match existing buttons */
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
  margin: 20px auto;
  width: calc(100% - 40px);
  text-align: center;
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

/* ===== TABLE STYLES ===== */
.seasonality-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin: 25px 0;
  text-align: left;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.seasonality-table th,
.seasonality-table td {
  padding: 15px;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.seasonality-table th {
  background: linear-gradient(to right, var(--secondary), var(--primary));
  color: white;
  font-weight: 500;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

.seasonality-table tr:nth-child(even) {
  background-color: rgba(44, 141, 173, 0.03);
}

.seasonality-table tr:hover {
  background-color: rgba(44, 141, 173, 0.08);
}

/* ===== RECOMMENDATION STYLES ===== */
.recommendation-card {
  background-color: white;
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 20px;
  border-left: 4px solid var(--secondary);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.recommendation-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.recommendation-card h3 {
  color: var(--dark);
  margin-bottom: 15px;
  font-size: 1.2rem;
}

.recommendation-card ul {
  padding-left: 20px;
}

.recommendation-card li {
  margin-bottom: 10px;
  position: relative;
  list-style-type: none;
  padding-left: 25px;
}

.recommendation-card li::before {
  content: '→';
  position: absolute;
  left: 0;
  color: var(--primary);
  font-weight: bold;
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

section, button, a.btn, form {
  animation: fadeIn 0.8s ease-out forwards;
}

/* ===== RESPONSIVE STYLES ===== */
@media (max-width: 992px) {
  .hero-section h1 {
    font-size: 2rem;
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
  
  .hero-section {
    padding: 60px 15px;
    margin: 20px auto;
  }
  
  .hero-section h1 {
    font-size: 1.8rem;
  }
  
  .content-box {
    padding: 20px;
  }
  
  .highlight-product {
    flex-direction: column;
  }
  
  .product-image {
    width: 100%;
    height: 150px;
  }
}

@media (max-width: 576px) {
  .hero-section h1 {
    font-size: 1.5rem;
  }
  
  .content-box h2 {
    font-size: 1.3rem;
  }
  
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
        <span class="cart-count" id="cartCount">0</span>
      </a>
    </div>
    
    <a href="customer-info.php" title="My Account">
      <img src="account-icon.png" alt="My Account" class="account-icon">
    </a>
  </div>
</header>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
  <a href="customer-homepage.php" class="active"><i class="fas fa-home"></i> Home</a>
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
      <h1>Buy Smart, Farm Fresh — Real-Time Market & Product Insights</h1>
      <p>Get the best agricultural products at competitive prices with real-time data</p>
    </div>
  </section>

  <section id="product-highlights" class="content-box">
    <h2><i class="fas fa-spa" style="color: var(--success);"></i> Fresh Picks of the Season</h2>
    <div class="product-highlight-card">
      <div class="product-grid">
        <?php foreach ($freshPicks as $index => $product): ?>
        <div class="highlight-product">
          <div class="product-badge <?php echo $product['seasonality'] == 'In Season' ? 'in-season-badge' : 'limited-badge'; ?>">
            <?php echo $product['seasonality'] == 'In Season' ? 'Seasonal Favorite' : 'Limited Stock'; ?>
          </div>
          <div class="product-content">
            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
            <div class="product-meta">
              <span class="price-tag">৳<?php echo htmlspecialchars($product['current_price']); ?>/kg</span>
              <span class="stock-status <?php echo $product['stock_status'] == 'In Stock' ? 'in-stock' : 'low-stock'; ?>">
                <?php echo htmlspecialchars($product['stock_status']); ?>
              </span>
            </div>
            <div class="product-description">
              <p>Freshly harvested premium quality <?php echo htmlspecialchars(strtolower($product['product_name'])); ?> with excellent shelf life</p>
            </div>
            <div class="product-actions">
              <a href="customer-product-catalog.php#<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $product['product_name']))); ?>" class="view-details">View Details <i class="fas fa-chevron-right"></i></a>
            </div>
          </div>
          <div class="product-image" style="background-image: url('<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $product['product_name']))); ?>.jpg');"></div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Chart container -->
      <div class="chart-container">
        <canvas id="priceChart"></canvas>
      </div>
      
      <a href="customer-product-catalog.php" class="btn">
        Explore All Products <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </section>

  <section id="crop-seasonality" class="content-box">
    <h2><i class="fas fa-calendar-alt"></i> Crop Seasonality Guide</h2>
    <table class="seasonality-table">
      <thead>
        <tr>
          <th>Crop</th>
          <th>Planting Season</th>
          <th>Harvest Season</th>
          <th>Best Buying Time</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Rice (Boro)</td>
          <td>December – February</td>
          <td>April – May</td>
          <td>May – June</td>
        </tr>
        <tr>
          <td>Maize</td>
          <td>November – January</td>
          <td>March – May</td>
          <td>April – June</td>
        </tr>
        <tr>
          <td>Wheat</td>
          <td>November – December</td>
          <td>March – April</td>
          <td>April</td>
        </tr>
        <tr>
          <td>Onion</td>
          <td>October – November</td>
          <td>February – March</td>
          <td>March – April</td>
        </tr>
        <tr>
          <td>Potato</td>
          <td>October – December</td>
          <td>January – March</td>
          <td>February – April</td>
        </tr>
        <tr>
          <td>Tomato</td>
          <td>September – October</td>
          <td>December – January</td>
          <td>January</td>
        </tr>
        <tr>
          <td>Groundnut</td>
          <td>April – June</td>
          <td>August – October</td>
          <td>September – October</td>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="content-box">
    <h2><i class="fas fa-lightbulb"></i> Personalized Recommendations</h2>
    
    <div class="recommendation-card">
      <p>You frequently buy Potatoes and Rice. Consider adding these complementary products:</p>
      <ul>
        <li>🌾 Buy <strong>Wheat</strong> now — peak harvest means better pricing & bulk deals available.</li>
        <li>🥔 Pre-order <strong>Potatoes</strong> before mid-April for lowest market rates.</li>
        <li>🍅 Avoid <strong>Tomatoes</strong> right now — prices are inflated due to low stock.</li>
        <li>🧅 Stock up on <strong>Onions</strong> before monsoon hits — risk of price hike ahead.</li>
        <li>🌽 Monitor <strong>Maize</strong> prices — upward trend expected in next 10 days.</li>
        <li>🥜 Fresh batch of <strong>Groundnuts</strong> now available — ideal for resellers!</li>
        <li>🚜 Check local vendor supply for <strong>Rice</strong> — price varies by district this season.</li>
        <li>📦 Place early orders for <strong>Soybean</strong> — supply is tight this month.</li>
      </ul>
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
    // Only close if we're not moving into the sidebar
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

  // Shared function to update cart count
  function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('farmvilleCart')) || [];
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    const cartCountElements = document.querySelectorAll('.cart-count');
    
    cartCountElements.forEach(element => {
      element.innerText = totalItems;
    });
  }

  // Call this function when each page loads
  document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Initialize the price chart
    initPriceChart();
  });

  // Initialize the price chart with data from PHP
  function initPriceChart() {
    const ctx = document.getElementById('priceChart').getContext('2d');
    
    // Prepare chart data from PHP
    const chartLabels = [];
    const chartData = [];
    const stockStatuses = [];
    
    <?php foreach ($chartData as $product): ?>
      chartLabels.push('<?php echo htmlspecialchars($product['product_name']); ?>');
      chartData.push(<?php echo htmlspecialchars($product['current_price']); ?>);
      stockStatuses.push('<?php echo htmlspecialchars($product['stock_status']); ?>');
    <?php endforeach; ?>
    
    // Generate dynamic colors based on stock status
    const backgroundColors = stockStatuses.map(status => {
      if (status === 'In Stock') return 'rgba(40, 167, 69, 0.7)';
      if (status === 'Low Stock') return 'rgba(255, 193, 7, 0.7)';
      if (status === 'Almost Gone') return 'rgba(220, 53, 69, 0.7)';
      return 'rgba(108, 117, 125, 0.7)';
    });
    
    const borderColors = stockStatuses.map(status => {
      if (status === 'In Stock') return 'rgba(40, 167, 69, 1)';
      if (status === 'Low Stock') return 'rgba(255, 193, 7, 1)';
      if (status === 'Almost Gone') return 'rgba(220, 53, 69, 1)';
      return 'rgba(108, 117, 125, 1)';
    });
    
    // Create the chart
    window.priceChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Price per kg (৳)',
          data: chartData,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
          borderWidth: 2,
          borderRadius: 6,
          borderSkipped: false,
          hoverBackgroundColor: backgroundColors.map(color => color.replace('0.7', '0.9')),
          hoverBorderWidth: 3,
          categoryPercentage: 0.8,
          barPercentage: 0.7
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: 'Product Price Comparison',
            font: {
              size: 18,
              weight: 'bold'
            },
            color: '#1f408e',
            padding: {
              top: 10,
              bottom: 20
            }
          },
          legend: {
            display: true,
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'rectRounded',
              padding: 20
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const index = context.dataIndex;
                return [
                  `Price: ৳${context.raw.toFixed(2)}`,
                  `Stock: ${stockStatuses[index] || 'N/A'}`
                ];
              }
            },
            backgroundColor: 'rgba(0,0,0,0.8)',
            titleFont: {
              size: 14,
              weight: 'bold'
            },
            bodyFont: {
              size: 12
            },
            padding: 12,
            cornerRadius: 8,
            displayColors: false
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return '৳' + value;
              },
              font: {
                weight: 'bold'
              }
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          y: {
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              font: {
                size: 12,
                weight: 'bold'
              },
              autoSkip: false,
              padding: 10
            },
            afterFit: function(axis) {
              axis.paddingTop = 20;
              axis.paddingBottom = 20;
            }
          }
        },
        onClick: function(evt, elements) {
          if (elements.length > 0) {
            const index = elements[0].index;
            const productName = this.data.labels[index];
            
            // Scroll to the product in the catalog page
            window.location.href = `customer-product-catalog.php#${productName.toLowerCase().replace(/ /g, '-')}`;
          }
        },
        layout: {
          padding: {
            left: 20,
            right: 20,
            top: 20,
            bottom: 20
          }
        }
      }
    });
  }
</script>

</body>
</html>