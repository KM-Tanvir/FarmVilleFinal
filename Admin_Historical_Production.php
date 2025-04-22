<?php
// historical_production.php - Backend for Historical Production Dashboard
// Database connection parameters
$host = "localhost";
$username = "agriculture_admin";
$password = "secure_password";
$database = "agriculture_db";

// Initialize response array
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Process based on request type
    $requestType = $_SERVER['REQUEST_METHOD'];
    
    switch ($requestType) {
        case 'GET':
            // Handle data retrieval requests
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'getProductionData':
                        $response = getProductionData($conn, $_GET);
                        break;
                    case 'getSummaryStats':
                        $response = getSummaryStats($conn, $_GET);
                        break;
                    case 'getChartData':
                        $response = getChartData($conn, $_GET);
                        break;
                    case 'getProductsList':
                        $response = getProductsList($conn);
                        break;
                    case 'getRegionsList':
                        $response = getRegionsList($conn);
                        break;
                    default:
                        $response['message'] = 'Invalid action specified';
                }
            } else {
                $response['message'] = 'No action specified';
            }
            break;
            
        case 'POST':
            // Handle export requests
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'exportCSV':
                        $response = exportCSV($conn, $_POST);
                        break;
                    case 'exportPDF':
                        $response = exportPDF($conn, $_POST);
                        break;
                    default:
                        $response['message'] = 'Invalid action specified';
                }
            } else {
                $response['message'] = 'No action specified';
            }
            break;
            
        default:
            $response['message'] = 'Invalid request method';
    }
} catch(PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

/**
 * Get production data based on filters
 * 
 * @param PDO $conn Database connection
 * @param array $params Filter parameters
 * @return array Response with production data
 */
function getProductionData($conn, $params) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        // Get filter parameters with defaults
        $product = isset($params['product']) ? $params['product'] : 'all';
        $region = isset($params['region']) ? $params['region'] : 'all';
        $fromYear = isset($params['fromYear']) ? intval($params['fromYear']) : 2020;
        $toYear = isset($params['toYear']) ? intval($params['toYear']) : 2024;
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $limit = isset($params['limit']) ? intval($params['limit']) : 10;
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Start building the query
        $query = "SELECT year, product_name, region_name, production_tons, yield_tons_per_acre, 
                   CASE 
                       WHEN prev_year_production IS NOT NULL 
                       THEN ((production_tons - prev_year_production) / prev_year_production * 100) 
                       ELSE NULL 
                   END AS growth_percentage
                   FROM (
                       SELECT 
                           p.year, 
                           pr.name AS product_name, 
                           r.name AS region_name, 
                           p.production_tons, 
                           p.yield_tons_per_acre,
                           LAG(p.production_tons) OVER (PARTITION BY p.product_id, p.region_id ORDER BY p.year) AS prev_year_production
                       FROM 
                           production p
                       JOIN 
                           products pr ON p.product_id = pr.id
                       JOIN 
                           regions r ON p.region_id = r.id
                       WHERE 
                           p.year BETWEEN :fromYear AND :toYear";
        
        // Add filters if not 'all'
        if ($product !== 'all') {
            $query .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $query .= " AND r.name = :region";
        }
        
        $query .= ") AS derived_table ORDER BY year DESC, product_name, region_name LIMIT :limit OFFSET :offset";
        
        // Prepare and execute query
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $stmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $stmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $stmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        // Fetch results
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM production p
                       JOIN products pr ON p.product_id = pr.id
                       JOIN regions r ON p.region_id = r.id
                       WHERE p.year BETWEEN :fromYear AND :toYear";
        
        if ($product !== 'all') {
            $countQuery .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $countQuery .= " AND r.name = :region";
        }
        
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $countStmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $countStmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $countStmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $countStmt->execute();
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Build response
        $response['success'] = true;
        $response['data'] = [
            'records' => $results,
            'pagination' => [
                'total' => $totalRecords,
                'current_page' => $page,
                'per_page' => $limit,
                'total_pages' => ceil($totalRecords / $limit)
            ]
        ];
        $response['message'] = 'Data retrieved successfully';
    } catch (PDOException $e) {
        $response['message'] = "Error retrieving production data: " . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get summary statistics
 * 
 * @param PDO $conn Database connection
 * @param array $params Filter parameters
 * @return array Response with summary statistics
 */
function getSummaryStats($conn, $params) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        // Get filter parameters with defaults
        $product = isset($params['product']) ? $params['product'] : 'all';
        $region = isset($params['region']) ? $params['region'] : 'all';
        $fromYear = isset($params['fromYear']) ? intval($params['fromYear']) : 2020;
        $toYear = isset($params['toYear']) ? intval($params['toYear']) : 2024;
        
        // Start building the query for average production
        $avgQuery = "SELECT AVG(p.production_tons) as avg_production
                     FROM production p
                     JOIN products pr ON p.product_id = pr.id
                     JOIN regions r ON p.region_id = r.id
                     WHERE p.year BETWEEN :fromYear AND :toYear";
        
        // Add filters if not 'all'
        if ($product !== 'all') {
            $avgQuery .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $avgQuery .= " AND r.name = :region";
        }
        
        // Prepare and execute average query
        $avgStmt = $conn->prepare($avgQuery);
        $avgStmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $avgStmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $avgStmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $avgStmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $avgStmt->execute();
        $avgProduction = $avgStmt->fetch(PDO::FETCH_ASSOC)['avg_production'];
        
        // Query for growth rate
        $growthQuery = "SELECT 
                          (POWER(MAX(production_tons) / MIN(first_year_production), 1/NULLIF(years_diff, 0)) - 1) * 100 AS annual_growth_rate
                        FROM (
                          SELECT 
                            MAX(p.production_tons) AS production_tons,
                            FIRST_VALUE(p.production_tons) OVER (ORDER BY p.year) AS first_year_production,
                            MAX(p.year) - MIN(p.year) AS years_diff
                          FROM 
                            production p
                          JOIN 
                            products pr ON p.product_id = pr.id
                          JOIN 
                            regions r ON p.region_id = r.id
                          WHERE 
                            p.year BETWEEN :fromYear AND :toYear";
        
        if ($product !== 'all') {
            $growthQuery .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $growthQuery .= " AND r.name = :region";
        }
        
        $growthQuery .= ") AS derived";
        
        // Prepare and execute growth query
        $growthStmt = $conn->prepare($growthQuery);
        $growthStmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $growthStmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $growthStmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $growthStmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $growthStmt->execute();
        $growthRate = $growthStmt->fetch(PDO::FETCH_ASSOC)['annual_growth_rate'];
        
        // Query for peak year
        $peakQuery = "SELECT p.year
                      FROM production p
                      JOIN products pr ON p.product_id = pr.id
                      JOIN regions r ON p.region_id = r.id
                      WHERE p.year BETWEEN :fromYear AND :toYear";
        
        if ($product !== 'all') {
            $peakQuery .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $peakQuery .= " AND r.name = :region";
        }
        
        $peakQuery .= " ORDER BY p.production_tons DESC LIMIT 1";
        
        // Prepare and execute peak year query
        $peakStmt = $conn->prepare($peakQuery);
        $peakStmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $peakStmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $peakStmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $peakStmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $peakStmt->execute();
        $peakYear = $peakStmt->fetch(PDO::FETCH_ASSOC)['year'];
        
        // Build response
        $response['success'] = true;
        $response['data'] = [
            'avg_production' => round($avgProduction, 2),
            'growth_rate' => round($growthRate, 2),
            'peak_year' => $peakYear
        ];
        $response['message'] = 'Summary statistics retrieved successfully';
    } catch (PDOException $e) {
        $response['message'] = "Error retrieving summary statistics: " . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get chart data
 * 
 * @param PDO $conn Database connection
 * @param array $params Filter parameters
 * @return array Response with chart data
 */
function getChartData($conn, $params) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        // Get filter parameters with defaults
        $product = isset($params['product']) ? $params['product'] : 'all';
        $region = isset($params['region']) ? $params['region'] : 'all';
        $fromYear = isset($params['fromYear']) ? intval($params['fromYear']) : 2020;
        $toYear = isset($params['toYear']) ? intval($params['toYear']) : 2024;
        
        // Base query
        $query = "SELECT 
                    p.year, 
                    pr.name AS product_name, 
                    r.name AS region_name, 
                    SUM(p.production_tons) AS production_tons
                  FROM 
                    production p
                  JOIN 
                    products pr ON p.product_id = pr.id
                  JOIN 
                    regions r ON p.region_id = r.id
                  WHERE 
                    p.year BETWEEN :fromYear AND :toYear";
        
        // Add filters if not 'all'
        if ($product !== 'all') {
            $query .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $query .= " AND r.name = :region";
        }
        
        $query .= " GROUP BY p.year, pr.name, r.name ORDER BY p.year, pr.name, r.name";
        
        // Prepare and execute query
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $stmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $stmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $stmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        // Fetch results
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process data for chart format
        $years = range($fromYear, $toYear);
        $chartData = [];
        
        // Initialize years array for labels
        $chartData['labels'] = $years;
        $chartData['datasets'] = [];
        
        // Group by product and region
        $groupedData = [];
        foreach ($results as $row) {
            $key = $row['product_name'] . ' (' . $row['region_name'] . ')';
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = array_fill_keys($years, null);
            }
            $groupedData[$key][$row['year']] = $row['production_tons'];
        }
        
        // Colors for datasets
        $colors = [
            'rgba(64, 136, 245, 1)',  // Blue
            'rgba(75, 192, 192, 1)',  // Teal
            'rgba(255, 159, 64, 1)',  // Orange
            'rgba(255, 99, 132, 1)',  // Red
            'rgba(153, 102, 255, 1)', // Purple
            'rgba(54, 162, 235, 1)',  // Light blue
            'rgba(255, 205, 86, 1)'   // Yellow
        ];
        
        // Convert to chart format
        $colorIndex = 0;
        foreach ($groupedData as $key => $values) {
            $dataset = [
                'label' => $key,
                'data' => array_values($values),
                'borderColor' => $colors[$colorIndex % count($colors)],
                'backgroundColor' => str_replace('1)', '0.2)', $colors[$colorIndex % count($colors)]),
                'borderWidth' => 2,
                'tension' => 0.3
            ];
            $chartData['datasets'][] = $dataset;
            $colorIndex++;
        }
        
        // Build response
        $response['success'] = true;
        $response['data'] = $chartData;
        $response['message'] = 'Chart data retrieved successfully';
    } catch (PDOException $e) {
        $response['message'] = "Error retrieving chart data: " . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get list of products
 * 
 * @param PDO $conn Database connection
 * @return array Response with products list
 */
function getProductsList($conn) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        $query = "SELECT id, name FROM products ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = $products;
        $response['message'] = 'Products list retrieved successfully';
    } catch (PDOException $e) {
        $response['message'] = "Error retrieving products list: " . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get list of regions
 * 
 * @param PDO $conn Database connection
 * @return array Response with regions list
 */
function getRegionsList($conn) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        $query = "SELECT id, name FROM regions ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = $regions;
        $response['message'] = 'Regions list retrieved successfully';
    } catch (PDOException $e) {
        $response['message'] = "Error retrieving regions list: " . $e->getMessage();
    }
    
    return $response;
}

