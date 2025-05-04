<?php
include 'db.php';

// Get current page name
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

<div class="container">
<br><br><br>
    <h2>Product List</h2>
    <?php 
    // Handle Delete Action
    if (isset($_GET['delete_product_id'])) {
        $delete_id = $_GET['delete_product_id'];
        $delete_query = "DELETE FROM Product_T WHERE ProductID = '$delete_id'";
        if ($conn->query($delete_query) === TRUE) {
            $message = "Product deleted successfully.";
        } else {
            $message = "Error deleting product: " . $conn->error;
        }
    }

    if (isset($message)) echo "<div class='alert alert-info'>$message</div>";
    ?>
    <!-- Search Bar -->
    <div class="d-flex justify-content-end mb-3">
        <div class="input-group w-25">
            <span class="input-group-text bg-white">🔍</span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
        </div>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Yield</th>
                <th>Acreage</th>
                <th>Cost</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch products from database
            $query = "SELECT * FROM Product_T";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['ProductID']}</td>
                            <td>{$row['ProductName']}</td>
                            <td>{$row['ProductYield']}</td>
                            <td>{$row['ProductAcreage']}</td>
                            <td>{$row['Cost']}</td>
                            <td>
                                <a href='HistoricalDataEdit.php?product_id={$row['ProductID']}' class='btn btn-warning btn-sm'>Edit</a>
                                <a href='HistoricalData.php?delete_product_id={$row['ProductID']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this product?\")'>Delete</a>
                            </td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>No products found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <a href="vendorAddProduct.php" class="btn btn-success mb-3">Add New Product</a>
</div>

<!-- Live Search Filter -->
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
