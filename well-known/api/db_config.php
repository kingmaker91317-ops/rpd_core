<?php
// Define database connection constants
define('DB_SERVER', 'localhost'); // Your database server, usually 'localhost'
define('DB_USERNAME', 'ffdetect_main');    // Your database username
define('DB_PASSWORD', 'ffdetect_main'); // Replace with the actual password    // Your database password
define('DB_NAME', 'ffdetect_main'); // Your database name

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error securely and show a generic message to the user
    error_log("Connection failed: " . $conn->connect_error);
    die("ERROR: Could not connect to the database.");
}

// Function to safely close the connection
function close_db_connection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