/**
 * Export data to CSV
 * 
 * @param PDO $conn Database connection
 * @param array $params Filter parameters
 * @return array Response with file URL or error message
 */
function exportCSV($conn, $params) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        // Get filter parameters with defaults
        $product = isset($params['product']) ? $params['product'] : 'all';
        $region = isset($params['region']) ? $params['region'] : 'all';
        $fromYear = isset($params['fromYear']) ? intval($params['fromYear']) : 2020;
        $toYear = isset($params['toYear']) ? intval($params['toYear']) : 2024;
        
        // Query to get all data
        $query = "SELECT 
                    p.year, 
                    pr.name AS product_name, 
                    r.name AS region_name, 
                    p.production_tons, 
                    p.yield_tons_per_acre,
                    CASE 
                        WHEN lag_production IS NOT NULL 
                        THEN ((p.production_tons - lag_production) / lag_production * 100) 
                        ELSE NULL 
                    END AS growth_percentage
                  FROM 
                    production p
                  JOIN 
                    products pr ON p.product_id = pr.id
                  JOIN 
                    regions r ON p.region_id = r.id
                  LEFT JOIN (
                    SELECT 
                        p2.id,
                        LAG(p2.production_tons) OVER (PARTITION BY p2.product_id, p2.region_id ORDER BY p2.year) AS lag_production
                    FROM 
                        production p2
                  ) AS prev ON p.id = prev.id
                  WHERE 
                    p.year BETWEEN :fromYear AND :toYear";
        
        // Add filters if not 'all'
        if ($product !== 'all') {
            $query .= " AND pr.name = :product";
        }
        
        if ($region !== 'all') {
            $query .= " AND r.name = :region";
        }
        
        $query .= " ORDER BY p.year DESC, pr.name, r.name";
        
        // Prepare and execute query
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':fromYear', $fromYear, PDO::PARAM_INT);
        $stmt->bindParam(':toYear', $toYear, PDO::PARAM_INT);
        
        if ($product !== 'all') {
            $stmt->bindParam(':product', $product, PDO::PARAM_STR);
        }
        
        if ($region !== 'all') {
            $stmt->bindParam(':region', $region, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        // Fetch results
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create CSV file
        $filename = 'production_data_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = '../exports/' . $filename;
        
        // Make sure the directory exists
        if (!file_exists('../exports')) {
            mkdir('../exports', 0777, true);
        }
        
        // Create and write to CSV file
        $file = fopen($filepath, 'w');
        
        // Add headers
        fputcsv($file, ['Year', 'Product', 'Region', 'Production (tons)', 'Yield (tons/acre)', 'Growth (%)']);
        
        // Add data rows
        foreach ($results as $row) {
            $csvRow = [
                $row['year'],
                $row['product_name'],
                $row['region_name'],
                $row['production_tons'],
                $row['yield_tons_per_acre'],
                $row['growth_percentage'] !== null ? number_format($row['growth_percentage'], 2) . '%' : 'N/A'
            ];
            fputcsv($file, $csvRow);
        }
        
        fclose($file);
        
        // Build response
        $response['success'] = true;
        $response['data'] = [
            'filename' => $filename,
            'url' => '../exports/' . $filename
        ];
        $response['message'] = 'CSV file created successfully';
    } catch (PDOException $e) {
        $response['message'] = "Error creating CSV file: " . $e->getMessage();
    }
    
    return $response;
}

