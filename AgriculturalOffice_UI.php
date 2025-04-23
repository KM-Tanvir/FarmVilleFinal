
<!-- Agricultural_officer_UI.php -->
<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agri Officer - Home</title>
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
    <h2>Agri Officer</h2>
    <a href="Agricultural_officer_UI.php">Home</a>
    <a href="product_info.php">Product Info</a>
    <a href="graph_chart.php">Graph/Chart</a>
    <a href="profile.php">Profile</a>
    <a href="signout.php">Sign Out</a>
</div>
<div class="content">
    <h1>Welcome, Agricultural Officer!</h1>
    <p>Manage agricultural products and view harvest data easily.</p>
</div>
</body>
</html>