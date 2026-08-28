<?php
// create_keys.php
$keys = [];
for ($i = 1; $i <= 31; $i++) {
    $keys[] = bin2hex(random_bytes(16)); // 32 ký tự hex
}

file_put_contents('keyenc.txt', implode("\n", $keys));
echo "Created keyenc.txt with 31 keys";
?>