/**
 * Export data to PDF
 * 
 * @param PDO $conn Database connection
 * @param array $params Filter parameters
 * @return array Response with file URL or error message
 */
function exportPDF($conn, $params) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => ''
    ];
    
    try {
        // Get filter parameters with defaults
        $product = isset($params['product']) ? $params['product'] : 'all';
        $region = isset($params['region']) ? $params['region'] : 'all';
        $fromYear = isset($params['fromYear']) ? intval($params['fromYear']) : 2020;
        $toYear = isset($params['toYear']) ? intval($params['toYear']) : 2024;
        
        // This would typically use a PDF generation library like TCPDF, FPDF, or mPDF
        // For this example, we'll just return a success message
        
        // Check if required PDF library exists (this is a placeholder)
        if (!class_exists('TCPDF')) {
            throw new Exception('PDF generation library not found');
        }
        
        // Build response
        $response['success'] = true;
        $response['data'] = [
            'filename' => 'production_report_' . date('Y-m-d_H-i-s') . '.pdf',
            'url' => '../exports/production_report_' . date('Y-m-d_H-i-s') . '.pdf'
        ];
        $response['message'] = 'PDF file would be created here with a proper PDF library';
    } catch (Exception $e) {
        $response['message'] = "Error creating PDF file: " . $e->getMessage();
    }
    
    return $response;
}
?>