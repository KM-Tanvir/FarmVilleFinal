<!DOCTYPE html>
<html>
<head>
    <title>FarmVille Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f8f9fa; }
        .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="login-box">
        <h3 class="mb-4">Login to FarmVille</h3>
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="user_role" class="form-label">Select Role</label>
                <select class="form-select" id="user_role" name="user_role" required>
                    <option value="">-- Select User Role --</option>
                    <option value="Vendor">Vendor</option>
                    <option value="Admin">Admin</option>
                    <option value="Farmer">Farmer</option>
                    <option value="Customer">Customer</option>
                    <option value="Agricultural Office">Agricultural Office</option>
                    <option value="Warehouse Manager">Warehouse Manager</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Login</button>
        </form>
    </div>
</body>
</html>
