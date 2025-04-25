<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">

<?php
$role = $_GET['role'] ?? '';
$id = $_GET['id'] ?? '';

if (!$role || !$id) {
    echo "<p class='text-danger'>Invalid parameters.</p>";
    exit;
}

$tableMap = [
    'farmer' => ['table' => 'Farmer_T', 'id' => 'FarmerID', 'name' => 'FarmerName', 'address' => 'FarmerAddress'],
    'vendor' => ['table' => 'Vendor_T', 'id' => 'VendorID', 'name' => 'VendorName', 'address' => 'VendorAddress'],
    'customer' => ['table' => 'Customer_T', 'id' => 'CustomerID', 'name' => 'CustomerName', 'address' => 'CustomerAddress'],
];

if (!isset($tableMap[$role])) {
    echo "<p class='text-danger'>Unknown role.</p>";
    exit;
}

$map = $tableMap[$role];
$query = "SELECT * FROM {$map['table']} WHERE {$map['id']} = $id";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $entry = $result->fetch_assoc();
} else {
    echo "<p class='text-danger'>Entry not found.</p>";
    exit;
}
?>

<h2>Edit <?php echo ucfirst($role); ?> Info</h2>
<form method="POST">
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($entry[$map['name']]); ?>" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($entry[$map['address']]); ?>" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Contact</label>
        <input type="text" name="contact" value="<?php echo htmlspecialchars($entry['Contact']); ?>" class="form-control" required>
    </div>
    <button type="submit" name="update" class="btn btn-success">Update</button>
</form>

<?php
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];

    $updateSQL = "UPDATE {$map['table']} 
                  SET {$map['name']} = '$name', {$map['address']} = '$address', Contact = '$contact'
                  WHERE {$map['id']} = $id";

    if ($conn->query($updateSQL)) {
        echo "<p class='text-success mt-3'>Updated Successfully! <a href='buyerAndSellerDirectory.php'>Go Back</a></p>";
    } else {
        echo "<p class='text-danger mt-3'>Error: " . $conn->error . "</p>";
    }
}
?>

</body>
</html>
