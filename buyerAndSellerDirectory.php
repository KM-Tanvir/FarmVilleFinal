<?php
include 'db.php';

// Delete logic
if (isset($_GET['role']) && isset($_GET['id'])) {
    $role = $_GET['role'];
    $id = intval($_GET['id']); // sanitize ID

    $tableMap = [
        'farmer' => ['table' => 'Farmer_T', 'idField' => 'FarmerID'],
        'vendor' => ['table' => 'Vendor_T', 'idField' => 'VendorID'],
        'customer' => ['table' => 'Customer_T', 'idField' => 'CustomerID']
    ];

    if (array_key_exists($role, $tableMap)) {
        $table = $tableMap[$role]['table'];
        $idField = $tableMap[$role]['idField'];

        $deleteSQL = "DELETE FROM $table WHERE $idField = $id";
        if ($conn->query($deleteSQL) === TRUE) {
            echo "<div class='toast-msg alert alert-success'>Entry deleted successfully!</div>";
        } else {
            echo "<div class='toast-msg alert alert-danger'>Error deleting entry: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyer and Seller Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #28a745; color: white; padding: 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; }
        .sidebar a:hover { text-decoration: underline; }
        .content { flex: 1; padding: 20px; }
        table { margin-bottom: 40px; }
        .toast-msg {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            padding: 12px 24px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: fadeOut 4s forwards;
        }

        @keyframes fadeOut {
            0%, 70% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                visibility: hidden;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Admin</h2>
    <a href="vendorHome.php">Home 🏠</a>
    <a href="vendorProduct.php">Product Info 📦</a>
    <a href="vendorGraph.php">Graph/Chart 📊</a>
    <a href="buyerAndSellerDirectory.php">Buyer & Seller Directory 📖</a>
    <a href="vendorProfile.php">Profile 👤</a>
    <a href="login.php">Sign Out 🚪</a>
</div>

<div class="content">
    <h2>Buyer and Seller Directory</h2>

    <!-- Helper function -->
    <?php function actionButtons($id, $role) {
        return "
        <td>
            <a href='directoryEdit.php?role=$role&id=$id' class='btn btn-sm btn-warning'>Edit</a>
            <a href='?role=$role&id=$id' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this entry?')\">Delete</a>
        </td>";
    } ?>

    <!-- Farmer Directory -->
    <h4>Farmer Directory</h4>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $farmers = $conn->query("SELECT FarmerID, FarmerName, FarmerAddress, Contact FROM Farmer_T");
            if ($farmers && $farmers->num_rows > 0) {
                while ($row = $farmers->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['FarmerName']}</td>
                            <td>{$row['FarmerAddress']}</td>
                            <td>{$row['Contact']}</td>
                            ".actionButtons($row['FarmerID'], 'farmer')."
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4' class='text-center'>No farmers found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <a href="directoryUpdate.php?role=farmer" class="btn btn-success mb-3">+ Add New Farmer</a>

    <!-- Vendor Directory -->
    <h4>Vendor Directory</h4>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $vendors = $conn->query("SELECT VendorID, VendorName, VendorAddress, Contact FROM Vendor_T");
            if ($vendors && $vendors->num_rows > 0) {
                while ($row = $vendors->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['VendorName']}</td>
                            <td>{$row['VendorAddress']}</td>
                            <td>{$row['Contact']}</td>
                            ".actionButtons($row['VendorID'], 'vendor')."
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4' class='text-center'>No vendors found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <a href="directoryUpdate.php?role=vendor" class="btn btn-success mb-3">+ Add New Vendor</a>

    <br><br><br>

    <!-- Customer Directory -->
    <h4>Customer Directory</h4>

    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $customers = $conn->query("SELECT CustomerID, CustomerName, CustomerAddress, Contact FROM Customer_T");
            if ($customers && $customers->num_rows > 0) {
                while ($row = $customers->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['CustomerName']}</td>
                            <td>{$row['CustomerAddress']}</td>
                            <td>{$row['Contact']}</td>
                            ".actionButtons($row['CustomerID'], 'customer')."
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4' class='text-center'>No customers found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Link to Add Customer Page -->
    <a href="customerUpdate.php" class="btn btn-success mb-3">+ Add New Customer</a>

</div>
</body>
</html>
