<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối DB có retry ngắn để giảm lỗi max_user_connections
function open_db_with_retry($host, $user, $pass, $db, $retries = 2, $sleepMs = 300) {
    for ($i = 0; $i <= $retries; $i++) {
        $conn = @new mysqli($host, $user, $pass, $db);
        if (!$conn->connect_errno) {
            $conn->set_charset('utf8mb4');
            register_shutdown_function(function() use ($conn) {
                if ($conn && $conn->ping()) {
                    $conn->close();
                }
            });
            return $conn;
        }

        if ($conn->connect_errno == 1203 && $i < $retries) {
            usleep($sleepMs * 1000);
            continue;
        }

        die("Lỗi kết nối database: " . $conn->connect_error);
    }
}

$conn = open_db_with_retry("localhost", "mbktunp_hama", "mbktunp_hama", "mbktunp_hama");

// ====================== NHẬN KEY ======================
$activekey = isset($_GET['activekey']) ? $_GET['activekey'] : '';
$admin_username = isset($_GET['admin']) ? preg_replace('/[^A-Za-z0-9_]/', '', $_GET['admin']) : '';
$gameParam = isset($_GET['game']) ? trim((string) $_GET['game']) : '';

if (!$activekey) {
    die("Key không hợp lệ hoặc thiếu activekey!");
}

if (!ctype_xdigit($activekey)) {
    die("Key không hợp lệ!");
}

// ====================== GIẢI MÃ KEY ======================
define('AES_CBC', 'aes-128-cbc');

function dec($key, $iv, $data_hex) {
    $data = hex2bin($data_hex);
    $decrypt = openssl_decrypt($data, AES_CBC, $key, OPENSSL_ZERO_PADDING, $iv);
    return base64_decode($decrypt);
}

$currentDate = date("d");
$key_file = file('keyenc.txt', FILE_IGNORE_NEW_LINES);
$key_secret = isset($key_file[$currentDate - 1]) ? trim((string) $key_file[$currentDate - 1]) : '';

if ($key_secret === '') {
    die("Lỗi hệ thống: keyenc.txt không hợp lệ!");
}

$iv = 'fedcba9876543210';

$payload = dec($key_secret, $iv, $activekey);

if (!$payload) {
    die("Giải mã key thất bại!");
}

// Payload có thể là "username|package"
$payload = trim((string) $payload);
$gameFromKey = '';
if (strpos($payload, '|') !== false) {
    $parts = explode('|', $payload, 2);
    $username = trim((string) ($parts[0] ?? ''));
    $gameFromKey = trim((string) ($parts[1] ?? ''));
} else {
    $username = $payload;
}

// Giới hạn định dạng username để tránh key bị lợi dụng tạo chuỗi bất thường
if ($username === '' || strlen($username) > 64 || !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
    die("Key không hợp lệ!");
}

// ====================== TẠO USER SQL ======================
$password = bin2hex(random_bytes(6));
$UID = NULL;
$device_limit = 1;
$max_devices = 1;
$duration = 24;

$now = date("Y-m-d H:i:s");
$expired_date = NULL;

$gameResolved = $gameParam;
if ($gameResolved === '' && $gameFromKey !== '') {
    $gameResolved = $gameFromKey;
}
if ($gameFromKey !== '' && $gameParam !== '' && $gameParam !== $gameFromKey) {
    die("Game không hợp lệ!");
}
if ($gameResolved === '') {
    die("Thiếu game!");
}

$game = $gameResolved;
$gameName = null;
$gameId = null;
$adminId = null;
$adminLevel = null;

function column_exists($conn, $table, $column) {
    $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $stmt->bind_result($dummy);
    $exists = $stmt->fetch() ? true : false;
    $stmt->close();
    return $exists;
}

function format_duration_label($hours) {
    $hours = (int) $hours;
    if ($hours >= 24 && $hours % 24 === 0) {
        $days = (int) ($hours / 24);
        return $days . ' ' . ($days === 1 ? 'ngày' : 'ngày');
    }
    return $hours . ' giờ';
}

