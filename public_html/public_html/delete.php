<?php
include 'DB.php';

// Lấy thời gian hiện tại
$now = date("Y-m-d H:i:s");

// Nếu expired_date lưu đúng kiểu DATETIME thì dùng query này:
$sql = "DELETE FROM `users` 
        WHERE `expired_date` IS NOT NULL 
        AND `expired_date` < ?";

$stmt = $con->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $con->error . "\n");
}

$stmt->bind_param("s", $now);
$stmt->execute();

// Lấy số hàng bị xoá
$deleted = $con->affected_rows;

echo "Cleanup done at $now, deleted rows: $deleted\n";

$stmt->close();
$con->close();
?>
