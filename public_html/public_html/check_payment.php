<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
header('Content-Type: application/json');

// Cấu hình API tra cứu giao dịch (ví dụ Web2M cho ACB).
$bankConfig = [
    'api_token'   => '1F174FC6-71F7-57A8-95BB-5BB5EC1C48E6', // Điền token API tại đây để hệ thống tự quét bank.
    'api_url'     => 'https://api.web2m.com/historyacb/{token}',
    'account_no'  => '24178037',
    'account_name'=> 'Pham Quoc Trieu',
];

function respond(string $status, array $data = []): void
{
    echo json_encode(array_merge(['status' => $status], $data));
    exit;
}

function calculateAmountFromDays(int $days): int
{
    if ($days == 1) return 5000;
    if ($days == 2) return 20000;
    if ($days == 3) return 25000;
    if ($days >= 4 && $days <= 6) return 25000 + ($days - 3) * 7000;
    if ($days == 7) return 50000;
    if ($days >= 8 && $days <= 29) return 50000 + ($days - 7) * 4500;
    if ($days == 30) return 150000;
    if ($days >= 31 && $days <= 59) return 150000 + ($days - 30) * 3500;
    if ($days == 60) return 250000;
    if ($days >= 61 && $days <= 89) return 250000 + ($days - 60) * 3500;
    if ($days == 90) return 350000;
    if ($days > 90 && $days < 180) return 350000 + ($days - 90) * 2900;
    if ($days == 180) return 600000;
    if ($days > 180 && $days <= 365) return 600000 + ($days - 180) * 3000;
    return 0;
}

function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO("mysql:host=localhost;dbname=arabemodz_lamdocheat", "arabemodz_lamdocheat", "arabemodz_lamdocheat");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function normalizeAmount($raw): int
{
    if (is_string($raw)) {
        $raw = str_replace([',', '.'], '', $raw);
    }
    return (int) $raw;
}

function fetchTransactions(array $config): array
{
    if (empty($config['api_token']) || empty($config['api_url'])) {
        return [];
    }

    $url = str_replace('{token}', urlencode($config['api_token']), $config['api_url']);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "User-Agent: PaymentChecker/1.0\r\n",
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return [];
    }

    $json = json_decode($response, true);
    if (!is_array($json)) {
        return [];
    }

    $candidates = [];
    // Lấy các key quen thuộc ở mức root hoặc trong data.
    foreach (['transactions', 'items', 'history', 'TransactionHistory'] as $key) {
        if (!empty($json[$key]) && is_array($json[$key])) {
            $candidates = $json[$key];
            break;
        }
    }

    if (empty($candidates) && !empty($json['data'])) {
        $data = $json['data'];
        if (is_array($data)) {
            foreach (['transactions', 'items', 'history', 'TransactionHistory', 'TranList', 'transaction'] as $key) {
                if (!empty($data[$key]) && is_array($data[$key])) {
                    $candidates = $data[$key];
                    break;
                }
            }
            if (empty($candidates) && isset($data[0]) && is_array($data[0])) {
                $candidates = $data;
            }
        }
    }

    if (empty($candidates) && isset($json[0]) && is_array($json[0])) {
        $candidates = $json;
    }

    // Nếu vẫn rỗng, duyệt sâu toàn bộ JSON để gom mọi mảng list.
    if (empty($candidates)) {
        $stack = [$json];
        while ($stack) {
            $node = array_pop($stack);
            if (!is_array($node)) continue;

            $isList = array_keys($node) === range(0, count($node) - 1);
            if ($isList && isset($node[0]) && is_array($node[0])) {
                $candidates = array_merge($candidates, $node);
            }
            foreach ($node as $child) {
                if (is_array($child)) {
                    $stack[] = $child;
                }
            }
        }
    }

    $normalized = [];
    foreach ($candidates as $item) {
        if (!is_array($item)) continue;

        $desc = '';
        foreach (['description', 'Description', 'desc', 'content', 'Content', 'tranDesc', 'note', 'remark', 'addInfo', 'message', 'detail', 'mota', 'MoTa', 'noi_dung', 'NoiDung', 'ND'] as $k) {
            if (!empty($item[$k]) && is_string($item[$k])) {
                $desc = trim($item[$k]);
                break;
            }
        }

        $amount = null;
        foreach (['amount', 'Amount', 'creditAmount', 'CreditAmount', 'money', 'value', 'SoTien', 'sotien', 'SoTienGhiCo', 'SoTienGhiNo', 'amountOut', 'amountIn', 'valueIn', 'valueOut'] as $k) {
            if (isset($item[$k])) {
                $amount = abs(normalizeAmount($item[$k]));
                break;
            }
        }

        $account = '';
        foreach (['accountNo', 'accountNumber', 'AccNo', 'subAcc', 'bankAccount', 'creditAccount', 'toAccount', 'account', 'SoTaiKhoan', 'stk', 'TK', 'accountReceive', 'accountReceiveNo'] as $k) {
            if (!empty($item[$k]) && is_string($item[$k])) {
                $account = preg_replace('/\D+/', '', $item[$k]);
                break;
            }
        }

        if ($desc && $amount > 0) {
            $normalized[] = [
                'description' => $desc,
                'amount' => $amount,
                'account' => $account,
            ];
        }
    }

    return $normalized;
}