// Validate game exists & active
$stmt = $conn->prepare("SELECT id, package, name FROM games WHERE package = ? AND status = 'active' LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $gameResolved);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && ($g = $res->fetch_assoc())) {
        $gameId = (int) $g['id'];
        $game = (string) $g['package'];
        $gameName = (string) ($g['name'] ?? $g['package']);
    }
    $stmt->close();
}

if (!$gameId) {
    die("Game không hợp lệ hoặc đang tạm dừng!");
}

// Nếu có admin/seller thì kiểm tra quyền game
if ($admin_username && $gameId) {
    $hasGetkeyGames = column_exists($conn, 'admin', 'getkey_games');
    $fields = $hasGetkeyGames ? 'id_users, level, getkey_games' : 'id_users, level';
    $stmt = $conn->prepare("SELECT {$fields} FROM admin WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $admin_username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($a = $res->fetch_assoc())) {
            $adminId = (int) $a['id_users'];
            $adminLevel = (int) $a['level'];
            $selectedCsv = $hasGetkeyGames ? (string) ($a['getkey_games'] ?? '') : '';

            // Level != 1: phải được gán game trong user_games
            if ($adminLevel !== 1) {
                $check = $conn->prepare("SELECT 1 FROM user_games WHERE user_id = ? AND game_id = ? LIMIT 1");
                if ($check) {
                    $check->bind_param('ii', $adminId, $gameId);
                    $check->execute();
                    $ok = $check->get_result()->fetch_assoc();
                    $check->close();
                    if (!$ok) {
                        die("Tài khoản không được cấp game này!");
                    }
                }
            }

            // Nếu có cấu hình game getkey thì phải nằm trong danh sách
            if ($hasGetkeyGames && $selectedCsv !== '') {
                $selectedIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $selectedCsv)))));
                if (!in_array($gameId, $selectedIds, true)) {
                    die("Game này chưa được bật trong GetKey!");
                }
            }
        } else {
            die("Admin/Seller không tồn tại!");
        }
        $stmt->close();
    }
}

$key_level = 1;
$registrator = $admin_username ?: 'system';

// Chống dùng lại link/activekey với grace period 5 phút
$checkKeyUsed = $conn->prepare("SELECT activated_at FROM activated_keys WHERE activekey_hash = ? LIMIT 1");
if ($checkKeyUsed) {
    $keyHash = hash('sha256', $activekey);
    $checkKeyUsed->bind_param('s', $keyHash);
    $checkKeyUsed->execute();
    $result = $checkKeyUsed->get_result();
    $used = $result->fetch_assoc();
    $checkKeyUsed->close();
    
    if ($used) {
        // Kiểm tra thời gian - cho phép dùng lại trong 5 phút
        $activatedTime = strtotime($used['activated_at']);
        $now = time();
        $gracePeriod = 5 * 60; // 5 phút
        
        if (($now - $activatedTime) > $gracePeriod) {
            die("Key đã được sử dụng!");
        }
        // Nếu trong grace period, cho phép tạo lại
    }
}

// Nếu username từ key đã tồn tại, tạo username mới
$originalUsername = $username;
$counter = 1;
$checkDup = $conn->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
while ($counter <= 100) {
    $testUsername = ($counter === 1) ? $originalUsername : $originalUsername . $counter;
    if ($checkDup) {
        $checkDup->bind_param('s', $testUsername);
        $checkDup->execute();
        $dup = $checkDup->get_result()->fetch_assoc();
        if (!$dup) {
            $username = $testUsername;
            break;
        }
    }
    $counter++;
}
$checkDup->close();

$sql = "INSERT INTO users 
(username, password, UID, device_limit, max_devices, duration, expired_date, expired, game, key_level, registrator, status, created_at) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sssiiisssiss",
    $username,
    $password,
    $UID,
    $device_limit,
    $max_devices,
    $duration,
    $expired_date,
    $expired_date,
    $game,
    $key_level,
    $registrator,
    $now
);

if (!$stmt->execute()) {
    die("Lỗi SQL khi tạo user: " . $stmt->error);
}

