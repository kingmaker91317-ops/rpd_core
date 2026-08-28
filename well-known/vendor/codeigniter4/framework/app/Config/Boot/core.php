<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload & Delete</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .upload-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="file"] {
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            width: 100%;
        }
        input[type="submit"] {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .delete-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 5px;
        }
        .delete-btn:hover {
            background: #c82333;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .file-list {
            margin-top: 30px;
        }
        .file-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            border: 1px solid #ddd;
        }
        .file-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        .file-actions {
            margin-top: 10px;
        }
        .php-file {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        .danger-zone {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>File Upload & Delete System</h1>
        <div class="warning">
            <strong>⚠ EXTREME SECURITY WARNING:</strong> This uploader accepts ALL file types including .php files and uploads to the current directory. No security restrictions are applied. Uploaded PHP files can be executed and DELETED.
        </div>
        
        <?php
        // Handle file upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // File upload handling
            if (isset($_FILES['file'])) {
                $fileName = $_FILES['file']['name'];
                $fileTmpName = $_FILES['file']['tmp_name'];
                $fileSize = $_FILES['file']['size'];
                $fileError = $_FILES['file']['error'];
                
                if ($fileError === UPLOAD_ERR_OK) {
                    // Upload to current directory
                    $destination = $fileName;
                    
                    if (move_uploaded_file($fileTmpName, $destination)) {
                        echo '<div class="message success">';
                        echo "File uploaded successfully: " . htmlspecialchars($fileName);
                        echo "<br>Size: " . number_format($fileSize) . " bytes";
                        echo "<br>Location: " . htmlspecialchars($destination) . " (same directory as this script)";
                        echo '</div>';
                        
                        // Show warning for PHP files
                        if (pathinfo($fileName, PATHINFO_EXTENSION) === 'php') {
                            echo '<div class="message warning">';
                            echo "<strong>PHP File Uploaded:</strong> This PHP file can now be executed directly by accessing: " . htmlspecialchars($fileName);
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="message error">Error moving uploaded file.</div>';
                    }
                } else {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
                        UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'PHP extension stopped the file upload'
                    ];
                    
                    $errorMessage = $errorMessages[$fileError] ?? 'Unknown upload error';
                    echo '<div class="message error">Upload error: ' . $errorMessage . '</div>';
                }
            }
            
            // File deletion handling
            if (isset($_POST['delete_file'])) {
                $fileToDelete = $_POST['delete_file'];
                
                // Security check: prevent deleting this script itself
                $currentScript = basename($_SERVER['PHP_SELF']);
                if ($fileToDelete === $currentScript) {
                    echo '<div class="message error">Cannot delete this script file.</div>';
                } elseif (file_exists($fileToDelete)) {
                    if (unlink($fileToDelete)) {
                        echo '<div class="message success">File deleted successfully: ' . htmlspecialchars($fileToDelete) . '</div>';
                    } else {
                        echo '<div class="message error">Error deleting file: ' . htmlspecialchars($fileToDelete) . '</div>';
                    }
                } else {
                    echo '<div class="message error">File not found: ' . htmlspecialchars($fileToDelete) . '</div>';
                }
            }
            
            // Bulk delete handling
            if (isset($_POST['delete_selected']) && isset($_POST['files_to_delete'])) {
                $deletedCount = 0;
                $errorCount = 0;
                $currentScript = basename($_SERVER['PHP_SELF']);
                
                foreach ($_POST['files_to_delete'] as $fileToDelete) {
                    // Prevent deleting this script
                    if ($fileToDelete === $currentScript) {
                        echo '<div class="message error">Cannot delete this script file: ' . htmlspecialchars($fileToDelete) . '</div>';
                        $errorCount++;
                        continue;
                    }
                    
                    if (file_exists($fileToDelete)) {
                        if (unlink($fileToDelete)) {
                            $deletedCount++;
                        } else {
                            echo '<div class="message error">Error deleting file: ' . htmlspecialchars($fileToDelete) . '</div>';
                            $errorCount++;
                        }
                    } else {
                        echo '<div class="message error">File not found: ' . htmlspecialchars($fileToDelete) . '</div>';
                        $errorCount++;
                    }
                }
                
                if ($deletedCount > 0) {
                    echo '<div class="message success">Successfully deleted ' . $deletedCount . ' file(s).</div>';
                }
                if ($errorCount > 0) {
                    echo '<div class="message error">Failed to delete ' . $errorCount . ' file(s).</div>';
                }
            }
        }
        ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="file"><strong>Upload New File (any type allowed):</strong></label>
                <input type="file" name="file" id="file" required>
            </div>
            <div class="form-group">
                <input type="submit" value="Upload File" name="submit">
            </div>
        </form>
        
        <?php
        // Display files in current directory (excluding this script)
        $currentDir = '.';
        $files = scandir($currentDir);
        $files = array_diff($files, array('.', '..'));
        
        // Filter out the current script file from the list
        $currentScript = basename($_SERVER['PHP_SELF']);
        $files = array_filter($files, function($file) use ($currentScript) {
            return $file !== $currentScript && is_file($file);
        });
        
        if (count($files) > 0) {
            echo '<div class="file-list">';
            echo '<h3>Files in Current Directory (excluding this script):</h3>';
            echo '<form action="" method="post" id="bulkDeleteForm">';
            
            foreach ($files as $file) {
                $fileSize = filesize($file);
                $fileType = mime_content_type($file);
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                $isPhpFile = ($fileExtension === 'php');
                
                echo '<div class="file-item ' . ($isPhpFile ? 'php-file' : '') . '">';
                echo '<div class="file-header">';
                echo '<strong>File:</strong> ' . htmlspecialchars($file);
                echo '<div>';
                echo '<input type="checkbox" name="files_to_delete[]" value="' . htmlspecialchars($file) . '" style="margin-right: 10px;">';
                echo '<label>Select for deletion</label>';
                echo '</div>';
                echo '</div>';
                echo '<strong>Size:</strong> ' . number_format($fileSize) . ' bytes<br>';
                echo '<strong>Type:</strong> ' . htmlspecialchars($fileType) . '<br>';
                echo '<strong>Extension:</strong> ' . htmlspecialchars($fileExtension);
                
                // Show direct link for non-PHP files
                if (!$isPhpFile) {
                    echo '<br><strong>Direct Link:</strong> <a href="' . htmlspecialchars($file) . '" target="_blank">' . htmlspecialchars($file) . '</a>';
                } else {
                    echo '<br><strong>PHP File:</strong> Can be executed directly';
                    echo '<br><strong>Execute URL:</strong> <a href="' . htmlspecialchars($file) . '" target="_blank">' . htmlspecialchars($file) . '</a>';
                }
                
                echo '<div class="file-actions">';
                echo '<button type="submit" name="delete_file" value="' . htmlspecialchars($file) . '" class="delete-btn" onclick="return confirm(\'Are you sure you want to delete ' . htmlspecialchars($file) . '?\')">Delete This File</button>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '<div class="danger-zone">';
            echo '<h3>🚨 Bulk Delete</h3>';
            echo '<p>Select multiple files using checkboxes and delete them all at once:</p>';
            echo '<button type="submit" name="delete_selected" class="delete-btn" onclick="return confirm(\'Are you sure you want to delete all selected files? This action cannot be undone.\')">Delete Selected Files</button>';
            echo '</div>';
            
            echo '</form>';
            echo '</div>';
        } else {
            echo '<div class="file-list">';
            echo '<h3>No other files found in current directory.</h3>';
            echo '</div>';
        }
        ?>
        
        <div class="danger-zone">
            <h3>🚨 Extreme Danger Zone</h3>
            <p><strong>Warning:</strong> This system allows:</p>
            <ul>
                <li>Uploading ANY file type including executable PHP files</li>
                <li>Deleting ANY file in the current directory</li>
                <li>No authentication or security checks</li>
                <li>Potential for complete server compromise</li>
            </ul>
            <p><strong>Use at your own risk in isolated environments only!</strong></p>
        </div>
    </div>

    <script>
        // JavaScript for bulk selection
        document.addEventListener('DOMContentLoaded', function() {
            const bulkForm = document.getElementById('bulkDeleteForm');
            const checkboxes = bulkForm.querySelectorAll('input[type="checkbox"]');
            
            // Add select all functionality
            const selectAllBtn = document.createElement('button');
            selectAllBtn.type = 'button';
            selectAllBtn.textContent = 'Select All';
            selectAllBtn.className = 'delete-btn';
            selectAllBtn.style.marginRight = '10px';
            selectAllBtn.style.background = '#6c757d';
            
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
            
            const deselectAllBtn = document.createElement('button');
            deselectAllBtn.type = 'button';
            deselectAllBtn.textContent = 'Deselect All';
            deselectAllBtn.className = 'delete-btn';
            deselectAllBtn.style.background = '#6c757d';
            
            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
            
            const bulkDeleteSection = bulkForm.querySelector('.danger-zone');
            bulkDeleteSection.insertBefore(deselectAllBtn, bulkDeleteSection.querySelector('button'));
            bulkDeleteSection.insertBefore(selectAllBtn, deselectAllBtn);
        });
    </script>
</body>
</html>