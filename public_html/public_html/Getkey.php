<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối DB có retry ngắn để tránh lỗi max_user_connections khi quá tải
function open_db_with_retry($host, $user, $pass, $db, $retries = 2, $sleepMs = 300) {
    for ($i = 0; $i <= $retries; $i++) {
        $conn = @new mysqli($host, $user, $pass, $db);
        if (!$conn->connect_errno) {
            $conn->set_charset('utf8mb4');
            // Đảm bảo đóng kết nối khi script kết thúc/die
            register_shutdown_function(function() use ($conn) {
                if ($conn && $conn->ping()) {
                    $conn->close();
                }
            });
            return $conn;
        }

        // 1203: max_user_connections; thử lại sau khi chờ ngắn
        if ($conn->connect_errno == 1203 && $i < $retries) {
            usleep($sleepMs * 1000);
            continue;
        }

        die("Lỗi kết nối database: " . $conn->connect_error);
    }
}

$conn = open_db_with_retry("localhost", "mbktunp_hama", "mbktunp_hama", "mbktunp_hama");
session_start();

$tabela = 'tokens';

$ipcheck = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];

// Admin/Seller username để lấy token riêng (?admin=username - chỉ ký tự chữ/số/_)
$admin_username = '';
if (isset($_GET['admin'])) {
    $admin_username = preg_replace('/[^A-Za-z0-9_]/', '', $_GET['admin']);
}

// Kiểm tra cột tồn tại (dùng information_schema)
function column_exists($conn, $column) {
    $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("s", $column);
    $stmt->execute();
    $stmt->bind_result($dummy);
    $exists = $stmt->fetch() ? true : false;
    $stmt->close();
    return $exists;
}

function fetch_url($url) {
    if (!$url) return false;

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "User-Agent: GetKeyBot/1.0\r\n",
        ]
    ]);

    $data = @file_get_contents($url, false, $context);
    if ($data !== false) {
        return $data;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'GetKeyBot/1.0',
        ]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    return false;
}

// Return body + final URL after redirects (for APIs that 301 to short link)
function fetch_with_redirect($url) {
    if (!$url) {
        return ['body' => false, 'final' => null];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'GetKeyBot/1.0',
        ]);
        $body = curl_exec($ch);
        $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return ['body' => $body, 'final' => $final];
    }

    $body = fetch_url($url);
    return ['body' => $body, 'final' => null];
}

function shorten_with_xlink($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }
    $apiUrl = "https://xlink.co/api?token={$apiToken}&url=" . urlencode($destinationUrl);
    $res = @json_decode(fetch_url($apiUrl), true);
    return $res['shortenedUrl'] ?? null;
}

function shorten_with_linkx($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }
    $alias = substr(bin2hex(random_bytes(4)), 0, 8);
    $params = [
        'api' => $apiToken,
        'url' => $destinationUrl,
        'alias' => $alias,
    ];
    $apiUrl = 'https://linkx.me/api?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $raw = fetch_url($apiUrl);
    if ($raw === false) {
        return null;
    }
    $res = json_decode($raw, true);
    if (is_array($res)) {
        $status = strtolower((string) ($res['status'] ?? ''));
        if ($status === 'error') {
            return null;
        }
        foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data'] as $k) {
            if (!empty($res[$k]) && is_string($res[$k])) {
                return trim($res[$k]);
            }
        }
    }
    $raw = trim($raw);
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        return $raw;
    }
    if (preg_match('#https?://[^\s"]+#i', $raw, $m)) {
        return $m[0];
    }
    return null;
}

function shorten_with_yeumoney($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    // YeuLink/YeuMoney API format
    $alias = substr(bin2hex(random_bytes(4)), 0, 8);
    $requestUrl = 'https://yeulink.com/api?token=' . urlencode($apiToken) .
                  '&url=' . urlencode($destinationUrl) .
                  '&alias=' . $alias;

    $raw = fetch_url($requestUrl);
    if ($raw === false) {
        return null;
    }

    // Try JSON first
    $res = json_decode($raw, true);
    if (is_array($res)) {
        foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data'] as $k) {
            if (!empty($res[$k]) && is_string($res[$k])) {
                return trim($res[$k]);
            }
        }
    }

    // If API returns plain string link
    $raw = trim($raw);
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        return $raw;
    }

    return null;
}