$durationLabel = format_duration_label($duration);
$deviceLabel = '0/' . $max_devices;
$gameLabel = $gameName ?: $game;
$copyPayload = "User: {$username} | Game: {$gameLabel} | Hạn: {$durationLabel} | Thiết bị: {$deviceLabel}";

// After successful INSERT, mark key as used
$keyHash = hash('sha256', $activekey);
$markUsed = $conn->prepare("INSERT INTO activated_keys (activekey_hash, username, activated_at) VALUES (?, ?, NOW())");
if ($markUsed) {
    $markUsed->bind_param('ss', $keyHash, $username);
    $markUsed->execute();
    $markUsed->close();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Key đã sẵn sàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/natacode.css">
    <link rel="stylesheet" href="style222.css">
</head>
<body class="app-shell getkey-page">
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card key-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="key-badge">Key miễn phí</div>
                    <h1 class="key-title">Key đã sẵn sàng</h1>
                    <p class="text-muted mb-4">Copy thông tin bên dưới và gửi cho khách hàng.</p>

                    <div class="mb-3">
                        <label for="copyUsername" class="form-label">Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="copyUsername" value="<?php echo htmlspecialchars($username); ?>" readonly>
                            <button type="button" class="btn btn-outline-primary copy-btn" data-copy-target="copyUsername">Copy</button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-light text-dark">Game: <?php echo htmlspecialchars($gameLabel); ?></span>
                        <span class="badge bg-light text-dark">Hạn: <?php echo htmlspecialchars($durationLabel); ?></span>
                        <span class="badge bg-light text-dark">Thiết bị: <?php echo htmlspecialchars($deviceLabel); ?></span>
                        <span class="badge bg-light text-dark">Người tạo: <?php echo htmlspecialchars($registrator); ?></span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" id="btnCopyAll" data-copy="<?php echo htmlspecialchars($copyPayload, ENT_QUOTES); ?>">
                            Copy tất cả thông tin
                        </button>
                        <a class="btn btn-outline-secondary" href="Getkey.php<?php echo $admin_username ? ('?admin=' . urlencode($admin_username)) : ''; ?>">
                            Lấy thêm key
                        </a>
                    </div>

                    <p class="small text-muted mt-4 mb-0">
                        Thời gian bắt đầu tính khi key được đăng nhập.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function() {
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        var temp = document.createElement("textarea");
        temp.value = text;
        temp.style.position = "fixed";
        temp.style.left = "-9999px";
        document.body.appendChild(temp);
        temp.focus();
        temp.select();
        var ok = document.execCommand("copy");
        document.body.removeChild(temp);
        return ok ? Promise.resolve() : Promise.reject();
    }

    document.querySelectorAll(".copy-btn").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var targetId = btn.getAttribute("data-copy-target");
            var input = document.getElementById(targetId);
            if (!input) {
                return;
            }
            copyText(input.value).then(function() {
                var original = btn.textContent;
                btn.textContent = "Copied";
                btn.classList.remove("btn-outline-primary");
                btn.classList.add("btn-success");
                setTimeout(function() {
                    btn.textContent = original;
                    btn.classList.add("btn-outline-primary");
                    btn.classList.remove("btn-success");
                }, 1200);
            }).catch(function() {
                alert("Xay ra loi khi sao chep");
            });
        });
    });

    var btnCopyAll = document.getElementById("btnCopyAll");
    if (btnCopyAll) {
        btnCopyAll.addEventListener("click", function() {
            var payload = btnCopyAll.getAttribute("data-copy") || "";
            copyText(payload).then(function() {
                var original = btnCopyAll.textContent;
                btnCopyAll.textContent = "Copied";
                btnCopyAll.classList.add("btn-success");
                btnCopyAll.classList.remove("btn-primary");
                setTimeout(function() {
                    btnCopyAll.textContent = original;
                    btnCopyAll.classList.add("btn-primary");
                    btnCopyAll.classList.remove("btn-success");
                }, 1200);
            }).catch(function() {
                alert("Xay ra loi khi sao chep");
            });
        });
    }
})();
</script>
</body>
</html>
