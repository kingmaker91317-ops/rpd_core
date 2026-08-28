<?php
require_once 'db_config.php';

// --- Input Validation ---
$reset_token = $_GET['token'] ?? '';
if (empty($reset_token)) {
    die("<h1>Access Denied</h1><p>Missing token parameter.</p>");
}

// --- 1. Token and User Lookup ---
$requester_level = 0;
$requester_username = '';
$stmt = $conn->prepare("SELECT level, username FROM users WHERE reset_link_token = ?");
$stmt->bind_param("s", $reset_token);
$stmt->execute();
$stmt->bind_result($level, $username);

if ($stmt->fetch()) {
    $requester_level = (int)$level;
    $requester_username = $username;
}
$stmt->close();

// --- 2. Authorization Check (Admin Only) ---
if ($requester_level !== 1) {
    close_db_connection($conn);
    die("<h1>Access Denied</h1><p>User '{$requester_username}' is not an administrator (Level {$requester_level}).</p>");
}

// --- 3. Handle Reset Token Generation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_token'])) {
    $user_id = (int)$_POST['user_id'];
    $new_token = bin2hex(random_bytes(16)); // generate a 32-character token
    $stmt = $conn->prepare("UPDATE users SET reset_link_token = ? WHERE id_users = ?");
    $stmt->bind_param("si", $new_token, $user_id);
    $stmt->execute();
    $stmt->close();
    // Optional: reload the page to show updated token
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// --- 4. Display Users Table ---
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-200 font-sans p-6">
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Admin Dashboard - Users</h1>
    <p class="mb-6">Logged in as: <span class="text-green-400 font-semibold">{$requester_username}</span> (Admin Level 1)</p>
HTML;

// --- Fetch users ---
$stmt = $conn->prepare("SELECT id_users, username, level, reset_link_token FROM users ORDER BY id_users ASC");
$stmt->execute();
$stmt->bind_result($id_users, $username, $level, $reset_token);

$rows_exist = false;
echo '<div class="overflow-x-auto">';
echo '<table class="min-w-full bg-gray-800 border border-gray-700 rounded-lg">';
echo '<thead class="bg-gray-700 text-gray-200">';
echo '<tr>';
echo '<th class="px-6 py-3 text-left">Username</th>';
echo '<th class="px-6 py-3 text-left">Role</th>';
echo '<th class="px-6 py-3 text-left">Reset Token</th>';
echo '<th class="px-6 py-3 text-left">Action</th>';
echo '</tr></thead><tbody>';

while ($stmt->fetch()) {
    $rows_exist = true;
    $role = ($level == 1) ? 'Admin' : (($level == 3) ? 'Reseller' : 'User');
    $token_display = $reset_token ? htmlspecialchars($reset_token) : '<span class="text-red-400">None</span>';

    echo '<tr class="border-b border-gray-700 hover:bg-gray-700">';
    echo '<td class="px-6 py-3">' . htmlspecialchars($username) . '</td>';
    echo '<td class="px-6 py-3">' . $role . '</td>';
    echo '<td class="px-6 py-3 flex items-center justify-between">';
    echo '<span class="break-all">' . $token_display . '</span>';
    if ($reset_token) {
        echo '<button onclick="copyToClipboard(\'' . $reset_token . '\')" class="ml-2 bg-gray-600 hover:bg-gray-500 text-white text-xs px-2 py-1 rounded">Copy</button>';
    }
    echo '</td>';

    echo '<td class="px-6 py-3">';
    if (!$reset_token) {
        // Form to generate token
        echo '<form method="post" class="inline">';
        echo '<input type="hidden" name="user_id" value="' . $id_users . '">';
        echo '<button type="submit" name="generate_token" class="bg-green-600 hover:bg-green-500 text-white text-xs px-2 py-1 rounded">Generate Token</button>';
        echo '</form>';
    } else {
        echo '<span class="text-gray-400 text-xs">Token exists</span>';
    }
    echo '</td>';

    echo '</tr>';
}

echo '</tbody></table></div>';

if (!$rows_exist) {
    echo "<p class='mt-4'>No users found in the database.</p>";
}

echo <<<HTML
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Reset token copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}
</script>

</body>
</html>
HTML;

$stmt->close();
close_db_connection($conn);
?>
