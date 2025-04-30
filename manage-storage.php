<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmville";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submissions
$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = sanitizeInput($_GET['delete_id']);
    $sql = "DELETE FROM storage_unit WHERE storage_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $delete_id);
    
    if ($stmt->execute()) {
        $success_message = "Record deleted successfully!";
    } else {
        $error_message = "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}

// Handle form submission (add/edit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['form_type'])) {
        if ($_POST['form_type'] == 'edit') {
            // Handle edit form submission
            $storage_id = sanitizeInput($_POST['storage_id']);
            $product_id = sanitizeInput($_POST['product_id']);
            $product_name = sanitizeInput($_POST['product_name']);
            $type = sanitizeInput($_POST['type']);
            $quantity = sanitizeInput($_POST['quantity']);
            $location = sanitizeInput($_POST['location']);
            $notes = sanitizeInput($_POST['notes']);
            
            // Update existing record
            $edit_id = sanitizeInput($_POST['edit_id']);
            $sql = "UPDATE storage_unit SET 
                    product_id = ?, 
                    product_name = ?, 
                    type = ?, 
                    quantity = ?, 
                    location = ?, 
                    notes = ? 
                    WHERE storage_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdsss", $product_id, $product_name, $type, $quantity, $location, $notes, $edit_id);
            
            if ($stmt->execute()) {
                $success_message = "Storage unit updated successfully!";
            } else {
                $error_message = "Error updating storage unit: " . $conn->error;
            }
            $stmt->close();
        } elseif ($_POST['form_type'] == 'add') {
            // Handle add new storage form submission
            $storage_id = sanitizeInput($_POST['storage_id']);
            $location = sanitizeInput($_POST['location']);
            $notes = sanitizeInput($_POST['notes']);
            
            // Insert new storage unit
            $sql = "INSERT INTO storage_unit (storage_id, location, notes) 
                    VALUES (?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $storage_id, $location, $notes);
            
            if ($stmt->execute()) {
                $success_message = "New storage unit added successfully!";
            } else {
                $error_message = "Error adding storage unit: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all storage units for display
$storage_units = array();
$sql = "SELECT * FROM storage_unit ORDER BY storage_id ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $storage_units[] = $row;
    }
}

// Close connection (will reopen if needed)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmVille - Manage Storage</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: #ffffff;
      color: #333;
      height: 100vh;
      display: flex;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 250px;
      background-color: #b3e5fc;
      padding: 20px 0;
      height: 100vh;
      border-right: 3px solid #1976d2;
      transition: all 0.3s ease;
    }

    .logo {
      text-align: center;
      padding: 0 20px 20px;
      font-size: 24px;
      font-weight: bold;
      color: #0d47a1;
      border-bottom: 2px solid #1976d2;
      margin-bottom: 20px;
    }

    .logo i {
      margin-right: 10px;
      color: #0288d1;
    }

    .nav-links {
      padding: 0 20px;
    }

    .nav-links li {
      list-style: none;
      margin-bottom: 10px;
    }

    .nav-links li a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: #333;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .nav-links li a:hover,
    .nav-links li a.active {
      background-color: #81d4fa;
      color: #01579b;
    }

    .nav-links li a i {
      margin-right: 10px;
      font-size: 18px;
      color: #0288d1;
    }

    /* Main Content Styles */
    .main-content {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
    }

    .top-nav {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-bottom: 2rem;
    }

    .user-info {
      display: flex;
      align-items: center;
      background-color: #e1f5fe;
      padding: 8px 15px;
      border-radius: 20px;
      border: 1px solid #1976d2;
    }

    .user-info .username {
      margin-right: 10px;
      color: #0d47a1;
    }

    .user-info .user-icon i {
      font-size: 18px;
      color: #0288d1;
    }

    /* Page Header */
    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 1.8rem;
      color: #0d47a1;
      display: flex;
      align-items: center;
    }

    .page-header h1 i {
      margin-right: 10px;
      color: #0288d1;
    }

    /* Tables */
    .management-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .management-table th,
    .management-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #b3e5fc;
    }

    .management-table th {
      background-color: #e1f5fe;
      color: #0d47a1;
      font-weight: 600;
      position: sticky;
      top: 0;
    }

    .management-table tr:hover {
      background-color: #f5fbff;
    }

    /* Status Badges */
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-weight: 500;
      font-size: 0.85rem;
    }

    .status-normal {
      background-color: #e1f5fe;
      color: #0288d1;
    }

    .status-warning {
      background-color: #fff8e1;
      color: #ff8f00;
    }

    .status-critical {
      background-color: #ffebee;
      color: #d32f2f;
    }

    /* Action Buttons */
    .action-btn {
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      border: none;
      margin-right: 5px;
    }

    .btn-edit {
      background-color: #0288d1;
      color: white;
    }

    .btn-delete {
      background-color: #d32f2f;
      color: white;
    }

    .action-btn:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* Forms */
    .management-form,
    .add-storage-form {
      background: #e1f5fe;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid #81d4fa;
    }

    .form-header {
      margin-bottom: 1.5rem;
    }

    .form-header h2 {
      color: #0d47a1;
      display: flex;
      align-items: center;
    }

    .form-header h2 i {
      margin-right: 10px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #0d47a1;
      font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border-radius: 8px;
      border: 1px solid #b3e5fc;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      border-color: #0288d1;
      outline: none;
      box-shadow: 0 0 0 2px rgba(2, 136, 209, 0.2);
    }

    .form-actions {
      margin-top: 1.5rem;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.3s ease;
      border: none;
    }

    .btn-primary {
      background-color: #0288d1;
      color: white;
    }

    .btn-primary:hover {
      background-color: #0277bd;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-secondary {
      background-color: #e1f5fe;
      color: #0d47a1;
      border: 1px solid #0288d1;
    }

    .btn-secondary:hover {
      background-color: #b3e5fc;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      width: 80%;
      max-width: 800px;
      animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .close:hover {
      color: #333;
    }

    /* Loading spinner */
    .loading-spinner {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.1);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid #0288d1;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
      .sidebar {
        width: 80px;
        overflow: hidden;
      }

      .logo span, .nav-links li a span {
        display: none;
      }

      .nav-links li a {
        justify-content: center;
        padding: 15px 0;
      }

      .nav-links li a i {
        margin-right: 0;
        font-size: 20px;
      }

      .management-table {
        display: block;
        overflow-x: auto;
      }

      .modal-content {
        width: 95%;
        margin: 10% auto;
        padding: 1rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
      background: #0288d1;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #0277bd;
    }

    /* Tooltip for action buttons */
    .tooltip {
      position: relative;
      display: inline-block;
    }

    .tooltip .tooltiptext {
      visibility: hidden;
      width: 120px;
      background-color: #333;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      left: 50%;
      transform: translateX(-50%);
      opacity: 0;
      transition: opacity 0.3s;
      font-size: 0.8rem;
    }

    .tooltip:hover .tooltiptext {
      visibility: visible;
      opacity: 1;
    }
  </style>
</head>
<body>
  <!-- Loading Spinner -->
  <div class="loading-spinner" id="loadingSpinner">
    <div class="spinner"></div>
  </div>

  <!-- Sidebar Navigation -->
  <div class="sidebar">
    <div class="logo">
      <i class="fas fa-store-alt"></i> <span>FarmVille</span>
    </div>
    <ul class="nav-links">
      <li><a href="storage.php"><i class="fas fa-tachometer-alt"></i> <span>Home</span></a></li>
      <li><a href="storage_units.php"><i class="fas fa-warehouse"></i> <span>Storage Units</span></a></li>
      <li><a href="add_stock.php"><i class="fas fa-boxes"></i> <span>Add Stock</span></a></li>
      <li><a href="product-movement.php"><i class="fas fa-exchange-alt"></i> <span>Product Movement</span></a></li>
      <li><a href="manage-storage.php" class="active"><i class="fas fa-tools"></i> <span>Manage Storage</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
      <div class="user-info">
        <span class="username">Admin</span>
        <div class="user-icon"><i class="fas fa-user-cog"></i></div>
      </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <h1><i class="fas fa-tools"></i> Manage Storage</h1>
    </div>

    <!-- Display success/error messages -->
    <?php if (!empty($success_message)): ?>
      <div class="management-form" style="background-color: #d4edda; color: #155724; margin-bottom: 20px;">
        <p><?php echo $success_message; ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
      <div class="management-form" style="background-color: #f8d7da; color: #721c24; margin-bottom: 20px;">
        <p><?php echo $error_message; ?></p>
      </div>
    <?php endif; ?>

    <!-- Add New Storage Form -->
    <div class="add-storage-form" id="addStorageForm" style="display: none;">
      <div class="form-header">
        <h2><i class="fas fa-plus-circle"></i> Add New Storage Unit</h2>
      </div>
      <form id="newStorageForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="hidden" name="form_type" value="add">
        <div class="form-grid">
          <div class="form-group">
            <label for="new_storage_id">Storage ID</label>
            <input type="text" id="new_storage_id" name="storage_id" required>
          </div>
          <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" required>
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Storage Unit</button>
        </div>
      </form>
    </div>

    <!-- Current Storage Units -->
    <h2 style="color: #0d47a1; margin-bottom: 1rem; display: flex; align-items: center;">
      <i class="fas fa-warehouse"></i> Current Storage Units
      <button class="btn btn-primary" style="margin-left: auto;" onclick="showAddForm()">
        <i class="fas fa-plus"></i> Add New Storage
      </button>
    </h2>
    <table class="management-table">
      <thead>
        <tr>
          <th>Storage ID</th>
          <th>Product Name</th>
          <th>Type</th>
          <th>Quantity (kg)</th>
          <th>Location</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
    <?php foreach ($storage_units as $unit): 
        // Determine status based on quantity
        $status_class = 'status-normal';
        $status_text = 'Good';
        
        if ($unit['quantity'] <= 0) {
            $status_class = 'status-critical';
            $status_text = 'Empty';
        } elseif ($unit['quantity'] >= 23000) { // Close to 25000 (within 2000)
            $status_class = 'status-critical';
            $status_text = 'Critical';
        } elseif ($unit['quantity'] >= 20000) { // Getting full (within 5000)
            $status_class = 'status-warning';
            $status_text = 'Warning';
        }
    ?>
          <tr>
            <td><?php echo htmlspecialchars($unit['storage_id']); ?></td>
            <td><?php echo htmlspecialchars($unit['product_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($unit['type'] ?? 'N/A'); ?></td>
            <td><?php echo number_format($unit['quantity'] ?? 0, 2); ?></td>
            <td><?php echo htmlspecialchars($unit['location']); ?></td>
            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
            <td>
              <button class="action-btn btn-edit" onclick="showEditModal('<?php echo $unit['storage_id']; ?>')">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="action-btn btn-delete" onclick="deleteUnit('<?php echo $unit['storage_id']; ?>')">
                <i class="fas fa-trash"></i> Delete
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Edit Storage Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2><i class="fas fa-edit"></i> Edit Storage Unit</h2>
      <form id="editStorageForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="hidden" name="form_type" value="edit">
        <input type="hidden" id="edit_id" name="edit_id" value="">
        <div class="form-grid">
          <div class="form-group">
            <label for="edit_storage_id">Storage ID</label>
            <input type="text" id="edit_storage_id" name="storage_id" readonly>
          </div>
          <div class="form-group">
            <label for="edit_product_id">Product ID</label>
            <input type="text" id="edit_product_id" name="product_id">
          </div>
          <div class="form-group">
            <label for="edit_product_name">Product Name</label>
            <input type="text" id="edit_product_name" name="product_name">
          </div>
          <div class="form-group">
            <label for="edit_type">Type</label>
            <input type="text" id="edit_type" name="type">
          </div>
          <div class="form-group">
            <label for="edit_quantity">Quantity (kg)</label>
            <input type="number" id="edit_quantity" name="quantity" step="0.01" min="0">
          </div>
          <div class="form-group">
            <label for="edit_location">Location</label>
            <input type="text" id="edit_location" name="location" required>
          </div>
          <div class="form-group" style="grid-column: 1 / -1">
            <label for="edit_notes">Notes</label>
            <textarea id="edit_notes" name="notes" rows="3"></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
      <p>Are you sure you want to delete this storage unit? This action cannot be undone.</p>
      <div class="form-actions">
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>

  <script>
    // Show loading spinner
    function showLoading() {
      document.getElementById('loadingSpinner').style.display = 'flex';
    }

    // Hide loading spinner
    function hideLoading() {
      document.getElementById('loadingSpinner').style.display = 'none';
    }

    // Show add new storage form
    function showAddForm() {
      document.getElementById('addStorageForm').style.display = 'block';
      document.getElementById('new_storage_id').value = 'STO-' + Math.random().toString(36).substr(2, 8).toUpperCase();
      document.getElementById('location').focus();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Hide add new storage form
    function hideAddForm() {
      document.getElementById('addStorageForm').style.display = 'none';
    }

    // Show edit modal with storage unit data
    function showEditModal(storageId) {
      showLoading();
      
      // In a real application, you would fetch this data via AJAX
      // For this example, we'll simulate getting data from the table
      setTimeout(() => {
        document.getElementById('edit_id').value = storageId;
        document.getElementById('edit_storage_id').value = storageId;
        
        // Find the row in the table with this storage ID
        const rows = document.querySelectorAll('.management-table tbody tr');
        for (const row of rows) {
          if (row.cells[0].textContent === storageId) {
            document.getElementById('edit_product_id').value = '';
            document.getElementById('edit_product_name').value = row.cells[1].textContent;
            document.getElementById('edit_type').value = row.cells[2].textContent;
            document.getElementById('edit_quantity').value = parseFloat(row.cells[3].textContent.replace(',', ''));
            document.getElementById('edit_location').value = row.cells[4].textContent;
            break;
          }
        }
        
        document.getElementById('editModal').style.display = 'block';
        hideLoading();
      }, 500);
    }

    // Close edit modal
    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    // Delete unit confirmation
    let unitToDelete = null;
    
    function deleteUnit(storageId) {
      unitToDelete = storageId;
      document.getElementById('deleteModal').style.display = 'block';
    }

    // Confirm deletion
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
      if (unitToDelete) {
        showLoading();
        window.location.href = `manage-storage.php?delete_id=${unitToDelete}`;
      }
    });

    // Close modal
    function closeModal() {
      document.getElementById('deleteModal').style.display = 'none';
      unitToDelete = null;
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target == document.getElementById('deleteModal')) {
        closeModal();
      }
      if (event.target == document.getElementById('editModal')) {
        closeEditModal();
      }
    });

    // Form validation
    document.getElementById('newStorageForm').addEventListener('submit', function() {
      showLoading();
    });

    document.getElementById('editStorageForm').addEventListener('submit', function() {
      showLoading();
    });

    // Generate a random storage ID when page loads
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('new_storage_id').value = 'STO-' + Math.random().toString(36).substr(2, 8).toUpperCase();
    });
  </script>
</body>
</html>