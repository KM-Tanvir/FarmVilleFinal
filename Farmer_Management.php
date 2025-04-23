<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "agriculture_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$error_message = "";
$success_message = "";

// Process form submission for adding/editing farmers
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if we're adding a new farmer or editing an existing one
    $farmer_id = isset($_POST['farmer_id']) && !empty($_POST['farmer_id']) ? intval($_POST['farmer_id']) : null;
    
    // Sanitize and validate inputs
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $farm_name = htmlspecialchars(trim($_POST['farm_name']));
    $farm_type = htmlspecialchars(trim($_POST['farm_type']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $location = htmlspecialchars(trim($_POST['location']));
    $farm_size = is_numeric($_POST['farm_size']) ? floatval($_POST['farm_size']) : 0;
    $status = htmlspecialchars(trim($_POST['status']));
    $registration_date = empty($_POST['registration_date']) ? date('Y-m-d') : $_POST['registration_date'];
    $notes = htmlspecialchars(trim($_POST['notes']));
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        if ($farmer_id) {
            // Update existing farmer
            $stmt = $conn->prepare("UPDATE farmers SET 
                first_name = ?, 
                last_name = ?, 
                farm_name = ?, 
                farm_type = ?, 
                email = ?, 
                phone = ?, 
                location = ?, 
                farm_size = ?, 
                status = ?, 
                registration_date = ?, 
                notes = ? 
                WHERE id = ?");
            
            $stmt->bind_param("sssssssdsssi", 
                $first_name, 
                $last_name, 
                $farm_name, 
                $farm_type, 
                $email, 
                $phone, 
                $location, 
                $farm_size, 
                $status, 
                $registration_date, 
                $notes, 
                $farmer_id
            );
            
            if ($stmt->execute()) {
                $success_message = "Farmer updated successfully";
            } else {
                $error_message = "Error updating farmer: " . $conn->error;
            }
        } else {
            // Add new farmer
            $stmt = $conn->prepare("INSERT INTO farmers (
                first_name, 
                last_name, 
                farm_name, 
                farm_type, 
                email, 
                phone, 
                location, 
                farm_size, 
                status, 
                registration_date, 
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssdsss", 
                $first_name, 
                $last_name, 
                $farm_name, 
                $farm_type, 
                $email, 
                $phone, 
                $location, 
                $farm_size, 
                $status, 
                $registration_date, 
                $notes
            );
            
            if ($stmt->execute()) {
                $success_message = "New farmer added successfully";
            } else {
                $error_message = "Error adding farmer: " . $conn->error;
            }
        }
        
        $stmt->close();
    }
}

// Handle DELETE requests for deleting farmers
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $farmer_id = intval($_GET['id']);
    
    // Check if the farmer exists
    $check_stmt = $conn->prepare("SELECT id FROM farmers WHERE id = ?");
    $check_stmt->bind_param("i", $farmer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Delete the farmer
        $delete_stmt = $conn->prepare("DELETE FROM farmers WHERE id = ?");
        $delete_stmt->bind_param("i", $farmer_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Farmer deleted successfully";
        } else {
            $error_message = "Error deleting farmer: " . $conn->error;
        }
        
        $delete_stmt->close();
    } else {
        $error_message = "Farmer not found";
    }
    
    $check_stmt->close();
}

// Handle GET requests for fetching a specific farmer's details
if (isset($_GET['action']) && $_GET['action'] == 'get' && isset($_GET['id'])) {
    $farmer_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM farmers WHERE id = ?");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $farmer = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($farmer);
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['error' => 'Farmer not found']);
        exit;
    }
    
    $stmt->close();
}

// Fetch all farmers for display in the table
$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
$farm_type_filter = isset($_GET['farm_type']) ? $_GET['farm_type'] : "";
$status_filter = isset($_GET['status']) ? $_GET['status'] : "";

// Build the query based on filters
$query = "SELECT * FROM farmers WHERE 
          (first_name LIKE ? OR last_name LIKE ? OR farm_name LIKE ?)";
