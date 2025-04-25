<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #28a745; color: white; padding: 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; }
        .sidebar a:hover { text-decoration: underline; }
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        .table-container { max-height: 500px; overflow-y: auto; }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Product Information</h2>
    <a href="vendorHome.php">Home 🏠</a>
    <a href="vendorProduct.php">Product Info 📦</a>
    <a href="vendorGraph.php">Graph/Chart 📊</a>
    <a href="buyerAndSellerDirectory.php">Buyer & Seller Directory 📖</a>
    <a href="vendorProfile.php">Profile 👤</a>
    <a href="login.php">Sign Out 🚪</a>
</div>

<div class="content">
    <h2>Product Information</h2>

    <div class="table-container">
        <table class="table table-bordered">
            <thead class="table-success">
                <tr>
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
                                <td>{$row['ProductName']}</td>
                                <td>{$row['ProductType']}</td>
                                <td>{$row['Variety']}</td>
                                <td>{$row['Seasonality']}</td>
                                <td>{$row['CurrentPrice']}</td>
                                <td>
                                    <a href='vendorUpdate.php?product_name=$productName' class='btn btn-warning btn-sm'>Edit</a>
                                    <a href='vendorProduct.php?delete_product_name=$productName' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this product?\")'>Delete</a>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="vendorAddProduct.php" class="btn btn-success">+ Add New Product</a>
    </div>
</div>
</body>
</html>