function shorten_with_sieuthiapi($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    $requestUrl = 'https://sieuthidora.io.vn/api1/st.php?token=' . urlencode($apiToken) .
                  '&url=' . urlencode($destinationUrl);

    $resp = fetch_with_redirect($requestUrl);
    $raw = $resp['body'];
    $final = $resp['final'];

    // Nếu API redirect thẳng tới link rút gọn
    if ($final && filter_var($final, FILTER_VALIDATE_URL) && $final !== $destinationUrl) {
        return $final;
    }

    if ($raw === false) {
        return null;
    }

    $raw = trim($raw);
    $res = json_decode($raw, true);
    if (is_array($res)) {
        foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data', 'short', 'shortUrl', 'shortURL', 'result'] as $k) {
            if (!empty($res[$k]) && is_string($res[$k])) {
                $val = trim($res[$k]);
                if (filter_var($val, FILTER_VALIDATE_URL)) {
                    return $val;
                }
            }
        }
    }

    // Nếu trả về plain text chứa URL
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        return $raw;
    }
    if (preg_match('#https?://[^\s"]+#i', $raw, $m)) {
        return $m[0];
    }

    return null;
}

function shorten_with_funlink($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    // Official public API
    $apiUrl = 'https://private.funlink.io/api/cong-khai/tao-lien-ket?apikey=' . urlencode($apiToken) . '&url=' . urlencode($destinationUrl);
    $raw = fetch_url($apiUrl);
    if ($raw !== false) {
        $res = json_decode(trim($raw), true);
        if (is_array($res)) {
            if (!empty($res['id'])) {
                // Build short link with funlink domain
                return 'https://funlink.io/' . $res['id'];
            }
            foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data', 'shortlink', 'short', 'shortUrl', 'shortURL'] as $k) {
                if (!empty($res[$k]) && is_string($res[$k])) {
                    $val = trim($res[$k]);
                    if (filter_var($val, FILTER_VALIDATE_URL)) {
                        return $val;
                    }
                }
            }
        }

        $rawTrim = trim($raw);
        if (filter_var($rawTrim, FILTER_VALIDATE_URL)) {
            return $rawTrim;
        }
    }

    // Backup endpoint
    $fallbackUrl = 'https://funlink.io/st?apikey=' . urlencode($apiToken) . '&url=' . urlencode($destinationUrl);
    $resp = fetch_with_redirect($fallbackUrl);
    $raw = $resp['body'];
    $final = $resp['final'];
    if ($final && filter_var($final, FILTER_VALIDATE_URL) && strpos($final, 'funlink.io/') !== false && $final !== $destinationUrl) {
        return $final;
    }
    if ($raw !== false) {
        $rawTrim = trim($raw);
        $res = json_decode($rawTrim, true);
        if (is_array($res)) {
            foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data', 'shortlink', 'short', 'shortUrl', 'shortURL'] as $k) {
                if (!empty($res[$k]) && is_string($res[$k])) {
                    $val = trim($res[$k]);
                    if (filter_var($val, FILTER_VALIDATE_URL)) {
                        return $val;
                    }
                }
            }
            if (!empty($res['id'])) {
                return 'https://funlink.io/' . $res['id'];
            }
        }
        if (filter_var($rawTrim, FILTER_VALIDATE_URL)) {
            return $rawTrim;
        }
    }

    return null;
}

function shorten_with_layma($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    $baseUrl = 'https://api.layma.net/api/admin/shortlink/quicklink';
    $params = [
        'tokenUser' => $apiToken,
        'format' => 'json',
        'url' => $destinationUrl,
        'link_du_phong' => $destinationUrl,
    ];
    $apiUrl = $baseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $raw = fetch_url($apiUrl);
    if ($raw !== false) {
        $rawTrim = trim($raw);
        $res = json_decode($rawTrim, true);
        if (is_array($res)) {
            if (!array_key_exists('success', $res) || $res['success'] !== false) {
                foreach (['html', 'shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data', 'shortlink', 'short', 'shortUrl', 'shortURL'] as $k) {
                    if (!empty($res[$k]) && is_string($res[$k])) {
                        $val = trim($res[$k]);
                        if (filter_var($val, FILTER_VALIDATE_URL)) {
                            return $val;
                        }
                    }
                }
            }
        }

        if (filter_var($rawTrim, FILTER_VALIDATE_URL)) {
            return $rawTrim;
        }
    }

    $params['format'] = 'text';
    $textUrl = $baseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $rawText = fetch_url($textUrl);
    if ($rawText === false) {
        return null;
    }
    $rawText = trim($rawText);
    if (filter_var($rawText, FILTER_VALIDATE_URL)) {
        return $rawText;
    }
    if (preg_match('#https?://[^\s"]+#i', $rawText, $m)) {
        return $m[0];
    }

    return null;
}

