<?php
include 'db.php';

if (isset($_GET['role'])) {
    $role = $_GET['role'];
    $formTitle = ucfirst($role) . " Form";
    $tableMap = [
        'farmer' => ['table' => 'Farmer_T', 'name' => 'FarmerName', 'address' => 'FarmerAddress', 'contact' => 'Contact'],
        'vendor' => ['table' => 'Vendor_T', 'name' => 'VendorName', 'address' => 'VendorAddress', 'contact' => 'Contact'],
        'customer' => ['table' => 'Customer_T', 'name' => 'CustomerName', 'address' => 'CustomerAddress', 'contact' => 'Contact']
    ];

    if (array_key_exists($role, $tableMap)) {
        $table = $tableMap[$role]['table'];
        $nameField = $tableMap[$role]['name'];
        $addressField = $tableMap[$role]['address'];
        $contactField = $tableMap[$role]['contact'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'];
            $address = $_POST['address'];
            $contact = $_POST['contact'];

            // Prepared statement to avoid SQL injection
            $stmt = $conn->prepare("INSERT INTO $table ($nameField, $addressField, $contactField) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $address, $contact);

            if ($stmt->execute()) {
                echo "<div class='toast-msg alert alert-success'>New $role added successfully!</div>";
            } else {
                echo "<div class='toast-msg alert alert-danger'>Error adding new $role: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    } else {
        die("Invalid role.");
    }
} else {
    die("Role not specified.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New <?php echo $formTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2><?php echo $formTitle; ?></h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" class="form-control" id="address" name="address" required>
        </div>
        <div class="mb-3">
            <label for="contact" class="form-label">Contact</label>
            <input type="text" class="form-control" id="contact" name="contact" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="buyerAndSellerDirectory.php" class="btn btn-secondary">Back to Directory</a>
    </form>
</div>
</body>
</html>