function findMatchingTransaction(array $transactions, string $code, int $expectedAmount, string $accountNo = ''): ?array
{
    foreach ($transactions as $tx) {
        if (!isset($tx['description'], $tx['amount'])) {
            continue;
        }

        if (stripos($tx['description'], $code) === false) {
            continue;
        }

        if ($tx['amount'] < $expectedAmount) {
            continue;
        }

        if ($accountNo) {
            $target = preg_replace('/\D+/', '', $accountNo);
            $txAcc = isset($tx['account']) ? $tx['account'] : '';
            if ($txAcc && $target && $txAcc !== $target) {
                continue;
            }
        }

        return $tx;
    }

    return null;
}

function createLicense(PDO $pdo, int $days, string $registrator = 'auto_buy'): string
{
    $durationHours = max(1, $days) * 24;
    $game = 'PLAY';
    $key_level = 2;
    $device_limit = 1;
    $max_devices = 1;
    $uid = null;
    $expired_date = null;
    $now = date("Y-m-d H:i:s");

    $sql = "INSERT INTO users 
    (username, password, UID, device_limit, max_devices, duration, expired_date, expired, game, key_level, registrator, status, created_at) 
    VALUES (:username, :password, :uid, :device_limit, :max_devices, :duration, :expired_date, :expired, :game, :key_level, :registrator, 1, :created_at)";

    $stmt = $pdo->prepare($sql);

    for ($i = 0; $i < 5; $i++) {
        $username = 'PAID_' . strtoupper(bin2hex(random_bytes(4)));
        $password = bin2hex(random_bytes(6));

        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':uid' => $uid,
                ':device_limit' => $device_limit,
                ':max_devices' => $max_devices,
                ':duration' => $durationHours,
                ':expired_date' => $expired_date,
                ':expired' => $expired_date,
                ':game' => $game,
                ':key_level' => $key_level,
                ':registrator' => $registrator,
                ':created_at' => $now,
            ]);

            return $username;
        } catch (PDOException $e) {
            // Nếu trùng username sẽ thử lại, còn lỗi khác thì báo ngay.
            if ($e->getCode() !== '23000') { // 23000 = constraint violation
                throw $e;
            }
        }
    }

    throw new RuntimeException('Không thể tạo key, vui lòng thử lại sau.');
}

$code = strtoupper($_GET['code'] ?? '');
if (!preg_match('/^[A-Z0-9]{6}$/', $code)) {
    respond('error', ['message' => 'Mã đơn hàng không hợp lệ.']);
}

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT * FROM pending_requests WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        respond('error', ['message' => 'Không tìm thấy đơn hàng.']);
    }

    if ($order['is_paid']) {
        respond('paid', [
            'key' => $order['key_generated'],
            'days' => (int) $order['days'],
        ]);
    }

    $expectedAmount = calculateAmountFromDays((int) $order['days']);
    if ($expectedAmount <= 0) {
        respond('error', ['message' => 'Số ngày đơn hàng không hợp lệ.']);
    }

    if (empty($bankConfig['api_token'])) {
        respond('error', ['message' => 'Hệ thống chưa cấu hình API kiểm tra giao dịch. Vui lòng liên hệ admin.']);
    }

    $transactions = fetchTransactions($bankConfig);
    $matched = findMatchingTransaction($transactions, $code, $expectedAmount, $bankConfig['account_no']);

    if (!$matched) {
        respond('pending', ['message' => 'Đang chờ thanh toán...']);
    }

    $pdo->beginTransaction();

    // Khóa đơn để tránh tạo key trùng khi nhiều request cùng lúc.
    $stmt = $pdo->prepare("SELECT * FROM pending_requests WHERE code = ? FOR UPDATE");
    $stmt->execute([$code]);
    $orderLocked = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orderLocked) {
        $pdo->rollBack();
        respond('error', ['message' => 'Không tìm thấy đơn hàng.']);
    }

    if ($orderLocked['is_paid']) {
        $pdo->rollBack();
        respond('paid', [
            'key' => $orderLocked['key_generated'],
            'days' => (int) $orderLocked['days'],
        ]);
    }

    $key = createLicense($pdo, (int) $orderLocked['days']);

    $update = $pdo->prepare("UPDATE pending_requests SET is_paid = 1, key_generated = ?, paid_at = NOW() WHERE code = ?");
    $update->execute([$key, $code]);

    $pdo->commit();

    respond('paid', [
        'key' => $key,
        'days' => (int) $orderLocked['days'],
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond('error', ['message' => 'Lỗi xử lý: ' . $e->getMessage()]);
}
