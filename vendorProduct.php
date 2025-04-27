<?php
include 'db.php';

// Get c
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product</title>
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
        ul { list-style: none; padding: 0; }
        ul li { margin: 10px 0; }
        ul li a { display: flex; align-items: center; }
        .icon { margin-right: 10px; font-size: 20px; }
        .input-group .form-control::placeholder {
            color: #aaa;
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
    <h2>Product Information</h2>

    <!-- Search Bar with Icon -->
    <div class="d-flex justify-content-end mb-3">
        <div class="input-group w-25">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i>🔍</span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
        </div>
    </div>

    <div class="table-container">
        <table class="table table-bordered">
            <thead class="table-success">
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Type</th>
                    <th>Variety</th>
                    <th>Seasonality</th>
                    <th>Price (per kg)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if 'delete' GET parameter is set to delete a product
                if (isset($_GET['delete_product_name'])) {
                    $deleteProductName = urldecode($_GET['delete_product_name']);
                    $deleteSQL = "DELETE FROM Product_T WHERE ProductName = '$deleteProductName'";

                    if ($conn->query($deleteSQL) === TRUE) {
                        echo "<p class='text-success'>Product deleted successfully!</p>";
                    } else {
                        echo "<p class='text-danger'>Error: " . $conn->error . "</p>";
                    }
                }

                // Fetch and display product information
                $query = "SELECT * FROM Product_T";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $productName = urlencode($row['ProductName']); // encode for URL safety
                        echo "<tr>
                                <td>{$row['ProductID']}</td>
                                <td>{$row['ProductName']}</td>
                                <td>{$row['ProductType']}</td>
                                <td>{$row['Variety']}</td>
                                <td>{$row['Seasonality']}</td>
                                <td>{$row['CurrentPrice']} tk</td>
                                <td>
                                    <a href='vendorUpdate.php?product_name=$productName' class='btn btn-warning btn-sm'>Edit</a>
                                    <a href='vendorProduct.php?delete_product_name=$productName' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this product?\")'>Delete</a>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="vendorAddProduct.php" class="btn btn-success">+ Add New Product</a>
    </div>
</div>

<!-- Search script -->
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

</body>
</html>