function shorten_with_just2earn($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    // Thử endpoint API chính thức
    $apiUrl = 'https://just2earn.com/api?api=' . urlencode($apiToken) . 
              '&url=' . urlencode($destinationUrl);

    $httpCode = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        // HTTP 521/522/523 = Cloudflare errors, skip logging
        if ($httpCode >= 521 && $httpCode <= 523) {
            return null;
        }

        if ($raw !== false && $httpCode == 200) {
            // Parse JSON response
            $res = json_decode(trim($raw), true);
            if (is_array($res)) {
                if (isset($res['status']) && $res['status'] === 'error') {
                    return null;
                }
                
                foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data'] as $k) {
                    if (!empty($res[$k]) && is_string($res[$k])) {
                        $val = trim($res[$k]);
                        if (filter_var($val, FILTER_VALIDATE_URL)) {
                            return $val;
                        }
                    }
                }
            }

            // Parse plain URL
            $raw = trim($raw);
            if (filter_var($raw, FILTER_VALIDATE_URL)) {
                return $raw;
            }
            
            if (preg_match('#https?://[^\s"<>]+#i', $raw, $m)) {
                $url = $m[0];
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return $url;
                }
            }
        }
    }

    return null;
}

function normalize_short_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    $url = trim($url, "\"'");
    $url = str_replace('\\/', '/', $url);
    return $url;
}

function is_cloudflare_error_response($raw) {
    $raw = (string) $raw;
    if ($raw === '') {
        return false;
    }
    $lower = strtolower($raw);
    if (strpos($lower, 'cloudflare.com/5xx-error-landing') !== false) {
        return true;
    }
    if (strpos($lower, 'cf-error-code') !== false) {
        return true;
    }
    if (strpos($lower, 'cloudflare') !== false && strpos($lower, 'error') !== false) {
        return true;
    }
    return false;
}

function shortlink_is_healthy($url) {
    if ($url === '') {
        return false;
    }
    if (!function_exists('curl_init')) {
        return true;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'GetKeyBot/1.0',
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $ok = curl_exec($ch);
    if ($ok === false) {
        curl_close($ch);
        return false;
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 0 || $code >= 500) {
        return false;
    }
    return true;
}

function normalize_vuotlink_token($token) {
    $token = trim((string) $token);
    if ($token === '') {
        return '';
    }

    $query = '';
    if (preg_match('#^https?://#i', $token)) {
        $parts = parse_url($token);
        $query = $parts['query'] ?? '';
    } elseif (strpos($token, '?') !== false && strpos($token, 'api=') !== false) {
        $query = substr($token, strpos($token, '?') + 1);
    } elseif (strpos($token, 'api=') !== false) {
        $query = $token;
    }

    if ($query !== '') {
        parse_str($query, $qs);
        if (!empty($qs['api'])) {
            return (string) $qs['api'];
        }
    }

    return $token;
}

function normalize_short_service($service) {
    $service = strtolower(trim((string) $service));
    if ($service === 'vuotlink.vip' || $service === 'vuotlinkvip') {
        $service = 'vuotlink';
    }
    if ($service === 'linkx.me' || $service === 'linkxme') {
        $service = 'linkx';
    }
    $allowed = ['xlink', 'linkx', 'yeumoney', 'sieuthiapi', 'funlink', 'layma', 'vuotlink', 'just2earn', 'nhapma'];
    return in_array($service, $allowed, true) ? $service : 'xlink';
}

function current_base_url() {
    $scheme = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
        if (in_array($proto, ['http', 'https'], true)) {
            $scheme = $proto;
        }
    } elseif (!empty($_SERVER['HTTP_CF_VISITOR'])) {
        $cf = json_decode((string) $_SERVER['HTTP_CF_VISITOR'], true);
        if (is_array($cf) && !empty($cf['scheme']) && in_array($cf['scheme'], ['http', 'https'], true)) {
            $scheme = $cf['scheme'];
        }
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return '';
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(dirname($scriptName), '/');
    $basePath = $dir === '/' ? '' : $dir;
    return $scheme . '://' . $host . $basePath;
}

function shorten_with_nhapma($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    $alias = substr(bin2hex(random_bytes(4)), 0, 8);
    $params = [
        'token' => $apiToken,
        'url' => $destinationUrl,
        'alias' => $alias,
    ];
    $apiUrl = 'https://service.nhapma.com/api?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $raw = fetch_url($apiUrl);
    if ($raw === false) {
        return null;
    }

    $rawTrim = trim($raw);
    $res = json_decode($rawTrim, true);
    if (is_array($res)) {
        $status = strtolower((string) ($res['status'] ?? ''));
        if ($status !== 'error') {
            foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data', 'shortlink', 'short', 'shortUrl', 'shortURL'] as $k) {
                if (!empty($res[$k]) && is_string($res[$k])) {
                    $val = trim($res[$k]);
                    if (filter_var($val, FILTER_VALIDATE_URL)) {
                        return $val;
                    }
                }
            }
        }
    }

    // If API returns plain URL
    if (filter_var($rawTrim, FILTER_VALIDATE_URL)) {
        return $rawTrim;
    }

    if (preg_match('#https?://[^\s"]+#i', $rawTrim, $m)) {
        return $m[0];
    }

    return null;
}

