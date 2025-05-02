<?php
// Database connection parameters
$host = "localhost";
$dbname = "farm_management";
$username = "root";
$password = ""; // Set your database password here

// Create database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle form submission for adding a new recommendation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = sanitize($_POST["title"]);
    $category = sanitize($_POST["category"]);
    $content = sanitize($_POST["content"]);
    $expiry = !empty($_POST["expiry"]) ? $_POST["expiry"] : NULL;
    
    // Handle target farmers (convert array to string if needed)
    $target_farmers = isset($_POST["target_farmers"]) ? implode(",", $_POST["target_farmers"]) : "all";
    
    // Get current date and time
    $created_at = date("Y-m-d H:i:s");
    
    try {
        // Check if we're updating an existing recommendation
        if (isset($_POST["recommendation_id"]) && !empty($_POST["recommendation_id"])) {
            // Update existing recommendation
            $id = sanitize($_POST["recommendation_id"]);
            
            $sql = "UPDATE recommendations 
                    SET title = :title, 
                        category = :category, 
                        content = :content, 
                        target_farmers = :target_farmers, 
                        expiry_date = :expiry,
                        updated_at = :updated_at
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":target_farmers", $target_farmers);
            $stmt->bindParam(":expiry", $expiry);
            $stmt->bindParam(":updated_at", $created_at);
            
            $stmt->execute();
            
            // Redirect back to the recommendations page with success message
            header("Location: Recommendations.html?status=updated");
            exit();
        } else {
            // Insert new recommendation
            $sql = "INSERT INTO recommendations (title, category, content, target_farmers, expiry_date, created_at, updated_at)
                    VALUES (:title, :category, :content, :target_farmers, :expiry, :created_at, :updated_at)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":target_farmers", $target_farmers);
            $stmt->bindParam(":expiry", $expiry);
            $stmt->bindParam(":created_at", $created_at);
            $stmt->bindParam(":updated_at", $created_at);
            
            $stmt->execute();
            
            // Redirect back to the recommendations page with success message
            header("Location: Recommendations.html?status=added");
            exit();
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle recommendation deletion if requested
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["action"]) && $_GET["action"] == "delete") {
    if (isset($_GET["id"]) && !empty($_GET["id"])) {
        $id = sanitize($_GET["id"]);
        
        try {
            $sql = "DELETE FROM recommendations WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            // Redirect back to the recommendations page with success message
            header("Location: Recommendations.html?status=deleted");
            exit();
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

// Handle fetching recommendations data for AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["action"]) && $_GET["action"] == "fetch") {
    try {
        // Build query based on filters
        $sql = "SELECT * FROM recommendations WHERE 1=1";
        
        // Apply category filter
        if (isset($_GET["category"]) && !empty($_GET["category"])) {
            $category = sanitize($_GET["category"]);
            $sql .= " AND category = :category";
        }
        
        // Apply search filter
        if (isset($_GET["search"]) && !empty($_GET["search"])) {
            $search = sanitize($_GET["search"]);
            $sql .= " AND (title LIKE :search OR content LIKE :search)";
        }
        
        // Order by created date, newest first
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters if they exist
        if (isset($category)) {
            $stmt->bindParam(":category", $category);
        }
        
        if (isset($search)) {
            $search = "%$search%";
            $stmt->bindParam(":search", $search);
        }
        
        $stmt->execute();
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return data as JSON
        header('Content-Type: application/json');
        echo json_encode($recommendations);
        exit();
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(["error" => $e->getMessage()]);
        exit();
    }
}

// Get single recommendation data for editing
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["action"]) && $_GET["action"] == "get" && isset($_GET["id"])) {
    $id = sanitize($_GET["id"]);
    
    try {
        $sql = "SELECT * FROM recommendations WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        $recommendation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($recommendation) {
            // Return data as JSON
            header('Content-Type: application/json');
            echo json_encode($recommendation);
        } else {
            header('Content-Type: application/json');
            echo json_encode(["error" => "Recommendation not found"]);
        }
        exit();
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(["error" => $e->getMessage()]);
        exit();
    }
}

// Create the recommendations table if it doesn't exist
function createRecommendationsTable($conn) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS recommendations (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            content TEXT NOT NULL,
            target_farmers VARCHAR(255) DEFAULT 'all',
            expiry_date DATE NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX (category),
            INDEX (created_at)
        )";
        
        $conn->exec($sql);
        return true;
    } catch(PDOException $e) {
        echo "Error creating table: " . $e->getMessage();
        return false;
    }
}

// Ensure the recommendations table exists
createRecommendationsTable($conn);
?>