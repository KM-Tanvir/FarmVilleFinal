<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];

    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO Customer_T (CustomerName, CustomerAddress, Contact) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $address, $contact); // use "sss" for string binding

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Customer added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
</head>
<body class="container mt-5">
    <h2>Add New Customer</h2>

    <?php if (isset($message)) echo $message; ?>

    <form method="POST">
        <div class="form-group">
            <label for="name">Customer Name</label>
            <input type="text" class="form-control" id="name" name="name" required maxlength="30">
        </div>
        <div class="form-group">
            <label for="address">Customer Address</label>
            <input type="text" class="form-control" id="address" name="address" required maxlength="50">
        </div>
        <div class="form-group">
            <label for="contact">Contact Number</label>
            <input type="text" class="form-control" id="contact" name="contact" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <a href="buyerAndSellerDirectory.php" class="btn btn-info">Back to Directory</a>
    </form>
</body>
</html>