function shorten_with_vuotlink($destinationUrl, $apiToken) {
    if (!$destinationUrl || !$apiToken) {
        return null;
    }

    $apiToken = normalize_vuotlink_token($apiToken);
    if ($apiToken === '') {
        return null;
    }

    $alias = substr(bin2hex(random_bytes(4)), 0, 8);
    $params = [
        'api' => $apiToken,
        'url' => $destinationUrl,
        'alias' => $alias,
    ];
    $apiUrl = 'https://vuotlink.vip/api?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $raw = fetch_url($apiUrl);
    if ($raw === false) {
        $raw = null;
    }
    if ($raw !== null && is_cloudflare_error_response($raw)) {
        $raw = null;
    }

    if ($raw !== null) {
        $rawTrim = trim($raw);
        $res = json_decode($rawTrim, true);
        if (is_array($res)) {
            $status = strtolower((string) ($res['status'] ?? ''));
            if ($status !== 'error') {
                foreach (['shortenedUrl', 'shortenedurl', 'shorturl', 'url', 'link', 'data', 'shortlink', 'short', 'shortUrl', 'shortURL', 'html'] as $k) {
                    if (!empty($res[$k]) && is_string($res[$k])) {
                        $val = normalize_short_url($res[$k]);
                        if (filter_var($val, FILTER_VALIDATE_URL)) {
                            if (!shortlink_is_healthy($val)) {
                                return null;
                            }
                            return $val;
                        }
                    }
                }
            }
        }

        $rawClean = normalize_short_url($rawTrim);
        if (filter_var($rawClean, FILTER_VALIDATE_URL)) {
            if (!shortlink_is_healthy($rawClean)) {
                return null;
            }
            return $rawClean;
        }
        if (preg_match('#https?://[^\s"\\\\]+#i', $rawClean, $m)) {
            if (!shortlink_is_healthy($m[0])) {
                return null;
            }
            return $m[0];
        }
    }

    $params['format'] = 'text';
    $textUrl = 'https://vuotlink.vip/api?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $rawText = fetch_url($textUrl);
    if ($rawText === false) {
        return null;
    }
    if (is_cloudflare_error_response($rawText)) {
        return null;
    }
    $rawText = normalize_short_url($rawText);
    if ($rawText === '') {
        return null;
    }
    if (filter_var($rawText, FILTER_VALIDATE_URL)) {
        if (!shortlink_is_healthy($rawText)) {
            return null;
        }
        return $rawText;
    }
    if (preg_match('#https?://[^\s"\\\\]+#i', $rawText, $m)) {
        if (!shortlink_is_healthy($m[0])) {
            return null;
        }
        return $m[0];
    }

    return null;
}

$has_steps    = column_exists($conn, 'getkey_steps');
$has_telegram = column_exists($conn, 'getkey_telegram');
$has_buy_url = column_exists($conn, 'getkey_buy_url');
$has_buy_ib = column_exists($conn, 'getkey_buy_ib');
$has_support_tele = column_exists($conn, 'getkey_support_tele');
$has_auto_buy = column_exists($conn, 'getkey_auto_buy');
$has_short_service = column_exists($conn, 'shortlink_service');
$has_getkey_games = column_exists($conn, 'getkey_games');

$admin_settings = [
    'id_users' => 0,
    'level' => 0,
    'api_token' => '975fe140-07ca-4cd1-9235-7718fcbda7d8', // fallback
    'getkey_steps' => 1,
    'getkey_telegram' => '',
    'getkey_buy_url' => '',
    'getkey_buy_ib' => '',
    'getkey_support_tele' => '',
    'getkey_auto_buy' => '',
    'shortlink_service' => 'xlink',
    'getkey_games' => ''
];

