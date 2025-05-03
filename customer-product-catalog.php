<?php
require_once 'config.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmville";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Corrected SQL query to match the product_t table structure
$sql = "SELECT 
            product_id, 
            product_name, 
            product_type, 
            variety, 
            seasonality, 
            current_price, 
            stock_status, 
            last_updated, 
            delivery_estimate, 
            bulk_discount 
        FROM product_t";
$result = $conn->query($sql);

$products = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    echo "No products found in the database.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FarmVille — Product Catalog</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

    /* ===== TABLE STYLES ===== */
    .product-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin: 25px 0;
      text-align: left;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .product-table th,
    .product-table td {
      padding: 15px;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .product-table th {
      background: linear-gradient(to right, var(--secondary), var(--primary));
      color: white;
      font-weight: 500;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }

    .product-table tr:nth-child(even) {
      background-color: rgba(44, 141, 173, 0.03);
    }

    .product-table tr:hover {
      background-color: rgba(44, 141, 173, 0.08);
    }

    /* Status indicators */
    .in-stock {
      color: var(--success);
      font-weight: 500;
      background: rgba(40, 167, 69, 0.1);
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    .low-stock {
      color: var(--warning);
      font-weight: 500;
      background: rgba(255, 193, 7, 0.1);
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    .almost-gone {
      color: #ff6b35;
      font-weight: 500;
      background: rgba(255, 107, 53, 0.1);
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    .in-season {
      color: var(--success);
      font-weight: 600;
      background: rgba(40, 167, 69, 0.1);
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    .off-season {
      color: var(--danger);
      font-weight: 600;
      background: rgba(220, 53, 69, 0.1);
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    /* ===== BUTTON STYLES ===== */
    .btn {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      display: inline-block;
      transition: all 0.3s ease;
      cursor: pointer;
      border: none;
      font-size: 14px;
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

    .add-to-cart-btn {
      padding: 8px 15px;
      font-size: 13px;
    }

    /* ===== ORDER ACTIONS ===== */
    .order-actions {
      display: flex;
      justify-content: center;
      gap: 10px;
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

    /* ===== ANIMATIONS ===== */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    section, button, a.btn {
      animation: fadeIn 0.8s ease-out forwards;
    }

    /* ===== RESPONSIVE STYLES ===== */
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
      .product-table {
        display: block;
        overflow-x: auto;
      }
      
      .product-table td {
        min-width: 120px;
      }
      
      .sidebar {
        padding-top: 100px;
      }
      
      .sidebar-toggle {
        top: 100px;
      }
    }

    @media (max-width: 576px) {
      .product-table td {
        display: block;
        text-align: right;
        padding-left: 50%;
        position: relative;
      }
      
      .product-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 45%;
        padding-right: 10px;
        text-align: left;
        font-weight: bold;
      }
      
      .product-table th {
        display: none;
      }
      
      .order-actions {
        justify-content: flex-end;
      }
      
      .btn {
        padding: 8px 15px;
        font-size: 13px;
      }
     
      .chart-container {
  margin-top: 40px;
  position: relative;
  height: 500px;
  min-height: 500px;
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);

}
 
      
    }
  </style>
</head>
<body>

  <header class="navbar">
    <div class="logo">
        <img src="farmville-logo.png" alt="FarmVille Logo" class="logo-img" />
    </div>

    <div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search for products..." />
    <button onclick="searchProducts()"><i class="fas fa-search"></i></button>
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

<div class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</div>

<div class="main-content">
    <section class="content-box">
        <h2><i class="fas fa-tags"></i> Current Market Prices</h2>
        <p>We source directly from trusted vendors and supply the best to our customers all over Bangladesh at the lowest prices!</p>

        <table class="product-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Type</th>
                    <th>Variety</th>
                    <th>Price (per kg)</th>
                    <th>Seasonality</th>
                    <th>Stock Status</th>
                    <th>Last Updated</th>
                    <th>Delivery Estimate</th>
                    <th>Bulk Discount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="productList">
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td data-label="Product Name"><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td data-label="Type"><?php echo htmlspecialchars($product['product_type']); ?></td>
                        <td data-label="Variety"><?php echo htmlspecialchars($product['variety']); ?></td>
                        <td data-label="Price (per kg)"><?php echo htmlspecialchars($product['current_price']); ?> ৳</td>
                        <td data-label="Seasonality">
                            <span class="<?php
                                echo $product['seasonality'] == 'In Season' ? 'in-season' :
                                    ($product['seasonality'] == 'Off Season' ? 'off-season' :
                                    ($product['seasonality'] == 'Peak Season' ? 'peak-season' : 'year-round'));
                            ?>">
                                <?php echo htmlspecialchars($product['seasonality']); ?>
                            </span>
                        </td>
                        <td data-label="Stock Status">
                            <span class="<?php
                                echo $product['stock_status'] == 'In Stock' ? 'in-stock' :
                                    ($product['stock_status'] == 'Low Stock' ? 'low-stock' : 'almost-gone');
                            ?>">
                                <?php echo htmlspecialchars($product['stock_status']); ?>
                            </span>
                        </td>
                        <td data-label="Last Updated"><?php echo htmlspecialchars($product['last_updated']); ?></td>
                        <td data-label="Delivery Estimate"><?php echo htmlspecialchars($product['delivery_estimate']); ?></td>
                        <td data-label="Bulk Discount"><?php echo htmlspecialchars($product['bulk_discount']); ?></td>
                        <td data-label="Action">
                            <div class="order-actions">
                                <button class="btn add-to-cart-btn" onclick="addToCart('<?php echo htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['current_price'], ENT_QUOTES); ?> ৳', 'FarmVille')">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="chart-container" style="margin-top: 40px; position: relative; height: 400px; min-height: 400px;">
    <canvas id="priceChart"></canvas>
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
// Initialize cart in localStorage if it doesn't exist
if (!localStorage.getItem('farmvilleCart')) {
    localStorage.setItem('farmvilleCart', JSON.stringify([]));
}

function searchProducts() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#productList tr');
    let resultsFound = false;

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        Array.from(cells).forEach(cell => {
            const cellText = cell.textContent.toLowerCase();
            if (cellText.includes(input)) {
                found = true;
            }
        });
        
        if (found) {
            row.style.display = '';
            resultsFound = true;
        } else {
            row.style.display = 'none';
        }
    });

    const noResultsMsg = document.getElementById('noResultsMsg');
    if (noResultsMsg) {
        noResultsMsg.style.display = resultsFound ? 'none' : 'block';
    } else if (!resultsFound) {
        const msg = document.createElement('div');
        msg.id = 'noResultsMsg';
        msg.className = 'no-results-message';
        msg.innerHTML = `
            <i class="fas fa-search"></i>
            <p>No products found matching "${input}"</p>
            <button class="btn" onclick="clearSearch()">Clear Search</button>
        `;
        const table = document.querySelector('.product-table');
        table.parentNode.insertBefore(msg, table.nextSibling);
    }

    updateChartWithFilteredData(input);
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    searchProducts();
}

function addToCart(crop, price) {
    const cart = JSON.parse(localStorage.getItem('farmvilleCart')) || [];

    const existingProduct = cart.find(item => item.crop === crop);

    if (existingProduct) {
        existingProduct.quantity += 1;
    } else {
        cart.push({
            crop: crop,
            price: price,
            quantity: 1
        });
    }

    localStorage.setItem('farmvilleCart', JSON.stringify(cart));
    updateCartCount();
    showNotification(`${crop} added to cart!`);
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('farmvilleCart')) || [];
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    document.getElementById('cartCount').innerText = totalItems;
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-check-circle"></i> ${message}
        </div>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Chart initialization function with proper bar spacing
function initChart() {
    const ctx = document.getElementById('priceChart').getContext('2d');
    
    // Get all products from the PHP data
    const chartLabels = [];
    const chartData = [];
    const productVarieties = [];
    const stockStatuses = [];
    
    <?php foreach ($products as $product): ?>
        chartLabels.push('<?php echo addslashes($product['product_name']); ?>');
        chartData.push(<?php echo floatval($product['current_price']); ?>);
        productVarieties.push('<?php echo addslashes($product['variety']); ?>');
        stockStatuses.push('<?php echo addslashes($product['stock_status']); ?>');
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
                // Control bar spacing here
                categoryPercentage: 0.8,  // 80% of the available width for each category
                barPercentage: 0.7,       // 70% of the available width for each bar
                // Fixed bar thickness can also be used instead:
                // barThickness: 25,
                // maxBarThickness: 30
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
                                `Variety: ${productVarieties[index] || 'N/A'}`,
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
                        padding: 10 // Add padding between labels
                    },
                    // Add additional space between bars
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
                    
                    const rows = document.querySelectorAll('#productList tr');
                    rows.forEach(row => {
                        row.style.backgroundColor = '';
                        const nameCell = row.querySelector('td[data-label="Product Name"]');
                        if (nameCell && nameCell.textContent.trim() === productName) {
                            row.style.backgroundColor = 'rgba(44, 141, 173, 0.15)';
                            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            
                            setTimeout(() => {
                                row.style.transition = 'background-color 0.5s';
                                row.style.backgroundColor = 'rgba(44, 141, 173, 0.05)';
                            }, 1000);
                        }
                    });
                }
            },
            // Add spacing between bars
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

