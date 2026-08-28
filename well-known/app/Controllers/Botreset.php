<?php

namespace App\Controllers;

/**
 * Botreset Controller for RapidCore
 * CodeIgniter 4 Controller replacing standalone botreset.php script
 */
class Botreset extends BaseController
{
    private $BOT_TOKEN = "8355969257:AAE6CmDdc0PPO1ik_ybXF5OhZH0IvmCVtg0";
    private $OWNER_ID = "8798904721";

    private $USERS_FILE;
    private $STATE_FILE;
    private $LOG_FILE;
    private $DEBUG_LOGGING = true;

    public function __construct()
    {
        $this->USERS_FILE = FCPATH . 'bot_users.txt';
        $this->STATE_FILE = FCPATH . 'bot_state.json';
        $this->LOG_FILE   = FCPATH . 'bot_debug.log';
    }

    public function index()
    {
        // 1. WEBHOOK SETUP (?setup=1)
        if ($this->request->getGet('setup')) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'] ?? 'rapidcore.fun';
            $uri = $_SERVER['REQUEST_URI'] ?? '/botreset';
            $clean_uri = strtok($uri, '?');
            $webhook_url = $protocol . $host . $clean_uri;

            if (strpos($webhook_url, 'localhost') !== false || strpos($webhook_url, '127.0.0.1') !== false) {
                return $this->response->setJSON([
                    "ok" => false,
                    "error_code" => 400,
                    "description" => "Webhook must be a public HTTPS URL. Telegram does not support local URLs."
                ]);
            }

            $res = $this->setWebhook($this->BOT_TOKEN, $webhook_url, true);
            return $this->response->setJSON($res);
        }

