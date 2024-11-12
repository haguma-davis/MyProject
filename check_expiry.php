<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "expiry_detector";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Placeholder function to simulate QR code processing
function getExpiryDateFromQRCode($file) {
    return '2024-12-01'; // example date (YYYY-MM-DD)
}

$statusMessage = "";
$statusClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["qr_code"])) {
    $product_name = "tomato sauce";
    $currentDate = date("Y-m-d");

    $stmt = $conn->prepare("SELECT expiry_date FROM products WHERE product_name = ?");
    if ($stmt === false) {
        die("Statement preparation failed: " . $conn->error);
    }

    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $stmt->bind_result($expiryDate);
    $stmt->fetch();
    $stmt->close();

    if (!$expiryDate) {
        $expiryDate = getExpiryDateFromQRCode($_FILES["qr_code"]["tmp_name"]);

        $stmt = $conn->prepare("INSERT INTO products (product_name, expiry_date, scan_date, status) VALUES (?, ?, NOW(), ?)");
        if ($stmt === false) {
            die("Statement preparation failed: " . $conn->error);
        }
        
        $status = ($currentDate <= $expiryDate) ? "Still Fresh" : "Not Fresh";
        $stmt->bind_param("sss", $product_name, $expiryDate, $status);
        $stmt->execute();
        $stmt->close();
    } else {
        $status = ($currentDate <= $expiryDate) ? "Still Fresh" : "Not Fresh";
    }

    $statusMessage = ($status == "Still Fresh") ? "Still Fresh" : "Not Fresh";
    $statusClass = ($status == "Still Fresh") ? "fresh" : "expired";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Status</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Expiry Status</h1>
        <p class="<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusMessage); ?></p>
        <a href="index.html"><button>Scan Another QR Code</button></a>
    </div>
</body>
</html>