if ($admin_username) {
    $fields = ['id_users', 'level', 'api_token'];
    if ($has_steps) $fields[] = 'getkey_steps';
    if ($has_telegram) $fields[] = 'getkey_telegram';
    if ($has_buy_url) $fields[] = 'getkey_buy_url';
    if ($has_buy_ib) $fields[] = 'getkey_buy_ib';
    if ($has_support_tele) $fields[] = 'getkey_support_tele';
    if ($has_auto_buy) $fields[] = 'getkey_auto_buy';
    if ($has_short_service) $fields[] = 'shortlink_service';
    if ($has_getkey_games) $fields[] = 'getkey_games';
    $sql = "SELECT " . implode(', ', $fields) . " FROM admin WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $admin_username);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                if (isset($row['id_users'])) {
                    $admin_settings['id_users'] = (int) $row['id_users'];
                }
                if (isset($row['level'])) {
                    $admin_settings['level'] = (int) $row['level'];
                }
                if (!empty($row['api_token'])) {
                    $admin_settings['api_token'] = $row['api_token'];
                }
                if ($has_steps && isset($row['getkey_steps'])) {
                    $admin_settings['getkey_steps'] = max(1, (int) $row['getkey_steps']);
                }
                if ($has_telegram && isset($row['getkey_telegram'])) {
                    $admin_settings['getkey_telegram'] = trim((string) $row['getkey_telegram']);
                }
                if ($has_buy_url && isset($row['getkey_buy_url'])) {
                    $admin_settings['getkey_buy_url'] = trim((string) $row['getkey_buy_url']);
                }
                if ($has_buy_ib && isset($row['getkey_buy_ib'])) {
                    $admin_settings['getkey_buy_ib'] = trim((string) $row['getkey_buy_ib']);
                }
                if ($has_support_tele && isset($row['getkey_support_tele'])) {
                    $admin_settings['getkey_support_tele'] = trim((string) $row['getkey_support_tele']);
                }
                if ($has_auto_buy && isset($row['getkey_auto_buy'])) {
                    $admin_settings['getkey_auto_buy'] = trim((string) $row['getkey_auto_buy']);
                }
                if ($has_short_service && isset($row['shortlink_service']) && $row['shortlink_service']) {
                    $admin_settings['shortlink_service'] = strtolower(trim((string) $row['shortlink_service']));
                }
                if ($has_getkey_games && isset($row['getkey_games'])) {
                    $admin_settings['getkey_games'] = trim((string) $row['getkey_games']);
                }
            }
        }
        $stmt->close();
    }
}

$telegramLink = !empty($admin_settings['getkey_telegram']) ? $admin_settings['getkey_telegram'] : 'https://t.me/mrlightxvdd';
$buyLink = !empty($admin_settings['getkey_buy_url']) ? $admin_settings['getkey_buy_url'] : 'https://mrlightdva.online/buy.php';
$buyIbLink = !empty($admin_settings['getkey_buy_ib']) ? $admin_settings['getkey_buy_ib'] : '';
$supportTeleLink = !empty($admin_settings['getkey_support_tele']) ? $admin_settings['getkey_support_tele'] : '';
$autoBuyLink = !empty($admin_settings['getkey_auto_buy']) ? $admin_settings['getkey_auto_buy'] : '';
$shortTokenRaw   = trim((string) $admin_settings['api_token']);
$shortSteps      = max(1, (int) $admin_settings['getkey_steps']);

$shortlinkChain = [];
$decodedChain = json_decode($shortTokenRaw, true);
if (is_array($decodedChain)) {
    foreach ($decodedChain as $row) {
        $service = normalize_short_service($row['service'] ?? $admin_settings['shortlink_service'] ?? 'xlink');
        $token = trim((string) ($row['token'] ?? ''));
        if ($token !== '') {
            $shortlinkChain[] = [
                'service' => $service,
                'token' => $service === 'vuotlink' ? normalize_vuotlink_token($token) : $token,
            ];
        }
    }
}

if (empty($shortlinkChain) && $shortTokenRaw !== '') {
    $service = normalize_short_service($admin_settings['shortlink_service'] ?? 'xlink');
    $shortlinkChain[] = [
        'service' => $service,
        'token' => $service === 'vuotlink' ? normalize_vuotlink_token($shortTokenRaw) : $shortTokenRaw,
    ];
}

if (empty($shortlinkChain)) {
    $shortlinkChain[] = [
        'service' => 'xlink',
        'token' => '',
    ];
}

if (count($shortlinkChain) > 4) {
    $shortlinkChain = array_slice($shortlinkChain, 0, 4);
}

// Đảm bảo số bước đủ lớn để chạy hết chuỗi dịch vụ (nhưng vòng lặp sẽ chỉ chạy theo độ dài chuỗi)
if ($shortSteps < count($shortlinkChain)) {
    $shortSteps = count($shortlinkChain);
}

$shortService = $shortlinkChain[0]['service'];
$shortToken   = $shortlinkChain[0]['token'];