        // 2. PARSE TELEGRAM UPDATE
        $update = $this->request->getJSON(true);
        if (!$update) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $update = json_decode($rawInput, true);
            }
        }

        if ($update) {
            try {
                $this->processUpdate($this->BOT_TOKEN, $update);
            } catch (\Throwable $e) {
                $errText = "⚠️ <b>Bot Webhook Error:</b>\n"
                         . "Message: " . htmlspecialchars($e->getMessage()) . "\n"
                         . "File: " . htmlspecialchars($e->getFile()) . "\n"
                         . "Line: " . htmlspecialchars($e->getLine());
                $this->sendMessage($this->BOT_TOKEN, $this->OWNER_ID, $errText);
            }
            return $this->response->setStatusCode(200)->setBody('OK');
        }

        // 3. LANDING PAGE
        $host = $_SERVER['HTTP_HOST'] ?? 'rapidcore.fun';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/botreset';
        $cleanUri = strtok($uri, '?');
        $setupUrl = "https://" . htmlspecialchars($host . $cleanUri) . "?setup=1";

        $html = "<html><head><title>Telegram Key Reset Bot</title>"
              . "<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#0f172a;color:#cbd5e1;}"
              . ".card{background:#1e293b;padding:40px;border-radius:12px;display:inline-block;box-shadow:0 10px 15px -3px rgba(0,0,0,0.3);max-width:550px;}"
              . "h1{color:#38bdf8;} a{color:#38bdf8;text-decoration:none;font-weight:bold;} code{background:#334155;padding:2px 6px;border-radius:4px;color:#f472b6;}</style></head><body>"
              . "<div class='card'><h1>🤖 Telegram Key Reset Bot</h1>"
              . "<p>To register webhook, visit: <br><code><a href='?setup=1'>{$setupUrl}</a></code></p>"
              . "</div></body></html>";

        return $this->response->setStatusCode(200)->setBody($html);
    }

    private function getLevel($level = 0)
    {
        $l = [
            1 => 'Owner',
            2 => 'Admin',
            3 => 'Seller',
        ];
        return array_key_exists($level, $l) ? $l[$level] : 'User';
    }

    private function processUpdate($token, $update)
    {
        if ($this->DEBUG_LOGGING) {
            file_put_contents($this->LOG_FILE, date('[Y-m-d H:i:s] ') . json_encode($update) . "\n", FILE_APPEND);
        }

        $chatId = null;
        $messageText = null;
        $callbackData = null;
        $callbackId = null;
        $from = null;

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $messageText = isset($update['message']['text']) ? $update['message']['text'] : null;
            $from = isset($update['message']['from']) ? $update['message']['from'] : null;
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $callbackData = $update['callback_query']['data'];
            $callbackId = $update['callback_query']['id'];
            $from = isset($update['callback_query']['from']) ? $update['callback_query']['from'] : null;
        }

        if (!$chatId) return;

        $this->addUser($chatId, $this->USERS_FILE);

        if ($from && strval($from['id']) !== strval($this->OWNER_ID)) {
            $stateData = $this->getChatState($chatId);
            $state = isset($stateData['state']) ? $stateData['state'] : '';

            $actionDescription = "";
            if ($callbackData !== null) {
                $actionDescription = "Clicked button: <code>" . htmlspecialchars($callbackData) . "</code>";
            } elseif ($messageText !== null) {
                if ($state === 'guest_awaiting_password') {
                    $actionDescription = "Entered Password: <code>[REDACTED]</code>";
                } else {
                    $actionDescription = "Sent message: <code>" . htmlspecialchars($messageText) . "</code>";
                }
            }

            if ($actionDescription !== "") {
                $panelUser = $this->getLoggedInUser($chatId);
                $panelInfo = "";
                if ($panelUser) {
                    $role = $this->getLevel($panelUser['level']);
                    $panelInfo = "\n🔑 <b>Panel Account:</b> <code>" . htmlspecialchars($panelUser['username']) . "</code> ({$role})";
                } else {
                    $panelInfo = "\n🔑 <b>Panel Account:</b> Guest / Unlinked";
                }

                $firstName = isset($from['first_name']) ? $from['first_name'] : '';
                $lastName = isset($from['last_name']) ? $from['last_name'] : '';
                $fullName = trim($firstName . ' ' . $lastName);
                $username = isset($from['username']) ? '@' . $from['username'] : 'None';

                $logMessage = "🔔 <b>New Bot Activity:</b>\n"
                            . "👤 <b>User:</b> " . htmlspecialchars($fullName) . "\n"
                            . "🆔 <b>Telegram ID:</b> <code>" . htmlspecialchars($from['id']) . "</code>\n"
                            . "🏷️ <b>Username:</b> " . htmlspecialchars($username) . $panelInfo . "\n"
                            . "📝 <b>Action:</b> {$actionDescription}";

                $this->sendMessage($token, $this->OWNER_ID, $logMessage);
            }
        }

        $isOwner = (strval($chatId) === strval($this->OWNER_ID));

        if ($isOwner) {
            $loggedInUser = [
                'id_users' => 0,
                'username' => 'owner',
                'fullname' => 'Owner',
                'level' => 1,
                'saldo' => 999999.0,
                'status' => 1,
                'telegram_id' => $this->OWNER_ID
            ];
            $this->handleAdminFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update, $loggedInUser);
        } else {
            $loggedInUser = $this->getLoggedInUser($chatId);
            if ($loggedInUser && $loggedInUser['status'] == 1) {
                $level = (int)$loggedInUser['level'];
                if ($level === 1 || $level === 2) {
                    $this->handleAdminFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update, $loggedInUser);
                } elseif ($level === 3) {
                    $this->handleSellerFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update, $loggedInUser);
                } else {
                    $this->handleGuestFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update);
                }
            } else {
                $this->handleGuestFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update);
            }
        }
    }

    private function sendWelcomeRoleMenu($token, $chatId, $prefix = '')
    {
        $text = ($prefix ? $prefix . "\n\n" : "")
              . "👋 <b>Welcome to RapidCore Bot!</b>\n\n"
              . "Please select your role to continue:";
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👤 User (Reset Key)', 'callback_data' => 'role_user'],
                    ['text' => '💼 Seller (Login)', 'callback_data' => 'role_seller']
                ]
            ]
        ];
        $this->sendMessage($token, $chatId, $text, $keyboard);
    }

    private function sendSellerMenu($token, $chatId, $sellerUser)
    {
        $levelName = $this->getLevel($sellerUser['level']);
        $text = "💼 <b>Reseller Control Panel</b>\n\n"
              . "Welcome back, <b>" . htmlspecialchars($sellerUser['username']) . "</b>!\n"
              . "🎖️ <b>Role:</b> {$levelName}\n"
              . "💰 <b>Balance:</b> $" . htmlspecialchars($sellerUser['saldo']) . "\n\n"
              . "Please select an action:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔑 Generate Key', 'callback_data' => 'seller_gen_key'],
                    ['text' => '🔄 Reset Key', 'callback_data' => 'seller_reset_key']
                ],
                [
                    ['text' => '📊 My Stats', 'callback_data' => 'seller_stats'],
                    ['text' => '❌ Logout', 'callback_data' => 'seller_logout']
                ]
            ]
        ];
        $this->sendMessage($token, $chatId, $text, $keyboard);
    }

    private function sendRoleMenu($token, $chatId, $user)
    {
        $level = (int)$user['level'];
        if ($level === 1 || $level === 2) {
            $levelName = $this->getLevel($level);
            $welcomeAdmin = "👋 <b>Hello " . htmlspecialchars($user['fullname']) . "!</b>\n\n"
                          . "Welcome to the Key Reset Bot admin panel.\n"
                          . "🎖️ <b>Role:</b> {$levelName}\n\n"
                          . "Use the buttons below to broadcast to bot users, generate new keys, or check statistics.\n\n"
                          . "💡 <b>Quick Reset:</b> You can send any key directly in this chat to reset its HWID with <u>admin bypass</u> (unlimited resets).";
            $this->sendMessage($token, $chatId, $welcomeAdmin, $this->getAdminMenuKeyboard($level));
        } else {
            $this->sendSellerMenu($token, $chatId, $user);
        }
    }

    private function getAdminMenuKeyboard($level)
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '📢 Start Broadcast', 'callback_data' => 'admin_broadcast'],
                    ['text' => '🔑 Generate Key', 'callback_data' => 'admin_generate_key']
                ],
                [
                    ['text' => '📊 Bot Users', 'callback_data' => 'admin_bot_stats'],
                    ['text' => '📈 Key/Web Stats', 'callback_data' => 'admin_db_stats']
                ],
                [
                    ['text' => '❌ Logout', 'callback_data' => 'admin_logout']
                ]
            ]
        ];
    }

    private function handleGuestFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update)
    {
        $stateData = $this->getChatState($chatId);
        $state = isset($stateData['state']) ? $stateData['state'] : null;

        if ($messageText === '/cancel' || $messageText === '❌ Cancel' || $callbackData === 'guest_cancel') {
            if ($callbackData) {
                $this->answerCallbackQuery($token, $callbackId);
            }
            $this->setChatState($chatId, []);
            $this->sendWelcomeRoleMenu($token, $chatId, "❌ Operation cancelled.");
            return;
        }

        if ($state === 'guest_awaiting_username') {
            if (empty($messageText)) {
                $this->sendMessage($token, $chatId, "⚠️ Please enter a valid username.");
                return;
            }
            $username = trim($messageText);
            $this->setChatState($chatId, ['state' => 'guest_awaiting_password', 'username' => $username]);
            $this->sendMessage($token, $chatId, "🔑 Please enter your password for <b>" . htmlspecialchars($username) . "</b>:");
            return;
        }

        if ($state === 'guest_awaiting_password') {
            if (empty($messageText)) {
                $this->sendMessage($token, $chatId, "⚠️ Please enter your password.");
                return;
            }
            $inputPassword = trim($messageText);
            $inputUsername = isset($stateData['username']) ? $stateData['username'] : '';
            $this->setChatState($chatId, []);

            $conn = $this->getDbConnection();
            if (!$conn) {
                $this->sendMessage($token, $chatId, "❌ Database connection error. Please try again later.");
                return;
            }

            $stmt = mysqli_prepare($conn, "SELECT password, status, level, telegram_id FROM users WHERE username = ? LIMIT 1");
            if (!$stmt) {
                $this->sendMessage($token, $chatId, "❌ Database error. Please try again.");
                mysqli_close($conn);
                return;
            }

            mysqli_stmt_bind_param($stmt, "s", $inputUsername);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $dbUser = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$dbUser) {
                $this->sendMessage($token, $chatId, "❌ <b>Login Failed!</b>\nInvalid username or password.");
                $this->sendWelcomeRoleMenu($token, $chatId);
                mysqli_close($conn);
                return;
            }

            if ($dbUser['status'] != 1) {
                $this->sendMessage($token, $chatId, "🔒 <b>Account Inactive!</b>\nYour account is disabled.");
                $this->sendWelcomeRoleMenu($token, $chatId);
                mysqli_close($conn);
                return;
            }

            if (!in_array((int)$dbUser['level'], [1, 2, 3])) {
                $this->sendMessage($token, $chatId, "❌ <b>Access Denied!</b>\nYour account level is not authorized for reseller access.");
                $this->sendWelcomeRoleMenu($token, $chatId);
                mysqli_close($conn);
                return;
            }

            $hash = md5("XquxmymXDtWRA66D" . $inputPassword);
            if (!password_verify($hash, $dbUser['password'])) {
                $this->sendMessage($token, $chatId, "❌ <b>Login Failed!</b>\nInvalid username or password.");
                $this->sendWelcomeRoleMenu($token, $chatId);
                mysqli_close($conn);
                return;
            }

            if ($dbUser['telegram_id'] !== null && $dbUser['telegram_id'] !== '' && strval($dbUser['telegram_id']) !== strval($chatId)) {
                $this->sendMessage($token, $chatId, "⚠️ <b>Login Blocked!</b>\nThis account is already linked to another Telegram account.\n\nPlease log out/unlink from the other Telegram account first, or contact support.");
                $this->sendWelcomeRoleMenu($token, $chatId);
                mysqli_close($conn);
                return;
            }

            mysqli_close($conn);
            if ($this->bindTelegramId($inputUsername, $chatId)) {
                $loggedIn = $this->getLoggedInUser($chatId);
                if ($loggedIn) {
                    $this->sendMessage($token, $chatId, "✅ <b>Login Successful!</b>\nWelcome back, <b>" . htmlspecialchars($inputUsername) . "</b>.");
                    $this->sendRoleMenu($token, $chatId, $loggedIn);
                    return;
                }
            }
            $this->sendMessage($token, $chatId, "❌ Failed to link Telegram ID to your account. Please contact support.");
            return;
        }

        if ($state === 'user_awaiting_key') {
            if (!empty($messageText) && strpos($messageText, '/') !== 0) {
                $this->processKeyReset($token, $chatId, $messageText, false);
                return;
            }
        }

        if ($callbackData) {
            $this->answerCallbackQuery($token, $callbackId);

            if ($callbackData === 'role_user') {
                $this->setChatState($chatId, ['state' => 'user_awaiting_key']);
                $welcomeUser = "🤖 <b>RapidCore Key Reset Bot</b>\n\n"
                             . "Welcome! This bot helps you reset the registered device (HWID) for your license keys so you can log in on a new device.\n\n"
                             . "⚡ <b>How to reset:</b>\n"
                             . "Simply send your license key directly in this chat (e.g., <code>KEY-ABCD-1234</code>).\n\n"
                             . "⚠️ <b>Reset Policy:</b>\n"
                             . "• You can reset each key up to <b>2 times per 24 hours</b>.\n"
                             . "• If the key is already reset (no devices registered), you do not need to reset it.\n\n"
                             . "<i>Powered by RapidCore</i>";

                $userMenu = [
                    'inline_keyboard' => [
                        [['text' => '🔙 Back to Menu', 'callback_data' => 'guest_cancel']]
                    ]
                ];
                $this->sendMessage($token, $chatId, $welcomeUser, $userMenu);
                return;
            }

            if ($callbackData === 'role_seller') {
                $this->setChatState($chatId, ['state' => 'guest_awaiting_username']);
                $this->sendMessage($token, $chatId, "💼 <b>Reseller Login</b>\n\nPlease enter your username:", [
                    'keyboard' => [[['text' => '❌ Cancel']]],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ]);
                return;
            }
        }

        if ($messageText === '/start' || $messageText === '/help') {
            $this->setChatState($chatId, []);
            $this->sendWelcomeRoleMenu($token, $chatId);
            return;
        }

        if (!empty($messageText) && strpos($messageText, '/') !== 0) {
            $this->sendWelcomeRoleMenu($token, $chatId, "⚠️ Please select your role first:");
            return;
        }
    }

    private function handleSellerFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update, $sellerUser)
    {
        $stateData = $this->getChatState($chatId);
        $state = isset($stateData['state']) ? $stateData['state'] : null;

        if ($messageText === '/cancel' || $messageText === '❌ Cancel' || $callbackData === 'seller_cancel') {
            if ($callbackData) {
                $this->answerCallbackQuery($token, $callbackId);
            }
            $this->setChatState($chatId, []);
            $this->sendMessage($token, $chatId, "❌ Operation cancelled.");
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        if ($state === 'seller_awaiting_key' && !empty($messageText)) {
            $this->setChatState($chatId, []);
            $this->processSellerKeyReset($token, $chatId, $messageText, $sellerUser);
            return;
        }

        if ($callbackData) {
            $this->answerCallbackQuery($token, $callbackId);

            if ($callbackData === 'seller_gen_key') {
                $minPrice = 0.5;
                if ((float)$sellerUser['saldo'] < $minPrice) {
                    $this->sendMessage($token, $chatId, "❌ <b>Insufficient Balance!</b>\nYour current balance is <b>$" . $sellerUser['saldo'] . "</b>, but the minimum required balance to generate a key is <b>$" . $minPrice . "</b>.");
                    $this->sendSellerMenu($token, $chatId, $sellerUser);
                    return;
                }

                $this->setChatState($chatId, ['state' => 'seller_gen_awaiting_duration']);
                $durationMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '1 Day ($0.5)', 'callback_data' => 'seller_gen_dur:24'],
                            ['text' => '7 Days ($1.0)', 'callback_data' => 'seller_gen_dur:168']
                        ],
                        [
                            ['text' => '14 Days ($2.0)', 'callback_data' => 'seller_gen_dur:336'],
                            ['text' => '30 Days ($4.0)', 'callback_data' => 'seller_gen_dur:720']
                        ],
                        [
                            ['text' => '❌ Cancel', 'callback_data' => 'seller_cancel']
                        ]
                    ]
                ];
                $this->sendMessage($token, $chatId, "🔑 <b>Generate Key</b>\n\n<b>Game:</b> <code>Freefire (Rapid Core)</code>\n\nPlease select the key duration:", $durationMarkup);
                return;
            }

            if (strpos($callbackData, 'seller_gen_dur:') === 0) {
                $duration = (int)substr($callbackData, 15);
                $this->setChatState($chatId, []);

                $prices = [
                    24 => 0.5,
                    168 => 1.0,
                    336 => 2.0,
                    720 => 4.0
                ];

                $price = isset($prices[$duration]) ? $prices[$duration] : 0.5;

                $conn = $this->getDbConnection();
                if ($conn) {
                    $stmt = mysqli_prepare($conn, "SELECT saldo, username, id_users FROM users WHERE id_users = ?");
                    mysqli_stmt_bind_param($stmt, "i", $sellerUser['id_users']);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $dbUser = mysqli_fetch_assoc($res);
                    mysqli_stmt_close($stmt);

                    if (!$dbUser) {
                        $this->sendMessage($token, $chatId, "❌ Failed to verify user account.");
                        mysqli_close($conn);
                        return;
                    }

                    $currentSaldo = (float)$dbUser['saldo'];
                    if ($currentSaldo < $price) {
                        $this->sendMessage($token, $chatId, "❌ <b>Insufficient Balance!</b>\nRequired: <b>$" . $price . "</b>, available: <b>$" . $currentSaldo . "</b>.");
                        mysqli_close($conn);
                        return;
                    }

                    $newSaldo = $currentSaldo - $price;
                    $stmtDeduct = mysqli_prepare($conn, "UPDATE users SET saldo = ? WHERE id_users = ?");
                    mysqli_stmt_bind_param($stmtDeduct, "di", $newSaldo, $sellerUser['id_users']);
                    $deductSuccess = mysqli_stmt_execute($stmtDeduct);
                    mysqli_stmt_close($stmtDeduct);

                    if (!$deductSuccess) {
                        $this->sendMessage($token, $chatId, "❌ Failed to process balance deduction.");
                        mysqli_close($conn);
                        return;
                    }

                    $prefix = "KEY";
                    $license = strtoupper($prefix . "-" . bin2hex(random_bytes(4)));

                    $game = 'Freefire';
                    $max_devices = 1;
                    $status = 1;
                    $registrator = $dbUser['username'];

                    $stmtInsert = mysqli_prepare($conn, "INSERT INTO keys_code (game, user_key, duration, max_devices, status, registrator) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmtInsert, "ssiiis", $game, $license, $duration, $max_devices, $status, $registrator);

                    if (mysqli_stmt_execute($stmtInsert)) {
                        $newKeyId = mysqli_insert_id($conn);
                        mysqli_stmt_close($stmtInsert);

                        $info = "{$game}|" . substr($license, 0, 8) . "|{$duration}|{$max_devices}";
                        $user_do = $registrator;
                        $created_at = date('Y-m-d H:i:s');
                        $updated_at = date('Y-m-d H:i:s');

                        $stmtLog = mysqli_prepare($conn, "INSERT INTO history (keys_id, user_do, info, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
                        $keys_id = strval($newKeyId);
                        mysqli_stmt_bind_param($stmtLog, "sssss", $keys_id, $user_do, $info, $created_at, $updated_at);
                        mysqli_stmt_execute($stmtLog);
                        mysqli_stmt_close($stmtLog);

                        $dayText = ($duration >= 24) ? ($duration / 24) . " Days" : $duration . " Hours";
                        $successGenMsg = "✅ <b>Key Generated Successfully!</b>\n\n"
                                       . "🔑 <b>Key:</b> <code>{$license}</code>\n"
                                       . "🎮 <b>Game:</b> {$game} (Rapid Core)\n"
                                       . "⏳ <b>Duration:</b> {$dayText} ({$duration} hours)\n"
                                       . "👤 <b>Registrator:</b> {$registrator}\n"
                                       . "💸 <b>Cost:</b> ${price}\n"
                                       . "💰 <b>New Balance:</b> ${newSaldo}\n\n"
                                       . "<i>Tip: Click the key to copy it!</i>";

                        $sellerUser['saldo'] = $newSaldo;
                        $this->sendMessage($token, $chatId, $successGenMsg);
                        $this->sendSellerMenu($token, $chatId, $sellerUser);
                    } else {
                        $stmtRefund = mysqli_prepare($conn, "UPDATE users SET saldo = ? WHERE id_users = ?");
                        mysqli_stmt_bind_param($stmtRefund, "di", $currentSaldo, $sellerUser['id_users']);
                        mysqli_stmt_execute($stmtRefund);
                        mysqli_stmt_close($stmtRefund);

                        $this->sendMessage($token, $chatId, "❌ Failed to save generated key in database. Balance refunded.");
                        $this->sendSellerMenu($token, $chatId, $sellerUser);
                    }
                    mysqli_close($conn);
                } else {
                    $this->sendMessage($token, $chatId, "❌ Database connection error during key generation.");
                    $this->sendSellerMenu($token, $chatId, $sellerUser);
                }
                return;
            }

            if ($callbackData === 'seller_reset_key') {
                $this->setChatState($chatId, ['state' => 'seller_awaiting_key']);
                $this->sendMessage($token, $chatId, "🔄 <b>Reset Key HWID</b>\n\nPlease enter the license key you want to reset:", [
                    'keyboard' => [[['text' => '❌ Cancel']]],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ]);
                return;
            }

            if ($callbackData === 'seller_stats') {
                $conn = $this->getDbConnection();
                if ($conn) {
                    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM keys_code WHERE registrator = ?");
                    mysqli_stmt_bind_param($stmt, "s", $sellerUser['username']);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($res);
                    $keysCount = $row['cnt'] ?? 0;
                    mysqli_stmt_close($stmt);

                    $stmtActive = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM keys_code WHERE registrator = ? AND devices IS NOT NULL");
                    mysqli_stmt_bind_param($stmtActive, "s", $sellerUser['username']);
                    mysqli_stmt_execute($stmtActive);
                    $resActive = mysqli_stmt_get_result($stmtActive);
                    $rowActive = mysqli_fetch_assoc($resActive);
                    $activeKeysCount = $rowActive['cnt'] ?? 0;
                    mysqli_stmt_close($stmtActive);

                    mysqli_close($conn);

                    $levelName = $this->getLevel($sellerUser['level']);
                    $statsMsg = "📊 <b>Seller Account Stats</b>\n\n"
                              . "👤 <b>Username:</b> {$sellerUser['username']}\n"
                              . "🎖️ <b>Level:</b> {$levelName}\n"
                              . "💰 <b>Balance:</b> ${sellerUser['saldo']}\n"
                              . "🔑 <b>Your Total Keys:</b> {$keysCount}\n"
                              . "🟢 <b>Active Keys (Linked):</b> {$activeKeysCount}\n"
                              . "⚪ <b>Unused Keys:</b> " . ($keysCount - $activeKeysCount);

                    $this->sendMessage($token, $chatId, $statsMsg);
                    $this->sendSellerMenu($token, $chatId, $sellerUser);
                } else {
                    $this->sendMessage($token, $chatId, "❌ Failed to retrieve stats due to database issue.");
                    $this->sendSellerMenu($token, $chatId, $sellerUser);
                }
                return;
            }

            if ($callbackData === 'seller_logout') {
                $this->unbindTelegramId($chatId);
                $this->sendMessage($token, $chatId, "👋 <b>Logged Out Successfully!</b>\nYour Telegram account is now unlinked from the panel.");
                $this->sendWelcomeRoleMenu($token, $chatId);
                return;
            }
        }

        if ($messageText === '/start' || $messageText === '/help' || empty($state)) {
            $this->setChatState($chatId, []);
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        if (!empty($messageText) && strpos($messageText, '/') !== 0) {
            $this->sendMessage($token, $chatId, "⚠️ Please use the buttons below to interact with the panel.");
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }
    }

    private function processKeyReset($token, $chatId, $uKey, $isOwnerBypass)
    {
        $uKey = trim($uKey);

        if (strpos(strtolower($uKey), '/reset') === 0) {
            $uKey = trim(substr($uKey, 6));
        }

        if (empty($uKey)) {
            $this->sendMessage($token, $chatId, "⚠️ Please enter a valid license key.");
            return;
        }

        $conn = $this->getDbConnection();
        if (!$conn) {
            $this->sendMessage($token, $chatId, "❌ Database connection error. Please try again later.");
            return;
        }

        $stmt = mysqli_prepare($conn, "SELECT id_keys, user_key, devices, status, game, registrator, expired_date FROM keys_code WHERE user_key = ?");
        mysqli_stmt_bind_param($stmt, "s", $uKey);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $keyData = mysqli_fetch_assoc($result);

        if (!$keyData) {
            $this->sendMessage($token, $chatId, "❌ <b>Key Not Found!</b>\n\nThe key you entered is not registered in our system. Please check for spelling mistakes and try again.");
            mysqli_close($conn);
            return;
        }

        if ($keyData['status'] != 1) {
            $this->sendMessage($token, $chatId, "🔒 <b>Key Locked!</b>\n\nThis key is currently disabled or locked. Please contact your reseller or administrator.");
            mysqli_close($conn);
            return;
        }

        if (!empty($keyData['expired_date']) && $keyData['expired_date'] !== '0000-00-00 00:00:00') {
            if (strtotime($keyData['expired_date']) < time()) {
                $this->sendMessage($token, $chatId, "⚠️ <b>Key Expired!</b>\n\nThis key expired on: <code>" . htmlspecialchars($keyData['expired_date']) . "</code>.\nResetting is not allowed for expired keys.");
                mysqli_close($conn);
                return;
            }
        }

        if (empty($keyData['devices'])) {
            $this->sendMessage($token, $chatId, "ℹ️ <b>No Devices Registered</b>\n\nThis key currently has no active device registrations. You can directly use it to log in on your device!");
            mysqli_close($conn);
            return;
        }

        if (!$isOwnerBypass) {
            $timeLimit = date('Y-m-d H:i:s', time() - 86400);
            $stmtCount = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM history WHERE keys_id = ? AND created_at >= ? AND (info LIKE '%HWID Reset%' OR info LIKE '%KEY_RESET_API%')");
            $keys_id = strval($keyData['id_keys']);
            mysqli_stmt_bind_param($stmtCount, "ss", $keys_id, $timeLimit);
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
            $rowCount = mysqli_fetch_assoc($resultCount);
            $resetCount = $rowCount['cnt'] ?? 0;

            if ($resetCount >= 2) {
                $this->sendMessage($token, $chatId, "⚠️ <b>Limit Reached!</b>\n\nYou can only reset this key twice in a 24-hour period. Please wait before trying again.");
                mysqli_close($conn);
                return;
            }
        } else {
            $resetCount = 0;
        }

        $stmtUpdate = mysqli_prepare($conn, "UPDATE keys_code SET devices = NULL WHERE id_keys = ?");
        mysqli_stmt_bind_param($stmtUpdate, "i", $keyData['id_keys']);
        $updateSuccess = mysqli_stmt_execute($stmtUpdate);

        if ($updateSuccess) {
            $actor = $isOwnerBypass ? "Admin Bot (Owner)" : "Telegram Bot";
            $user_do = $actor . " (" . $chatId . ")";
            $info = "HWID Reset via Telegram|" . $uKey;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            $keys_id = strval($keyData['id_keys']);

            $stmtLog = mysqli_prepare($conn, "INSERT INTO history (keys_id, user_do, info, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtLog, "sssss", $keys_id, $user_do, $info, $created_at, $updated_at);
            mysqli_stmt_execute($stmtLog);

            $badge = $isOwnerBypass ? "⭐ <b>Admin Bypass Active</b>\n" : "";
            $remainingResets = $isOwnerBypass ? "∞" : (2 - ($resetCount + 1));

            $successMsg = "✅ <b>HWID Reset Successfully!</b>\n\n"
                        . $badge
                        . "🔑 <b>Key:</b> <code>" . htmlspecialchars($uKey) . "</code>\n"
                        . "🎮 <b>Game:</b> " . htmlspecialchars($keyData['game']) . " (Rapid Core)\n"
                        . "👤 <b>Reseller:</b> " . htmlspecialchars($keyData['registrator'] ?: 'System') . "\n"
                        . "🔄 <b>Remaining Resets Today:</b> {$remainingResets}/2\n\n"
                        . "You can now log in using this key on your new device!";
            $this->sendMessage($token, $chatId, $successMsg);
        } else {
            $this->sendMessage($token, $chatId, "❌ Failed to reset key. Please try again later or contact support.");
        }

        mysqli_close($conn);
    }

    private function handleAdminFlow($token, $chatId, $messageText, $callbackData, $callbackId, $update, $adminUser)
    {
        $adminData = $this->getChatState($chatId);
        $state = isset($adminData['state']) ? $adminData['state'] : null;

        if ($messageText === '/cancel' || $messageText === '❌ Cancel') {
            $this->setChatState($chatId, []);
            $this->sendMessage($token, $chatId, "❌ Operation cancelled.", $this->getAdminMenuKeyboard($adminUser['level']));
            return;
        }

        if ($state === 'awaiting_broadcast' && !empty($messageText)) {
            $this->setChatState($chatId, []);

            $users = $this->getUsers($this->USERS_FILE);
            $totalUsers = count($users);

            if ($totalUsers === 0) {
                $this->sendMessage($token, $chatId, "⚠️ No users found in the database to broadcast to.", $this->getAdminMenuKeyboard($adminUser['level']));
                return;
            }

            $this->sendMessage($token, $chatId, "🚀 Starting broadcast to {$totalUsers} users. Please wait...", $this->getAdminMenuKeyboard($adminUser['level']));

            $success = 0;
            $failed = 0;

            foreach ($users as $user) {
                $user = trim($user);
                if (empty($user)) continue;

                $res = null;
                if (isset($update['message']['photo'])) {
                    $photo = end($update['message']['photo']);
                    $caption = isset($update['message']['caption']) ? $update['message']['caption'] : null;
                    $res = $this->sendPhoto($token, $user, $photo['file_id'], $caption);
                } elseif (isset($update['message']['video'])) {
                    $caption = isset($update['message']['caption']) ? $update['message']['caption'] : null;
                    $res = $this->sendVideo($token, $user, $update['message']['video']['file_id'], $caption);
                } elseif (isset($update['message']['document'])) {
                    $caption = isset($update['message']['caption']) ? $update['message']['caption'] : null;
                    $res = $this->sendDocument($token, $user, $update['message']['document']['file_id'], $caption);
                } elseif (isset($update['message']['audio'])) {
                    $caption = isset($update['message']['caption']) ? $update['message']['caption'] : null;
                    $res = $this->sendAudio($token, $user, $update['message']['audio']['file_id'], $caption);
                } elseif (isset($update['message']['voice'])) {
                    $caption = isset($update['message']['caption']) ? $update['message']['caption'] : null;
                    $res = $this->sendVoice($token, $user, $update['message']['voice']['file_id'], $caption);
                } elseif (isset($update['message']['animation'])) {
                    $caption = isset($update['message']['caption']) ? $update['message']['caption'] : null;
                    $res = $this->sendAnimation($token, $user, $update['message']['animation']['file_id'], $caption);
                } elseif (isset($update['message']['sticker'])) {
                    $res = $this->sendSticker($token, $user, $update['message']['sticker']['file_id']);
                } elseif (isset($update['message']['text'])) {
                    $res = $this->sendMessage($token, $user, $update['message']['text']);
                }

                if ($res && isset($res['ok']) && $res['ok'] === true) {
                    $success++;
                } else {
                    $failed++;
                }

                usleep(50000);
            }

            $summary = "✅ <b>Broadcast Completed!</b>\n\n"
                     . "👥 <b>Total Users:</b> {$totalUsers}\n"
                     . "✔️ <b>Successfully Sent:</b> {$success}\n"
                     . "❌ <b>Failed/Blocked:</b> {$failed}";

            $this->sendMessage($token, $chatId, $summary, $this->getAdminMenuKeyboard($adminUser['level']));
            return;
        }

        if ($callbackData) {
            $this->answerCallbackQuery($token, $callbackId);

            if ($callbackData === 'admin_broadcast') {
                $this->setChatState($chatId, ['state' => 'awaiting_broadcast']);
                $this->sendMessage($token, $chatId, "📢 <b>Broadcast Mode Enabled</b>\n\n"
                                           . "Please send the message you want to broadcast.\n"
                                           . "Supports: Text, Photo, Video, Document, Sticker, GIFs, etc.\n\n"
                                           . "Send <code>/cancel</code> or press <b>Cancel</b> to abort.", [
                                               'keyboard' => [[['text' => '❌ Cancel']]],
                                               'resize_keyboard' => true,
                                               'one_time_keyboard' => true
                                            ]);
                return;
            }

            if ($callbackData === 'admin_generate_key') {
                $this->setChatState($chatId, ['state' => 'gen_awaiting_duration', 'game' => 'Freefire']);

                $durationMarkup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '1 Day (24h)', 'callback_data' => 'gen_dur:24'],
                            ['text' => '7 Days (168h)', 'callback_data' => 'gen_dur:168']
                        ],
                        [
                            ['text' => '14 Days (336h)', 'callback_data' => 'gen_dur:336'],
                            ['text' => '30 Days (720h)', 'callback_data' => 'gen_dur:720']
                        ],
                        [
                            ['text' => '❌ Cancel', 'callback_data' => 'gen_cancel']
                        ]
                    ]
                ];
                $this->sendMessage($token, $chatId, "🔑 <b>Generate Key</b>\n\n<b>Game:</b> <code>Freefire (Rapid Core)</code>\n\nPlease select the key duration:", $durationMarkup);
                return;
            }

            if (strpos($callbackData, 'gen_dur:') === 0) {
                $duration = (int)substr($callbackData, 8);
                $game = isset($adminData['game']) ? $adminData['game'] : 'Freefire';
                $this->setChatState($chatId, []);

                $prefix = "RAPID";
                $license = strtoupper($prefix . "-" . bin2hex(random_bytes(4)));

                $conn = $this->getDbConnection();
                if ($conn) {
                    $max_devices = 1;
                    $status = 1;
                    $registrator = $adminUser['username'];

                    $stmtInsert = mysqli_prepare($conn, "INSERT INTO keys_code (game, user_key, duration, max_devices, status, registrator) VALUES (?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmtInsert, "ssiiis", $game, $license, $duration, $max_devices, $status, $registrator);

                    if (mysqli_stmt_execute($stmtInsert)) {
                        $newKeyId = mysqli_insert_id($conn);
                        mysqli_stmt_close($stmtInsert);

                        $info = "{$game}|" . substr($license, 0, 8) . "|{$duration}|{$max_devices}";
                        $user_do = $registrator;
                        $created_at = date('Y-m-d H:i:s');
                        $updated_at = date('Y-m-d H:i:s');

                        $stmtLog = mysqli_prepare($conn, "INSERT INTO history (keys_id, user_do, info, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
                        $keys_id = strval($newKeyId);
                        mysqli_stmt_bind_param($stmtLog, "sssss", $keys_id, $user_do, $info, $created_at, $updated_at);
                        mysqli_stmt_execute($stmtLog);
                        mysqli_stmt_close($stmtLog);

                        $dayText = ($duration >= 24) ? ($duration / 24) . " Days" : $duration . " Hours";
                        $successGenMsg = "✅ <b>Key Generated Successfully!</b>\n\n"
                                       . "🔑 <b>Key:</b> <code>{$license}</code>\n"
                                       . "🎮 <b>Game:</b> {$game} (Rapid Core)\n"
                                       . "⏳ <b>Duration:</b> {$dayText} ({$duration} hours)\n"
                                       . "👤 <b>Registrator:</b> {$registrator}\n\n"
                                       . "<i>Tip: Click the key to copy it!</i>";

                        $this->sendMessage($token, $chatId, $successGenMsg, $this->getAdminMenuKeyboard($adminUser['level']));
                    } else {
                        $this->sendMessage($token, $chatId, "❌ Failed to save generated key in database.", $this->getAdminMenuKeyboard($adminUser['level']));
                    }
                    mysqli_close($conn);
                } else {
                    $this->sendMessage($token, $chatId, "❌ Database connection error during key generation.", $this->getAdminMenuKeyboard($adminUser['level']));
                }
                return;
            }

            if ($callbackData === 'gen_cancel') {
                $this->setChatState($chatId, []);
                $this->sendMessage($token, $chatId, "❌ Key generation cancelled.", $this->getAdminMenuKeyboard($adminUser['level']));
                return;
            }

            if ($callbackData === 'admin_bot_stats') {
                $users = $this->getUsers($this->USERS_FILE);
                $totalUsers = count($users);

                $msg = "📊 <b>Bot Users Statistics</b>\n\n"
                     . "👥 <b>Total Registered Bot Users:</b> {$totalUsers}\n\n"
                     . "<i>These users will receive broadcasts sent from this menu.</i>";
                $this->sendMessage($token, $chatId, $msg, $this->getAdminMenuKeyboard($adminUser['level']));
                return;
            }

            if ($callbackData === 'admin_db_stats') {
                $conn = $this->getDbConnection();
                if ($conn) {
                    $resKeys = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM keys_code");
                    $rowKeys = mysqli_fetch_assoc($resKeys);
                    $totalKeys = $rowKeys['cnt'] ?? 0;

                    $resActive = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM keys_code WHERE devices IS NOT NULL");
                    $rowActive = mysqli_fetch_assoc($resActive);
                    $activeKeys = $rowActive['cnt'] ?? 0;

                    $resUsers = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users");
                    $rowUsers = mysqli_fetch_assoc($resUsers);
                    $totalPanelUsers = $rowUsers['cnt'] ?? 0;

                    mysqli_close($conn);

                    $msg = "📈 <b>Database & Server Stats</b>\n\n"
                         . "🔑 <b>Total Keys:</b> {$totalKeys}\n"
                         . "🟢 <b>Active Keys (Linked):</b> {$activeKeys}\n"
                         . "⚪ <b>Unused Keys:</b> " . ($totalKeys - $activeKeys) . "\n"
                         . "👤 <b>Panel Accounts:</b> {$totalPanelUsers}";
                    $this->sendMessage($token, $chatId, $msg, $this->getAdminMenuKeyboard($adminUser['level']));
                } else {
                    $this->sendMessage($token, $chatId, "❌ Database error.", $this->getAdminMenuKeyboard($adminUser['level']));
                }
                return;
            }

            if ($callbackData === 'admin_logout') {
                $this->unbindTelegramId($chatId);
                $this->sendMessage($token, $chatId, "👋 <b>Logged Out Successfully!</b>");
                $this->sendWelcomeRoleMenu($token, $chatId);
                return;
            }
        }

        if ($messageText === '/start' || $messageText === '/help' || empty($state)) {
            if (!empty($messageText) && strpos($messageText, '/') !== 0) {
                $this->processKeyReset($token, $chatId, $messageText, true);
                return;
            }
            $this->setChatState($chatId, []);
            $this->sendRoleMenu($token, $chatId, $adminUser);
            return;
        }
    }

    private function processSellerKeyReset($token, $chatId, $uKey, $sellerUser)
    {
        $uKey = trim($uKey);
        if (strpos(strtolower($uKey), '/reset') === 0) {
            $uKey = trim(substr($uKey, 6));
        }

        if (empty($uKey)) {
            $this->sendMessage($token, $chatId, "⚠️ Please enter a valid license key.");
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        $conn = $this->getDbConnection();
        if (!$conn) {
            $this->sendMessage($token, $chatId, "❌ Database connection error.");
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        $stmt = mysqli_prepare($conn, "SELECT id_keys, user_key, devices, status, game, registrator FROM keys_code WHERE user_key = ?");
        mysqli_stmt_bind_param($stmt, "s", $uKey);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $keyData = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$keyData) {
            $this->sendMessage($token, $chatId, "❌ <b>Key Not Found!</b>");
            mysqli_close($conn);
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        if ($sellerUser['level'] == 3 && strtolower($keyData['registrator']) !== strtolower($sellerUser['username'])) {
            $this->sendMessage($token, $chatId, "⛔ <b>Permission Denied!</b>\nYou can only reset keys that were generated by your account.");
            mysqli_close($conn);
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        if (empty($keyData['devices'])) {
            $this->sendMessage($token, $chatId, "ℹ️ <b>No Devices Registered</b>\nThis key has no active device registration.");
            mysqli_close($conn);
            $this->sendSellerMenu($token, $chatId, $sellerUser);
            return;
        }

        $stmtUpdate = mysqli_prepare($conn, "UPDATE keys_code SET devices = NULL WHERE id_keys = ?");
        mysqli_stmt_bind_param($stmtUpdate, "i", $keyData['id_keys']);
        $updateSuccess = mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);

        if ($updateSuccess) {
            $user_do = "Reseller Bot (" . $sellerUser['username'] . ")";
            $info = "HWID Reset via Reseller Bot|" . $uKey;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            $keys_id = strval($keyData['id_keys']);

            $stmtLog = mysqli_prepare($conn, "INSERT INTO history (keys_id, user_do, info, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmtLog, "sssss", $keys_id, $user_do, $info, $created_at, $updated_at);
            mysqli_stmt_execute($stmtLog);
            mysqli_stmt_close($stmtLog);

            $this->sendMessage($token, $chatId, "✅ <b>HWID Reset Successfully!</b>\n\n🔑 <b>Key:</b> <code>{$uKey}</code>");
        } else {
            $this->sendMessage($token, $chatId, "❌ Failed to reset key.");
        }

        mysqli_close($conn);
        $this->sendSellerMenu($token, $chatId, $sellerUser);
    }

    private function addUser($chatId, $usersFile)
    {
        $users = $this->getUsers($usersFile);
        if (!in_array(strval($chatId), $users)) {
            $users[] = strval($chatId);
            file_put_contents($usersFile, implode("\n", $users) . "\n");
        }
    }

    private function getUsers($usersFile)
    {
        if (!file_exists($usersFile)) {
            return [];
        }
        $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_map('trim', $lines);
    }

    private function getChatState($chatId)
    {
        if (!file_exists($this->STATE_FILE)) {
            return [];
        }
        $json = file_get_contents($this->STATE_FILE);
        $data = json_decode($json, true);
        return isset($data[$chatId]) ? $data[$chatId] : [];
    }

    private function setChatState($chatId, $data)
    {
        $allData = [];
        if (file_exists($this->STATE_FILE)) {
            $json = file_get_contents($this->STATE_FILE);
            $allData = json_decode($json, true);
            if (!is_array($allData)) {
                $allData = [];
            }
        }
        if (empty($data)) {
            unset($allData[$chatId]);
        } else {
            $allData[$chatId] = $data;
        }
        file_put_contents($this->STATE_FILE, json_encode($allData));
    }

    private function getLoggedInUser($chatId)
    {
        $conn = $this->getDbConnection();
        if (!$conn) return null;

        $stmt = mysqli_prepare($conn, "SELECT id_users, username, fullname, saldo, level, status, telegram_id FROM users WHERE telegram_id = ? LIMIT 1");
        if (!$stmt) {
            mysqli_close($conn);
            return null;
        }

        $chatIdStr = strval($chatId);
        mysqli_stmt_bind_param($stmt, "s", $chatIdStr);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        return $user;
    }

    private function bindTelegramId($targetUsername, $chatId)
    {
        $conn = $this->getDbConnection();
        if (!$conn) return false;

        $stmtClear = mysqli_prepare($conn, "UPDATE users SET telegram_id = NULL WHERE telegram_id = ?");
        if ($stmtClear) {
            $chatIdStr = strval($chatId);
            mysqli_stmt_bind_param($stmtClear, "s", $chatIdStr);
            mysqli_stmt_execute($stmtClear);
            mysqli_stmt_close($stmtClear);
        }

        $stmt = mysqli_prepare($conn, "UPDATE users SET telegram_id = ? WHERE username = ?");
        if (!$stmt) {
            mysqli_close($conn);
            return false;
        }

        $chatIdStr = strval($chatId);
        mysqli_stmt_bind_param($stmt, "ss", $chatIdStr, $targetUsername);
        $success = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        return $success;
    }

    private function unbindTelegramId($chatId)
    {
        $conn = $this->getDbConnection();
        if (!$conn) return false;

        $stmt = mysqli_prepare($conn, "UPDATE users SET telegram_id = NULL WHERE telegram_id = ?");
        if (!$stmt) {
            mysqli_close($conn);
            return false;
        }

        $chatIdStr = strval($chatId);
        mysqli_stmt_bind_param($stmt, "s", $chatIdStr);
        $success = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        return $success;
    }

    private function getDbConnection()
    {
        if (file_exists(FCPATH . 'conn.php')) {
            include(FCPATH . 'conn.php');
            if (isset($conn) && $conn) {
                return $conn;
            }
        }
        $servername = "localhost";
        $username = "xkynpbah_titoo";
        $password = "xkynpbah_titoo";
        $dbname = "xkynpbah_titoo";

        return mysqli_connect($servername, $username, $password, $dbname);
    }

    private function sendMessage($token, $chatId, $text, $replyMarkup = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $postData = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        if ($replyMarkup) {
            $postData['reply_markup'] = json_encode($replyMarkup);
        }
        return $this->makeRequest($url, $postData);
    }

    private function answerCallbackQuery($token, $callbackId)
    {
        $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";
        return $this->makeRequest($url, ['callback_query_id' => $callbackId]);
    }

    private function sendPhoto($token, $chatId, $photoFileId, $caption = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendPhoto";
        $postData = [
            'chat_id' => $chatId,
            'photo' => $photoFileId
        ];
        if ($caption) {
            $postData['caption'] = $caption;
            $postData['parse_mode'] = 'HTML';
        }
        return $this->makeRequest($url, $postData);
    }

    private function sendVideo($token, $chatId, $videoFileId, $caption = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendVideo";
        $postData = [
            'chat_id' => $chatId,
            'video' => $videoFileId
        ];
        if ($caption) {
            $postData['caption'] = $caption;
            $postData['parse_mode'] = 'HTML';
        }
        return $this->makeRequest($url, $postData);
    }

    private function sendDocument($token, $chatId, $documentFileId, $caption = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendDocument";
        $postData = [
            'chat_id' => $chatId,
            'document' => $documentFileId
        ];
        if ($caption) {
            $postData['caption'] = $caption;
            $postData['parse_mode'] = 'HTML';
        }
        return $this->makeRequest($url, $postData);
    }

    private function sendAudio($token, $chatId, $audioFileId, $caption = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendAudio";
        $postData = [
            'chat_id' => $chatId,
            'audio' => $audioFileId
        ];
        if ($caption) {
            $postData['caption'] = $caption;
            $postData['parse_mode'] = 'HTML';
        }
        return $this->makeRequest($url, $postData);
    }

    private function sendVoice($token, $chatId, $voiceFileId, $caption = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendVoice";
        $postData = [
            'chat_id' => $chatId,
            'voice' => $voiceFileId
        ];
        if ($caption) {
            $postData['caption'] = $caption;
            $postData['parse_mode'] = 'HTML';
        }
        return $this->makeRequest($url, $postData);
    }

    private function sendAnimation($token, $chatId, $animationFileId, $caption = null)
    {
        $url = "https://api.telegram.org/bot{$token}/sendAnimation";
        $postData = [
            'chat_id' => $chatId,
            'animation' => $animationFileId
        ];
        if ($caption) {
            $postData['caption'] = $caption;
            $postData['parse_mode'] = 'HTML';
        }
        return $this->makeRequest($url, $postData);
    }

    private function sendSticker($token, $chatId, $stickerFileId)
    {
        $url = "https://api.telegram.org/bot{$token}/sendSticker";
        $postData = [
            'chat_id' => $chatId,
            'sticker' => $stickerFileId
        ];
        return $this->makeRequest($url, $postData);
    }

    private function setWebhook($token, $url, $dropPending = false)
    {
        $apiUrl = "https://api.telegram.org/bot{$token}/setWebhook";
        $params = ['url' => $url];
        if ($dropPending) {
            $params['drop_pending_updates'] = true;
        }
        return $this->makeRequest($apiUrl, $params);
    }

    private function makeRequest($url, $postData = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
