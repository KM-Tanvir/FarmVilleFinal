<?php
include 'db.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: rgb(108, 189, 246); color: white; padding: 20px; }
        .sidebar a { 
            color: white; 
            display: block; 
            margin: 15px 0; 
            padding: 10px; 
            border-radius: 8px; 
            text-decoration: none; 
            transition: background 0.3s; 
        }
        .sidebar a:hover { 
            background: rgb(50, 121, 169); 
        }
        .sidebar a.active { 
            background: rgb(63, 162, 233);
            font-weight: bold; 
            
        }
        .content { flex: 1; padding: 20px; }
        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin: 10px 0;
        }
        ul li a {
            display: flex;
            align-items: center;
        }
        .icon {
            margin-right: 10px;
            font-size: 20px;
        }
    </style>
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            sidebar.style.display = (sidebar.style.display === 'none') ? 'block' : 'none';
        }
    </script>
</head>
<body>

<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <span>☰</span>
    </button>
    <ul>
        <li>
            <a href="vendorHome.php" class="<?php echo ($current_page == 'vendorHome.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="vendorProduct.php" class="<?php echo ($current_page == 'vendorProduct.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Product Info</span>
            </a>
        </li>
        <li>
            <a href="HistoricalData.php" class="<?php echo ($current_page =='HistoricalData.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Historical Production Data</span>
            </a>
        </li>

        <li>
            <a href="DemandData.php" class="<?php echo ($current_page =='DemandData.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Demand Data</span>
            </a>
        </li>

        <li>
            <a href="SupplyLevel.php" class="<?php echo ($current_page =='SupplyLevel.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Supply Level</span>
            </a>
        </li>

        <li>
            <a href="marketPrice.php" class="<?php echo ($current_page =='marketPrice.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Historical and Current prices</span>
            </a>
        </li>

        <li>
            <a href="vendorGraph.php" class="<?php echo ($current_page == 'vendorGraph.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Graph/Chart</span>
            </a>
        </li>

        <li>
            <a href="Recommendations.php" class="<?php echo ($current_page == 'Recommendations.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Recommendations</span>
            </a>
        </li>

        <li>
            <a href="buyerAndSellerDirectory.php" class="<?php echo ($current_page == 'buyerAndSellerDirectory.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Buyer/Seller Directory</span>
            </a>
        </li>
        <li>
            <a href="vendorProfile.php" class="<?php echo ($current_page == 'vendorProfile.php') ? 'active' : ''; ?>">
                <span class="icon"></span>
                <span class="text">Profile 👤</span>
            </a>
        </li>
        <li>
            <a href="login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                <span class="icon">⏻</span>
                <span class="text">Sign Out</span>
            </a>
        </li>
    </ul>
</div>

    <div class="content">
        <h2>Add New Product (Full Details)</h2>

        <?php
        if (isset($_POST['save'])) {
            $productName = $conn->real_escape_string($_POST['product_name']);
            $productType = $conn->real_escape_string($_POST['product_type']);
            $variety = $conn->real_escape_string($_POST['variety']);
            $seasonality = $conn->real_escape_string($_POST['seasonality']);
            $currentPrice = floatval($_POST['current_price']);
            $yield = floatval($_POST['yield']);
            $acreage = floatval($_POST['acreage']);
            $cost = floatval($_POST['cost']);
            
            if (empty($productName) || empty($productType) || empty($variety) || empty($seasonality) || empty($currentPrice) || empty($yield) || empty($acreage) || empty($cost)) {
                echo "<p class='text-danger'>All fields are required!</p>";
            } else {
                $insertSQL = "INSERT INTO Product_T (ProductName, ProductType, Variety, Seasonality, CurrentPrice, Yield, Acreage, Cost)
                            VALUES ('$productName', '$productType', '$variety', '$seasonality', $currentPrice, $yield, $acreage, $cost)";
                
                if ($conn->query($insertSQL) === TRUE) {
                    echo "<p class='text-success mt-3'>Product added successfully!</p>";
                } else {
                    echo "<p class='text-danger mt-3'>Error: " . $conn->error . "</p>";
                }
            }
        }
        ?>

        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="product_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Product Type</label>
                <input type="text" name="product_type" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Variety</label>
                <input type="text" name="variety" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Seasonality</label>
                <input type="text" name="seasonality" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Current Price (Tk.)</label>
                <input type="number" step="0.01" name="current_price" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Yield</label>
                <input type="number" step="0.01" name="yield" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Acreage</label>
                <input type="number" step="0.01" name="acreage" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Cost (Tk.)</label>
                <input type="number" step="0.01" name="cost" class="form-control" required>
            </div>

            <button type="submit" name="save" class="btn btn-success">Add Product</button>
            <a href="HistoricalData.php" class="btn btn-secondary ms-2">Back</a>
        </form>
    </div>
</div>
</body>
</html>
