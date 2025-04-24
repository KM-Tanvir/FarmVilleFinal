<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <h2>Delete Product</h2>
    <form method="POST" action="">
        <input type="text" name="product_id" placeholder="Product ID" class="form-control mb-3" required>
        <button type="submit" name="delete" class="btn btn-danger">Delete</button>
    </form>

<?php
if (isset($_POST['delete'])) {
    $id = $_POST['product_id'];
    $sql = "DELETE FROM products WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "<p class='text-success mt-3'>Product Deleted Successfully!</p>";
    } else {
        echo "<p class='text-danger mt-3'>Error: " . $conn->error . "</p>";
    }
}
?>
</body>
</html>
