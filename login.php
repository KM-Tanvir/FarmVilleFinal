<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_role = $_POST['user_role'];
    $password = $_POST['password'];

    if ($password !== '1234') {
        echo "<script>alert('Incorrect password!'); window.location.href='index.php';</script>";
        exit();
    }

    // Redirect based on role
    switch ($user_role) {
        case 'Admin':
            header("Location: vendorHome.php");
            break;
        // You can later add other roles:
        // case 'Admin':
        //     header("Location: adminHome.php");
        //     break;

        default:
            echo "<script>alert('This role is not yet configured.'); window.location.href='index.php';</script>";
            break;
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
