<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Profile</title>
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
<div class="container mt-4">
    <h2>Vendor Profile</h2>
    <?php
    $vendor_id = 1; // Hardcoded for now – make dynamic if needed
    $query = "SELECT name, email, phone, address FROM vendors WHERE id = $vendor_id";
    $result = $conn->query($query);
    $vendor = $result->fetch_assoc();
    ?>
    <table class="table table-striped">
        <tr><th>Name</th><td><?php echo $vendor['name']; ?></td></tr>
        <tr><th>Email</th><td><?php echo $vendor['email']; ?></td></tr>
        <tr><th>Phone</th><td><?php echo $vendor['phone']; ?></td></tr>
        <tr><th>Address</th><td><?php echo $vendor['address']; ?></td></tr>
    </table>
</div>
</body>
</html>