// ==================== KIỂM TRA HỢP ĐỒNG SELLER ====================
$contractError = null;
if (!empty($admin_settings['contract_expired_at'])) {
    $expiredDate = strtotime($admin_settings['contract_expired_at']);
    $now = time();
    
    // Level 1 (Admin) không bao giờ hết hạn
    if ($admin_settings['level'] != 1 && $expiredDate < $now) {
        $contractError = "Hợp đồng của bạn đã hết hạn. Vui lòng liên hệ admin để gia hạn.";
    }
}

if ($contractError) {
    echo "<script>alert('" . addslashes($contractError) . "');window.location.href = window.location.href;</script>";
    exit;
}

// Danh sách game được phép cho GetKey (theo user_games + getkey_games nếu có)
$getkey_games_available = [];
$getkey_game_packages = [];
$selectedGame = isset($_POST['game']) ? trim((string) $_POST['game']) : '';

$allGames = [];
$resGames = $conn->query("SELECT id, name, package FROM games WHERE status = 'active' ORDER BY name ASC");
if ($resGames) {
    while ($g = $resGames->fetch_assoc()) {
        $allGames[] = $g;
    }
}

$adminId = (int) ($admin_settings['id_users'] ?? 0);
$adminLevel = (int) ($admin_settings['level'] ?? 0);
$allowedIds = null;

// Keylevel = 0: free level, toàn bộ game (như admin)
// Keylevel = 1: admin, toàn bộ game
// Keylevel = 2, 3, ...: restricted level, chỉ game được phép
if ($adminId && $adminLevel !== 1 && $adminLevel !== 0) {
    $allowedIds = [];
    $stmt = $conn->prepare("SELECT game_id FROM user_games WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($r && ($row = $r->fetch_assoc())) {
            $allowedIds[] = (int) $row['game_id'];
        }
        $stmt->close();
    }
}

$selectedIds = [];
if ($has_getkey_games && !empty($admin_settings['getkey_games'])) {
    $selectedIds = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $admin_settings['getkey_games'])))));
}

foreach ($allGames as $g) {
    $gid = (int) ($g['id'] ?? 0);
    if ($allowedIds !== null && !in_array($gid, $allowedIds, true)) {
        continue;
    }
    if (!empty($selectedIds) && !in_array($gid, $selectedIds, true)) {
        continue;
    }
    $getkey_games_available[] = $g;
    if (!empty($g['package'])) {
        $getkey_game_packages[] = (string) $g['package'];
    }
}

if ($selectedGame === '' && count($getkey_games_available) === 1) {
    $selectedGame = (string) $getkey_games_available[0]['package'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Nhận Key Miễn Phí</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/natacode.css">
    <style>
        body.getkey-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .getkey-hero {
            padding: 3.5rem 0;
            flex: 1;
        }
        .getkey-card {
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.93);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
        }
        .getkey-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-strong);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 0.75rem;
        }
        .getkey-title {
            font-size: clamp(2rem, 2.5vw, 2.7rem);
            margin-bottom: 0.5rem;
        }
        .getkey-lead {
            color: var(--muted);
        }
        .getkey-search .input-group-text {
            background: var(--surface-muted);
            border-color: var(--border);
        }
        .getkey-game-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            max-height: 260px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
        .getkey-game-item {
            display: inline-flex;
        }
        .getkey-pill {
            border-radius: 999px;
            border-color: var(--border);
            background: var(--surface-muted);
            color: var(--ink);
            padding: 0.45rem 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
        }
        .getkey-pill small {
            font-weight: 500;
        }
        .btn-check:checked + .getkey-pill {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: var(--shadow-sm);
        }
        .btn-check:checked + .getkey-pill small {
            color: rgba(255, 255, 255, 0.7);
        }
        .getkey-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1.5rem;
        }
        .getkey-meta .badge {
            background: var(--surface-muted);
            color: var(--ink);
        }
        .getkey-steps {
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }
        .getkey-steps li {
            margin-bottom: 0.65rem;
            color: var(--muted);
        }
        .getkey-support a {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }
        .getkey-footer {
            padding: 1.5rem 0 2.5rem;
            text-align: center;
            color: var(--muted);
        }
    </style>
