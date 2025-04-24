<?php
include "db.php";
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: Login_Page.html");
    exit;
}

// Database connection
$host = "localhost";
$username = "db_username";
$password = "db_password";
$dbname = "farm_management";

// Create database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$reports = [];
$message = "";
$error = "";

// Handle report generation form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
    // Get form data
    $report_type = $_POST['report_type'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $product_type = $_POST['product_type'] ?? '';
    $region = $_POST['region'] ?? '';
    $format = $_POST['format'] ?? 'pdf';
    
    // Validate inputs
    if (empty($report_type)) {
        $error = "Please select a report type";
    } else {
        // Generate report name
        $report_name = generateReportName($report_type);
        
        // Insert report record into database
        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO reports (report_name, report_type, date_from, date_to, product_type, region, format, user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $report_name, $report_type, $date_from, $date_to, $product_type, $region, $format, $user_id);
        
        if ($stmt->execute()) {
            $report_id = $stmt->insert_id;
            $message = "Report generated successfully!";
            
            // Call appropriate report generation function based on type
            switch($report_type) {
                case 'production':
                    generateProductionReport($report_id, $date_from, $date_to, $product_type, $region, $format);
                    break;
                case 'farmer':
                    generateFarmerReport($report_id, $date_from, $date_to, $product_type, $region, $format);
                    break;
                case 'market':
                    generateMarketReport($report_id, $date_from, $date_to, $product_type, $region, $format);
                    break;
                case 'inventory':
                    generateInventoryReport($report_id, $date_from, $date_to, $product_type, $region, $format);
                    break;
                case 'sales':
                    generateSalesReport($report_id, $date_from, $date_to, $product_type, $region, $format);
                    break;
                default:
                    $error = "Invalid report type selected";
            }
        } else {
            $error = "Error generating report: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Handle report deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $report_id = $_GET['id'];
    
    // Delete the report file
    deleteReportFile($report_id);
    
    // Delete the report record
    $sql = "DELETE FROM reports WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    
    if ($stmt->execute()) {
        $message = "Report deleted successfully!";
    } else {
        $error = "Error deleting report: " . $stmt->error;
    }
    
    $stmt->close();
}

// Get recent reports
$sql = "SELECT r.id, r.report_name, r.report_type, r.created_at, r.format, u.username 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Function to generate a unique report name
function generateReportName($type) {
    $prefix = '';
    
    switch($type) {
        case 'production':
            $prefix = 'PROD';
            break;
        case 'farmer':
            $prefix = 'FARM';
            break;
        case 'market':
            $prefix = 'MKT';
            break;
        case 'inventory':
            $prefix = 'INV';
            break;
        case 'sales':
            $prefix = 'SALES';
            break;
        default:
            $prefix = 'RPT';
    }
    
    return $prefix . '_' . date('Ymd') . '_' . substr(md5(uniqid()), 0, 6);
}

// Functions to generate different types of reports
function generateProductionReport($report_id, $date_from, $date_to, $product_type, $region, $format) {
    // Logic to query production data and generate report
    // This would typically involve:
    // 1. Querying the database for relevant production data
    // 2. Processing the data
    // 3. Creating a file in the requested format (PDF, Excel, CSV)
    // 4. Saving the file to a specific location
    // 5. Updating the report record with the file path
    
    // Example implementation (simplified)
    $report_path = "reports/production_" . $report_id . "." . $format;
    
    // Update report record with file path
    global $conn;
    $sql = "UPDATE reports SET file_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $report_path, $report_id);
    $stmt->execute();
    $stmt->close();
}

function generateFarmerReport($report_id, $date_from, $date_to, $product_type, $region, $format) {
    // Similar to production report but with farmer-specific data
    $report_path = "reports/farmer_" . $report_id . "." . $format;
    
    global $conn;
    $sql = "UPDATE reports SET file_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $report_path, $report_id);
    $stmt->execute();
    $stmt->close();
}

function generateMarketReport($report_id, $date_from, $date_to, $product_type, $region, $format) {
    // Market price trends report generation
    $report_path = "reports/market_" . $report_id . "." . $format;
    
    global $conn;
    $sql = "UPDATE reports SET file_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $report_path, $report_id);
    $stmt->execute();
    $stmt->close();
}

function generateInventoryReport($report_id, $date_from, $date_to, $product_type, $region, $format) {
    // Inventory status report generation
    $report_path = "reports/inventory_" . $report_id . "." . $format;
    
    global $conn;
    $sql = "UPDATE reports SET file_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $report_path, $report_id);
    $stmt->execute();
    $stmt->close();
}

function generateSalesReport($report_id, $date_from, $date_to, $product_type, $region, $format) {
    // Sales overview report generation
    $report_path = "reports/sales_" . $report_id . "." . $format;
    
    global $conn;
    $sql = "UPDATE reports SET file_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $report_path, $report_id);
    $stmt->execute();
    $stmt->close();
}

// Function to delete report file
function deleteReportFile($report_id) {
    global $conn;
    
    // Get file path
    $sql = "SELECT file_path FROM reports WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $file_path = $row['file_path'];
        
        // Delete file if it exists
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt->close();
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports Dashboard</title>
  <!-- CSS is included in the separate HTML file -->
</head>
<body>
  <!-- This content will be replaced by the HTML file -->
  <?php if (!empty($message)): ?>
    <script>
      alert("<?php echo $message; ?>");
      window.location.href = "reports.php";
    </script>
  <?php endif; ?>
  
  <?php if (!empty($error)): ?>
    <script>
      alert("Error: <?php echo $error; ?>");
    </script>
  <?php endif; ?>
  
  <script>
    // This script will populate the reports table with data from PHP
    document.addEventListener("DOMContentLoaded", function() {
      const reportsTable = document.getElementById("reports-table").getElementsByTagName('tbody')[0];
      
      <?php foreach ($reports as $report): ?>
        const row = reportsTable.insertRow();
        
        // Report Name
        const cellName = row.insertCell();
        cellName.textContent = "<?php echo $report['report_name']; ?>";
        
        // Report Type
        const cellType = row.insertCell();
        cellType.textContent = "<?php echo ucfirst($report['report_type']); ?>";
        
        // Date Generated
        const cellDate = row.insertCell();
        cellDate.textContent = "<?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?>";
        
        // Generated By
        const cellUser = row.insertCell();
        cellUser.textContent = "<?php echo $report['username']; ?>";
        
        // Format
        const cellFormat = row.insertCell();
        cellFormat.textContent = "<?php echo strtoupper($report['format']); ?>";
        
        // Actions
        const cellActions = row.insertCell();
        cellActions.className = "action-buttons";
        cellActions.innerHTML = `
          <button class="action-btn view-btn" onclick="viewReport(<?php echo $report['id']; ?>)">View</button>
          <button class="action-btn download-btn" onclick="downloadReport(<?php echo $report['id']; ?>)">Download</button>
          <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $report['id']; ?>)">Delete</button>
        `;
      <?php endforeach; ?>
    });
    
    function viewReport(id) {
      window.open('view_report.php?id=' + id, '_blank');
    }
    
    function downloadReport(id) {
      window.location.href = 'download_report.php?id=' + id;
    }
    
    function confirmDelete(id) {
      if (confirm('Are you sure you want to delete this report?')) {
        window.location.href = 'reports.php?action=delete&id=' + id;
      }
    }
  </script>
</body>
</html>