function updateChartWithFilteredData(searchTerm) {
    if (!window.priceChart) return;
    
    const rows = document.querySelectorAll('#productList tr');
    const visibleProducts = [];
    const visiblePrices = [];
    const visibleVarieties = [];
    const visibleStockStatuses = [];
    
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const productName = row.querySelector('td[data-label="Product Name"]');
            const priceCell = row.querySelector('td[data-label="Price (per kg)"]');
            const varietyCell = row.querySelector('td[data-label="Variety"]');
            const stockCell = row.querySelector('td[data-label="Stock Status"] span');
            
            if (productName && priceCell) {
                visibleProducts.push(productName.textContent.trim());
                const priceText = priceCell.textContent.trim();
                visiblePrices.push(parseFloat(priceText.replace('৳', '').trim()));
                visibleVarieties.push(varietyCell ? varietyCell.textContent.trim() : '');
                visibleStockStatuses.push(stockCell ? stockCell.textContent.trim() : '');
            }
        }
    });
    
    // Update chart data
    window.priceChart.data.labels = visibleProducts;
    window.priceChart.data.datasets[0].data = visiblePrices;
    
    // Update colors based on stock status
    window.priceChart.data.datasets[0].backgroundColor = visibleStockStatuses.map(status => {
        if (status === 'In Stock') return 'rgba(40, 167, 69, 0.7)';
        if (status === 'Low Stock') return 'rgba(255, 193, 7, 0.7)';
        if (status === 'Almost Gone') return 'rgba(220, 53, 69, 0.7)';
        return 'rgba(108, 117, 125, 0.7)';
    });
    
    window.priceChart.update();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initChart();
    updateCartCount();
    
    const chartContainer = document.querySelector('.chart-container');
    chartContainer.style.opacity = '0';
    chartContainer.style.transform = 'translateY(20px)';
    chartContainer.style.transition = 'all 0.8s ease-out';
    
    setTimeout(() => {
        chartContainer.style.opacity = '1';
        chartContainer.style.transform = 'translateY(0)';
    }, 300);
});

// Sidebar functionality
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const body = document.body;
let sidebarTimeout;

sidebarToggle.addEventListener('mouseenter', () => {
    clearTimeout(sidebarTimeout);
    body.classList.add('sidebar-open');
});

sidebar.addEventListener('mouseenter', () => {
    clearTimeout(sidebarTimeout);
});

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

sidebarToggle.addEventListener('click', () => {
    body.classList.toggle('sidebar-open');
});
</script>

</body>
</html>
<?php
$conn->close();
?>