</head>
<body class="app-shell getkey-page">
<main class="getkey-hero">
    <div class="container">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <div class="card getkey-card h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="getkey-badge"><i class="fa-solid fa-bolt"></i> Key miễn phí</div>
                        <h1 class="getkey-title">Nhận key miễn phí</h1>
                        <p class="getkey-lead">Chọn game, tạo link, nhận key nhanh gọn trong vài bước.</p>

                        <form action="#" method="post">
                            <?php if (!empty($getkey_games_available)) : ?>
                                <div class="getkey-search input-group input-group-sm mt-4">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="getkey_game_search" placeholder="Tìm game...">
                                </div>
                                <div class="getkey-game-grid mt-3" id="getkey_game_grid">
                                    <?php foreach ($getkey_games_available as $index => $g) : ?>
                                        <?php $pkg = (string) ($g['package'] ?? ''); ?>
                                        <?php $gid = 'getkey_game_' . $index; ?>
                                        <div class="getkey-game-item" data-search="<?= htmlspecialchars(strtolower(($g['name'] ?? '') . ' ' . $pkg), ENT_QUOTES) ?>">
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="game"
                                                   id="<?= htmlspecialchars($gid, ENT_QUOTES) ?>"
                                                   value="<?= htmlspecialchars($pkg, ENT_QUOTES) ?>"
                                                   <?= ($selectedGame === $pkg) ? 'checked' : '' ?>
                                                   required>
                                            <label class="btn btn-outline-secondary getkey-pill" for="<?= htmlspecialchars($gid, ENT_QUOTES) ?>">
                                                <?= htmlspecialchars($g['name'] ?? $pkg, ENT_QUOTES) ?>
                                                <small class="text-muted">— <?= htmlspecialchars($pkg, ENT_QUOTES) ?></small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="alert alert-warning mt-3">
                                    Chưa được cấp game nào. Liên hệ Admin/Seller!
                                </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" id="submitButton" name="action" class="btn btn-primary btn-lg">
                                    Nhận key miễn phí
                                </button>
                                <a href="<?= htmlspecialchars($buyIbLink ?: $telegramLink, ENT_QUOTES) ?>" class="btn btn-outline-dark" target="_blank">
                                    Mua key (IB Admin)
                                </a>
                                <a href="<?= htmlspecialchars($autoBuyLink ?: $buyLink, ENT_QUOTES) ?>" class="btn btn-outline-primary">
                                    Mua key (Tự động)
                                </a>
                            </div>
                        </form>

                        <div class="getkey-meta">
                            <span class="badge">Số lần vượt link: <?= (int) $shortSteps ?> lần</span>
                            <span class="badge">Thời hạn key: 1 ngày</span>
                            <span class="badge">Thiết bị: 1</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card getkey-card h-100">
                    <div class="card-body p-4 p-lg-5">
                        <h5 class="mb-3">Hướng dẫn nhanh</h5>
                        <ol class="getkey-steps">
                            <li>Chọn đúng game bạn muốn nhận key.</li>
                            <li>Nhấn nút "Nhận key miễn phí".</li>
                            <li>Vượt link theo số bước quy định.</li>
                            <li>Key sẽ hiện ra tại trang kích hoạt.</li>
                        </ol>
                        <div class="getkey-support">
                            <a href="<?= htmlspecialchars($supportTeleLink ?: $telegramLink, ENT_QUOTES) ?>" target="_blank">
                                <i class="fa-brands fa-telegram"></i> Hỗ trợ nhanh qua Telegram
                            </a>
                        </div>
                        <div class="mt-4 text-muted small">
                            Nếu gặp lỗi, hãy chụp màn hình và gửi về admin để được xử lý nhanh.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="getkey-footer">
    Powered by <a href="<?= htmlspecialchars($telegramLink, ENT_QUOTES) ?>" target="_blank">Light</a>
</footer>

<script>
(function() {
    var search = document.getElementById("getkey_game_search");
    var items = document.querySelectorAll(".getkey-game-item");
    if (!search || items.length === 0) {
        return;
    }
    search.addEventListener("input", function() {
        var term = search.value.trim().toLowerCase();
        items.forEach(function(item) {
            var haystack = item.getAttribute("data-search") || "";
            var match = term === "" || haystack.indexOf(term) !== -1;
            item.classList.toggle("d-none", !match);
        });
    });
})();
</script>