$params = [$search, $search, $search];
$types = "sss";

if (!empty($farm_type_filter)) {
    $query .= " AND farm_type = ?";
    $params[] = $farm_type_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add sorting and pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Items per page
$offset = ($page - 1) * $limit;

$query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM farmers WHERE 
               (first_name LIKE ? OR last_name LIKE ? OR farm_name LIKE ?)";
$count_params = [$search, $search, $search];
$count_types = "sss";

if (!empty($farm_type_filter)) {
    $count_query .= " AND farm_type = ?";
    $count_params[] = $farm_type_filter;
    $count_types .= "s";
}

if (!empty($status_filter)) {
    $count_query .= " AND status = ?";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get all farm types for the filter dropdown
$farm_types_query = "SELECT DISTINCT farm_type FROM farmers ORDER BY farm_type";
$farm_types_result = $conn->query($farm_types_query);
$farm_types = [];
while ($row = $farm_types_result->fetch_assoc()) {
    $farm_types[] = $row['farm_type'];
}

// Handle AJAX requests for data loading
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    $farmers = [];
    while ($row = $result->fetch_assoc()) {
        $farmers[] = $row;
    }
    
    $response = [
        'farmers' => $farmers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_rows' => $total_rows
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Create a SQL script to initialize the database table if it doesn't exist
function generateDatabaseScript() {
    return "
    CREATE TABLE IF NOT EXISTS farmers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        farm_name VARCHAR(100) NOT NULL,
        farm_type VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        location VARCHAR(100) NOT NULL,
        farm_size DECIMAL(10,2) NOT NULL,
        status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
        registration_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    -- Insert some sample data if the table is empty
    INSERT INTO farmers (first_name, last_name, farm_name, farm_type, email, phone, location, farm_size, status, registration_date, notes)
    SELECT * FROM (
        SELECT 'John', 'Doe', 'Green Valley Farm', 'Organic Vegetables', 'john.doe@example.com', '(555) 123-4567', 'Springfield, IL', 75.0, 'active', '2024-01-15', 'Specializes in organic tomatoes and peppers.'
    ) AS tmp
    WHERE NOT EXISTS (
        SELECT * FROM farmers
    ) LIMIT 1;
    
    INSERT INTO farmers (first_name, last_name, farm_name, farm_type, email, phone, location, farm_size, status, registration_date, notes)
    SELECT * FROM (
        SELECT 'Sarah', 'Smith', 'Sunny Fields', 'Mixed Crops', 'sarah.smith@example.com', '(555) 234-5678', 'Cedar Rapids, IA', 120.0, 'active', '2024-02-20', 'Practices sustainable farming methods.'
    ) AS tmp
    WHERE NOT EXISTS (
        SELECT * FROM farmers WHERE id > 1
    ) LIMIT 1;
    
    INSERT INTO farmers (first_name, last_name, farm_name, farm_type, email, phone, location, farm_size, status, registration_date, notes)
    SELECT * FROM (
        SELECT 'Miguel', 'Rodriguez', 'Rodriguez Family Farm', 'Fruit Orchard', 'miguel@example.com', '(555) 345-6789', 'Fresno, CA', 65.0, 'active', '2024-01-30', 'Family-owned for three generations.'
    ) AS tmp
    WHERE NOT EXISTS (
        SELECT * FROM farmers WHERE id > 2
    ) LIMIT 1;
    
    INSERT INTO farmers (first_name, last_name, farm_name, farm_type, email, phone, location, farm_size, status, registration_date, notes)
    SELECT * FROM (
        SELECT 'Emma', 'Johnson', 'Blue Sky Dairy', 'Dairy', 'emma.johnson@example.com', '(555) 456-7890', 'Madison, WI', 150.0, 'inactive', '2023-11-10', 'Produces organic dairy products.'
    ) AS tmp
    WHERE NOT EXISTS (
        SELECT * FROM farmers WHERE id > 3
    ) LIMIT 1;
    
    INSERT INTO farmers (first_name, last_name, farm_name, farm_type, email, phone, location, farm_size, status, registration_date, notes)
    SELECT * FROM (
        SELECT 'Robert', 'Williams', 'Williams Ranch', 'Livestock', 'robert@example.com', '(555) 567-8901', 'Austin, TX', 320.0, 'pending', '2024-03-05', 'Specializes in grass-fed beef.'
    ) AS tmp
    WHERE NOT EXISTS (
        SELECT * FROM farmers WHERE id > 4
    ) LIMIT 1;";
}

// Initialize database if requested
if (isset($_GET['action']) && $_GET['action'] == 'init_db') {
    $sql = generateDatabaseScript();
    if ($conn->multi_query($sql)) {
        do {
            // Store result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        $success_message = "Database initialized with sample data";
    } else {
        $error_message = "Error initializing database: " . $conn->error;
    }
}

// Add this script to include at the bottom of the HTML page to handle AJAX requests
$script = '
<script>
// Load farmers data via AJAX
function loadFarmers(page = 1) {
    const searchInput = document.getElementById("searchInput").value;
    const farmTypeFilter = document.getElementById("farmTypeFilter").value;
    const statusFilter = document.getElementById("statusFilter").value;
    
    fetch(`farmer_management.php?ajax=true&page=${page}&search=${searchInput}&farm_type=${farmTypeFilter}&status=${statusFilter}`)
        .then(response => response.json())
        .then(data => {
            // Update table rows
            const tableBody = document.querySelector("#farmersTable tbody");
            tableBody.innerHTML = "";
            
            data.farmers.forEach(farmer => {
                let statusClass = "";
                if (farmer.status === "active") statusClass = "active-status";
                else if (farmer.status === "inactive") statusClass = "inactive-status";
                else if (farmer.status === "pending") statusClass = "pending-status";
                
                tableBody.innerHTML += `
                    <tr>
                        <td>${farmer.id}</td>
                        <td>${farmer.first_name} ${farmer.last_name}</td>
                        <td>${farmer.farm_name}</td>
                        <td>${farmer.farm_type}</td>
                        <td>${farmer.location}</td>
                        <td>${farmer.farm_size}</td>
                        <td><span class="status-badge ${statusClass}">${farmer.status.charAt(0).toUpperCase() + farmer.status.slice(1)}</span></td>
                        <td class="action-buttons">
                            <button class="action-btn view-btn" onclick="viewFarmer(${farmer.id})">View</button>
                            <button class="action-btn edit-btn" onclick="editFarmer(${farmer.id})">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteFarmer(${farmer.id})">Delete</button>
                        </td>
                    </tr>
                `;
            });
            
            // Update pagination
            updatePagination(data.pagination);
        })
        .catch(error => console.error("Error loading farmers:", error));
}

// Update pagination buttons
function updatePagination(pagination) {
    const paginationElement = document.getElementById("pagination");
    paginationElement.innerHTML = "";
    
    // Previous button
    paginationElement.innerHTML += `
        <button onclick="loadFarmers(${pagination.current_page > 1 ? pagination.current_page - 1 : 1})" 
                ${pagination.current_page === 1 ? "disabled" : ""}>
            &laquo;
        </button>
    `;
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationElement.innerHTML += `
            <button class="${i === pagination.current_page ? "active" : ""}" 
                    onclick="loadFarmers(${i})">
                ${i}
            </button>
        `;
    }
    
    // Next button
    paginationElement.innerHTML += `
        <button onclick="loadFarmers(${pagination.current_page < pagination.total_pages ? pagination.current_page + 1 : pagination.total_pages})" 
                ${pagination.current_page === pagination.total_pages ? "disabled" : ""}>
            &raquo;
        </button>
    `;
}

// View farmer details
function viewFarmer(id) {
    fetch(`farmer_management.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(farmer => {
            const viewModal = document.getElementById("viewFarmerModal");
            const detailsDiv = document.getElementById("farmerDetails");
            
            let statusClass = "";
            if (farmer.status === "active") statusClass = "active-status";
            else if (farmer.status === "inactive") statusClass = "inactive-status";
            else if (farmer.status === "pending") statusClass = "pending-status";
            
            detailsDiv.innerHTML = `
                <div class="form-row">
                    <div class="form-col">
                        <h3>Personal Information</h3>
                        <p><strong>Name:</strong> ${farmer.first_name} ${farmer.last_name}</p>
                        <p><strong>Email:</strong> ${farmer.email}</p>
                        <p><strong>Phone:</strong> ${farmer.phone}</p>
                    </div>
                    <div class="form-col">
                        <h3>Farm Details</h3>
                        <p><strong>Farm Name:</strong> ${farmer.farm_name}</p>
                        <p><strong>Farm Type:</strong> ${farmer.farm_type}</p>
                        <p><strong>Size:</strong> ${farmer.farm_size} acres</p>
                        <p><strong>Location:</strong> ${farmer.location}</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <h3>Account Status</h3>
                        <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${farmer.status.charAt(0).toUpperCase() + farmer.status.slice(1)}</span></p>
                        <p><strong>Registration Date:</strong> ${farmer.registration_date}</p>
                    </div>
                    <div class="form-col">
                        <h3>Additional Notes</h3>
                        <p>${farmer.notes || "No additional notes"}</p>
                    </div>
                </div>
            `;
            
            // Store the farmer ID for the edit button
            document.getElementById("editFromViewBtn").setAttribute("data-id", farmer.id);
            
            viewModal.style.display = "block";
        })
        .catch(error => console.error("Error fetching farmer details:", error));
}

// Edit farmer
function editFarmer(id) {
    fetch(`farmer_management.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(farmer => {
            document.getElementById("modalTitle").textContent = "Edit Farmer";
            document.getElementById("farmerId").value = farmer.id;
            document.getElementById("firstName").value = farmer.first_name;
            document.getElementById("lastName").value = farmer.last_name;
            document.getElementById("farmName").value = farmer.farm_name;
            document.getElementById("farmType").value = farmer.farm_type;
            document.getElementById("email").value = farmer.email;
            document.getElementById("phone").value = farmer.phone;
            document.getElementById("location").value = farmer.location;
            document.getElementById("farmSize").value = farmer.farm_size;
            document.getElementById("status").value = farmer.status;
            document.getElementById("registrationDate").value = farmer.registration_date;
            document.getElementById("notes").value = farmer.notes;
            
            document.getElementById("farmerModal").style.display = "block";
        })
        .catch(error => console.error("Error fetching farmer for edit:", error));
}

// Delete farmer
function deleteFarmer(id) {
    if (confirm("Are you sure you want to delete this farmer? This action cannot be undone.")) {
        window.location.href = `farmer_management.php?action=delete&id=${id}`;
    }
}

// Initialize page
document.addEventListener("DOMContentLoaded", function() {
    // Load initial data
    loadFarmers();
    
    // Setup search and filter handlers
    document.getElementById("searchInput").addEventListener("input", function() {
        loadFarmers(1);
    });
    
    document.getElementById("farmTypeFilter").addEventListener("change", function() {
        loadFarmers(1);
    });
    
    document.getElementById("statusFilter").addEventListener("change", function() {
        loadFarmers(1);
    });
    
    // Handle edit from view modal
    document.getElementById("editFromViewBtn").addEventListener("click", function() {
        const farmerId = this.getAttribute("data-id");
        document.getElementById("viewFarmerModal").style.display = "none";
        editFarmer(farmerId);
    });
});
</script>
';

// Close database connection
$conn->close();

// Include the HTML code here or include a separate file
include 'farmer_management_view.php'; // You would create this file with the HTML code
?>