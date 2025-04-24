<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #28a745; color: white; padding: 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; }
        .sidebar a:hover { text-decoration: underline; }
        .content { flex: 1; padding: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2> Vendor home page</h2>
        <a href="vendorHome.php">Home</a>
        <a href="vendorProduct.php">Product Info</a>
        <a href="vendorGraph.php">Graph/Chart</a>
        <a href="vendorProfile.php">Profile</a>
        <a href="signout.php">Sign Out</a>
    </div>
<body class="p-5">
    <h2>Add New Product</h2>

<?php
// Check if the form is submitted
if (isset($_POST['add'])) {
    $productName = $_POST['ProductName'];
    $productType = $_POST['ProductType'];
    $variety = $_POST['Variety'];
    $seasonality = $_POST['Seasonality'];
    $currentPrice = $_POST['CurrentPrice'];

    // Check if fields are empty
    if (empty($productName) || empty($productType) || empty($variety) || empty($seasonality) || empty($currentPrice)) {
        echo "<p class='text-danger'>All fields are required!</p>";
    } else {
        // Insert new product into the database
        $insertSQL = "INSERT INTO Product_T (ProductName, ProductType, Variety, Seasonality, CurrentPrice) 
                        VALUES ('$productName', '$productType', '$variety', '$seasonality', '$currentPrice')";
        
        if ($conn->query($insertSQL) === TRUE) {
            echo "<p class='text-success mt-3'>Product Added Successfully!</p>";
        } else {
            echo "<p class='text-danger mt-3'>Error: " . $conn->error . "</p>";
        }
    }
}
?>

<form method="POST" action="">
    <div class="mb-3">
        <label>Product Name</label>
        <input type="text" name="ProductName" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Product Type</label>
        <input type="text" name="ProductType" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Variety</label>
        <input type="text" name="Variety" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Seasonality</label>
        <input type="text" name="Seasonality" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Price (per kg)</label>
        <input type="text" name="CurrentPrice" class="form-control" required>
    </div>
    <button type="submit" name="add" class="btn btn-success">Add Product</button>
</form>

</body>
</html>