<?php
// ===================== XỬ LÝ TẠO KEY =====================
if(isset($_POST['action'])) {

    $selectedGamePost = isset($_POST['game']) ? trim((string) $_POST['game']) : '';
    if (empty($getkey_game_packages)) {
        echo "<script>alert('Chưa có game nào được cấp/bật cho GetKey!');window.location.href = window.location.href;</script>";
        exit;
    }
    if ($selectedGamePost === '' || !in_array($selectedGamePost, $getkey_game_packages, true)) {
        echo "<script>alert('Vui lòng chọn game hợp lệ!');window.location.href = window.location.href;</script>";
        exit;
    }

    $randomString = "FreeKey_" . substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6);
    $payload = $randomString;
    if ($selectedGamePost !== '') {
        $payload .= '|' . $selectedGamePost;
    }
    $StartDate= date('Y-m-d H:i');
    $dias = 1;

    // đọc key theo ngày
    $currentDate = date("d");
    $file = fopen('keyenc.txt', 'r');

    if ($file) {
        $lineNumber = 1;
        while (($line = fgets($file)) !== false) {
            if ($lineNumber == $currentDate) {
                $key = trim($line);
                break;
            }
            $lineNumber++;
        }
        fclose($file);
    }

    if (empty($key)) {
        echo "<script>alert('Lỗi hệ thống: không đọc được keyenc.txt theo ngày!');window.location.href = window.location.href;</script>";
        exit;
    }

    define('AES_CBC', 'aes-128-cbc');

    function enc($key,$iv,$data){
        return bin2hex(openssl_encrypt(base64_encode($data), AES_CBC, $key, 0, $iv));
    }

    $iv = 'fedcba9876543210'; 
    $key_real = enc($key,$iv,$payload);

    // Link keyactived + truyền admin (nếu có) để lưu registrator + token riêng
    $params = [
        'activekey' => $key_real,
    ];
    if (!empty($admin_username)) {
        $params['admin'] = $admin_username;
    }
    if (!empty($selectedGamePost)) {
        $params['game'] = $selectedGamePost;
    }
    $baseUrl = current_base_url();
    if ($baseUrl === '') {
        $baseUrl = 'https://lamdovn.fun';
    }
    $linkgoc = rtrim($baseUrl, '/') . '/keyactived.php?' . http_build_query($params);

    // Rút link nhiều bước (getkey_steps) theo cấu hình, dùng token riêng từng admin/seller
    $final = $linkgoc;
    $successfulShortens = 0;
    $stepsToRun = $shortSteps;
    $chainCount = max(1, count($shortlinkChain));

    for ($i = 0; $i < $stepsToRun; $i++) {
        $chainIndex = $i % $chainCount;
        $currentService = $shortlinkChain[$chainIndex]['service'] ?? 'xlink';
        $currentToken = $shortlinkChain[$chainIndex]['token'] ?? '';

        if ($currentService === 'yeumoney') {
            $shortened = shorten_with_yeumoney($final, $currentToken);
            if (!$shortened) {
                $shortened = shorten_with_xlink($final, $currentToken);
            }
        } elseif ($currentService === 'linkx') {
            $shortened = shorten_with_linkx($final, $currentToken);
            if (!$shortened) {
                $shortened = shorten_with_xlink($final, $currentToken);
            }
        } elseif ($currentService === 'sieuthiapi') {
            $shortened = shorten_with_sieuthiapi($final, $currentToken);
            if (!$shortened) {
                $shortened = shorten_with_xlink($final, $currentToken);
            }
        } elseif ($currentService === 'funlink') {
            $shortened = shorten_with_funlink($final, $currentToken);
            if (!$shortened) {
                $shortened = shorten_with_xlink($final, $currentToken);
            }
        } elseif ($currentService === 'layma') {
            $shortened = shorten_with_layma($final, $currentToken);
            if (!$shortened) {
                $shortened = shorten_with_xlink($final, $currentToken);
            }
        } elseif ($currentService === 'just2earn') {
            $shortened = shorten_with_just2earn($final, $currentToken);
            if (!$shortened) {
                error_log("Just2Earn failed, falling back to xlink. Token: " . substr($currentToken, 0, 10) . "...");
                $shortened = shorten_with_xlink($final, $currentToken);
            } else {
                error_log("Just2Earn success: $shortened");
            }
        } elseif ($currentService === 'vuotlink') {
            $shortened = shorten_with_vuotlink($final, $currentToken);
        } elseif ($currentService === 'nhapma') {
            $shortened = shorten_with_nhapma($final, $currentToken);
            if (!$shortened) {
                $shortened = shorten_with_xlink($final, $currentToken);
            }
        } else {
            $shortened = shorten_with_xlink($final, $currentToken);
        }

        if ($shortened) {
            $final = $shortened;
            $successfulShortens++;
        }
    }

    // Kiểm tra xem đã rút gọn đủ bước hay chưa
    if ($successfulShortens < $stepsToRun) {
        echo "<script>alert('Lỗi rút gọn link: chỉ thành công " . $successfulShortens . "/" . $stepsToRun . " bước. Vui lòng kiểm tra API token và thử lại.');window.location.href = window.location.href;</script>";
        exit;
    }
?>
<script>
alert("Vui lòng đọc hướng dẫn vượt link để lấy key!");
window.location.href = "<?php echo $final ?>";
</script>
<?php } ?>

</body>
</html>
