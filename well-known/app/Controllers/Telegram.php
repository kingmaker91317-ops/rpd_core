<?php

namespace App\Controllers;

use App\Models\KeysModel;

class Telegram extends BaseController
{
    protected $keysModel;

    public function __construct()
    {
        $this->keysModel = new KeysModel();
    }

    public function index()
    {
        $startTime = microtime(true);
        log_message('error', 'Telegram controller called');

        // Telegram sends JSON via webhook
        $request = $this->request->getJSON();

        if (!$request) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No data received']);
        }

        // Log the request for debugging
        log_message('debug', 'Telegram Webhook: ' . json_encode($request));

        $message = $request->message ?? null;
        $callbackQuery = $request->callback_query ?? null;

        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery);
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (!$message) {
            return $this->response->setJSON(['status' => 'ok']); // Not a message, ignore
        }

        $chatId = $message->chat->id ?? null;
        $userId = $message->from->id ?? null;
        $messageId = $message->message_id ?? null;

        if (!$chatId) {
            return $this->response->setJSON(['status' => 'ok']);
        }

        // Save user to database for broadcasting
        $this->saveTelegramUser($chatId);

        // Forward every message (text, photo, video, etc) to Owner
        if ($messageId) {
            $this->forwardToOwner($chatId, $messageId, $message->from);
        }

        $text = $message->text ?? '';
        $caption = $message->caption ?? '';
        $textCmd = $text ? $text : $caption;

        // Handle Broadcast command (Owner only)
        $ownerId = env('OWNER_TELEGRAM_ID');
        if ($chatId == $ownerId && strpos(strtolower($textCmd), '/broadcast') === 0) {
            $this->handleBroadcast($message, $chatId, $textCmd);
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (!$text) {
            // If it's not text (e.g. photo without caption, etc.), we can't process it as a key reset.
            return $this->response->setJSON(['status' => 'ok']);
        }

        // Clean text
        $text = trim($text);

        // Cooldown check
        $cache = \Config\Services::cache();
        $cacheKey = "tg_cooldown_" . $userId;

        if ($cache->get($cacheKey)) {
            $this->sendMessage($chatId, "<tg-emoji emoji-id=\"6215493851193285904\">⏳</tg-emoji> Please wait 30 seconds before sending another request.");
            return $this->response->setJSON(['status' => 'ok']);
        }

        // Commands
        if ($text === '/start' || strtolower($text) === 'start') {
            $this->sendWelcomeMessage($chatId);
            return $this->response->setJSON(['status' => 'ok']);
        }

        if ($text === '/status' || strtolower($text) === 'status') {
            $this->sendStatusMessage($chatId, $userId, $message->from);
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (strpos(strtolower($text), '/check ') === 0 || strpos(strtolower($text), '/info ') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) > 1) {
                $keyToSearch = trim($parts[1]);
                $this->sendKeyInfoMessage($chatId, $keyToSearch);
            } else {
                $this->sendMessage($chatId, "<tg-emoji emoji-id=\"6091404678279470371\">⚠️</tg-emoji> Please provide a key to check. Usage: <code>/check YOUR_KEY</code>");
            }
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (strpos(strtolower($text), '/contact ') === 0) {
            $parts = explode(' ', $text, 2);
            if (count($parts) > 1) {
                $contactMsg = trim($parts[1]);
                $this->sendContactMessage($chatId, $userId, $message->from, $contactMsg);
            } else {
                $this->sendMessage($chatId, "<tg-emoji emoji-id=\"6091404678279470371\">⚠️</tg-emoji> Please provide a message. Usage: <code>/contact Your message here</code>");
            }
            return $this->response->setJSON(['status' => 'ok']);
        }

        // Search for key
        $keyObject = $this->keysModel->getKeys($text);

        if ($keyObject) {
            $this->askForResetConfirmation($chatId, $userId, $message->from, $text, $keyObject);
        } else {
            // Not found
            $this->sendMessage($chatId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> Key `{$text}` not found.");
            $this->notifyOwner(
                "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> <b>FAILED RESET (Not Found)</b>\n" .
                "<tg-emoji emoji-id=\"5402421675515986446\">👤</tg-emoji> <b>User:</b> " . htmlspecialchars($message->from->first_name ?? 'Unknown') . " (@" . htmlspecialchars($message->from->username ?? 'N/A') . ")\n" .
                "<tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> <b>ID:</b> <code>{$userId}</code>\n" .
                "<tg-emoji emoji-id=\"6089037760457354764\">🔑</tg-emoji> <b>Key:</b> <code>" . htmlspecialchars($text) . "</code>"
            );
        }

        return $this->response->setJSON(['status' => 'ok']);
    }

    private function handleCallbackQuery($callbackQuery)
    {
        $userId = $callbackQuery->from->id;
        $chatId = $callbackQuery->message->chat->id;
        $messageId = $callbackQuery->message->message_id;
        $data = $callbackQuery->data;

        $this->saveTelegramUser($chatId);

        if (strpos($data, 'cancel_') === 0) {
            $this->editMessageText($chatId, $messageId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> Reset cancelled by user.");
        } elseif (strpos($data, 'reset_') === 0) {
            $key = substr($data, 6);
            $this->processKeyReset($chatId, $userId, $callbackQuery->from, $key, $messageId);
        }

        $this->answerCallbackQuery($callbackQuery->id);
    }

    private function askForResetConfirmation($chatId, $userId, $from, $key, $keyObject)
    {
        $cache = \Config\Services::cache();
        $cacheKeyUsageForKey = "tg_key_usage_" . md5($key);
        $keyUsage = $cache->get($cacheKeyUsageForKey) ?? 0;

        $db = \Config\Database::connect();
        $isPremium = $db->table('telegram_premium')->where('telegram_id', $userId)->where('expiration_date >', date('Y-m-d H:i:s'))->countAllResults() > 0;

        $keyLimitText = $isPremium ? "Unlimited" : "2";

        $infoText = $this->formatKeyInfoText($key, $keyObject);

        $text = $infoText . "\n\n"
            . "<tg-emoji emoji-id=\"6332186798365612896\">📊</tg-emoji> <b>Key Reset Usage:</b> {$keyUsage} / {$keyLimitText}\n\n"
            . "Are you sure you want to reset this key?";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Reset Key',
                        'callback_data' => 'reset_' . $key,
                        'style' => 'success',
                        'icon_custom_emoji_id' => '6334666613698075229'
                    ]
                ],
                [
                    [
                        'text' => 'Cancel',
                        'callback_data' => 'cancel_' . $key,
                        'style' => 'danger',
                        'icon_custom_emoji_id' => '5210952531676504517'
                    ]
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function processKeyReset($chatId, $userId, $from, $text, $messageId)
    {
        $startTime = microtime(true);
        $keyObject = $this->keysModel->getKeys($text);
        if (!$keyObject) {
            $this->editMessageText($chatId, $messageId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> Key `{$text}` not found.");
            return;
        }

        $cache = \Config\Services::cache();
        $cacheKey = "tg_cooldown_" . $userId;

        if ($cache->get($cacheKey)) {
            $this->editMessageText($chatId, $messageId, "<tg-emoji emoji-id=\"6215493851193285904\">⏳</tg-emoji> Please wait 30 seconds before sending another request.");
            return;
        }

        $db = \Config\Database::connect();
        $premium = $db->table('telegram_premium')
            ->where('telegram_id', $userId)
            ->where('expiration_date >', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();

        $isPremium = ($premium !== null);

        $cacheKeyUsageForKey = "tg_key_usage_" . md5($text);
        $keyUsage = $cache->get($cacheKeyUsageForKey) ?? 0;

        if (!$isPremium) {
            if ($keyUsage >= 2) {
                $this->editMessageText($chatId, $messageId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> This key has already been reset its maximum of 2 times.");
                $this->notifyOwner(
                    "<tg-emoji emoji-id=\"6091404678279470371\">⚠️</tg-emoji> <b>LIMIT REACHED (Key Lifetime)</b>\n" .
                    "<tg-emoji emoji-id=\"5402421675515986446\">👤</tg-emoji> <b>User:</b> " . htmlspecialchars($from->first_name ?? 'Unknown') . " (@" . htmlspecialchars($from->username ?? 'N/A') . ")\n" .
                    "<tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> <b>ID:</b> <code>{$userId}</code>\n" .
                    "<tg-emoji emoji-id=\"6089037760457354764\">🔑</tg-emoji> <b>Key:</b> <code>" . htmlspecialchars($text) . "</code>\n" .
                    "<tg-emoji emoji-id=\"6332186798365612896\">📊</tg-emoji> <b>Key Reset Count:</b> {$keyUsage}"
                );
                return;
            }
        }

        $cacheKeyUsage = "tg_usage_" . $userId;
        $usage = $cache->get($cacheKeyUsage) ?? 0;

        if (!$isPremium) {
            if ($usage >= 2) {
                $this->editMessageText($chatId, $messageId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> You have reached your daily limit of 2 resets.");
                $this->notifyOwner(
                    "<tg-emoji emoji-id=\"6091404678279470371\">⚠️</tg-emoji> <b>LIMIT REACHED (User)</b>\n" .
                    "<tg-emoji emoji-id=\"5402421675515986446\">👤</tg-emoji> <b>User:</b> " . htmlspecialchars($from->first_name ?? 'Unknown') . " (@" . htmlspecialchars($from->username ?? 'N/A') . ")\n" .
                    "<tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> <b>ID:</b> <code>{$userId}</code>\n" .
                    "<tg-emoji emoji-id=\"6089037760457354764\">🔑</tg-emoji> <b>Key:</b> <code>" . htmlspecialchars($text) . "</code>\n" .
                    "<tg-emoji emoji-id=\"6332186798365612896\">📈</tg-emoji> <b>User Daily Usage:</b> {$usage}"
                );
                return;
            }
        }

        // Clear devices
        $this->keysModel->update($keyObject->id_keys, ['devices' => null]);

        // Increment key usage
        $keyUsage++;

        // Calculate TTL
        $now = new \DateTime('now', new \DateTimeZone('Asia/Dhaka'));
        $tomorrow = new \DateTime('tomorrow', new \DateTimeZone('Asia/Dhaka'));
        $ttl = $tomorrow->getTimestamp() - $now->getTimestamp();

        $cache->save($cacheKeyUsageForKey, $keyUsage, 31536000);

        if (!$isPremium) {
            $usage++;
            $cache->save($cacheKeyUsage, $usage, $ttl);
        }

        $cache->save($cacheKey, true, 30);

        $endTime = microtime(true);
        $durationMs = round(($endTime - $startTime) * 1000);

        $nowStr = $now->format('h:i:s A');
        $usageStr = $isPremium ? "Unlimited (Key Usage: {$keyUsage}/2)" : "{$usage}/2";

        $successMsg = "<tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> RESET SUCCESSFUL\n\n"
            . "<tg-emoji emoji-id=\"5341715473882955310\">🎯</tg-emoji> Operation Complete\n"
            . "├ Status: <tg-emoji emoji-id=\"6332130972380699913\">🟢</tg-emoji> Success\n"
            . "├ Speed: <tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> Fast ({$durationMs}ms)\n"
            . "├ Time: {$nowStr}\n"
            . "└ Usage: {$usageStr}\n\n"
            . "<tg-emoji emoji-id=\"6089037760457354764\">🔑</tg-emoji> Key Processed:\n"
            . "<code>" . htmlspecialchars($text) . "</code>";

        $this->editMessageText($chatId, $messageId, $successMsg);

        $this->notifyOwner(
            "<tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> <b>SUCCESSFUL RESET</b>\n" .
            "<tg-emoji emoji-id=\"5402421675515986446\">👤</tg-emoji> <b>User:</b> " . htmlspecialchars($from->first_name ?? 'Unknown') . " (@" . htmlspecialchars($from->username ?? 'N/A') . ")\n" .
            "<tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> <b>ID:</b> <code>{$userId}</code>\n" .
            "<tg-emoji emoji-id=\"6089037760457354764\">🔑</tg-emoji> <b>Key:</b> <code>" . htmlspecialchars($text) . "</code>\n" .
            "<tg-emoji emoji-id=\"6332186798365612896\">📊</tg-emoji> <b>Key Reset Count:</b> {$keyUsage} / 2\n" .
            "<tg-emoji emoji-id=\"6332186798365612896\">📈</tg-emoji> <b>User Daily Usage:</b> {$usageStr}"
        );
    }

    private function sendWelcomeMessage($chatId)
    {
        $text = "<tg-emoji emoji-id=\"5463423955014529788\">👋</tg-emoji> Welcome to Gxpanel Key Reset Bot!\n\n"
            . "You can use this bot to manage your keys.\n\n"
            . "<b>Available Commands:</b>\n"
            . "<tg-emoji emoji-id=\"5447410659077661506\">🔹</tg-emoji> Send your key directly to reset it\n"
            . "<tg-emoji emoji-id=\"5447410659077661506\">🔹</tg-emoji> <code>/check YOUR_KEY</code> - Check key validity and status\n"
            . "<tg-emoji emoji-id=\"5447410659077661506\">🔹</tg-emoji> <code>/status</code> - Check your account limits\n"
            . "<tg-emoji emoji-id=\"5447410659077661506\">🔹</tg-emoji> <code>/contact MESSAGE</code> - Send a message to the owner\n\n"
            . "<tg-emoji emoji-id=\"5458603043203327669\">📢</tg-emoji> Channel: <a href=\"https://t.me/GxPanel\">Gxpanel Join Channel</a>\n"
            . "<tg-emoji emoji-id=\"6334830578369567214\">ℹ️</tg-emoji> Support: @RockXiter";
        $this->sendMessage($chatId, $text);
    }

    private function sendStatusMessage($chatId, $userId, $from)
    {
        $db = \Config\Database::connect();
        $premium = $db->table('telegram_premium')
            ->where('telegram_id', $userId)
            ->where('expiration_date >', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();
        $isPremium = ($premium !== null);

        $cache = \Config\Services::cache();
        $cacheKey = "tg_usage_" . $userId;
        $usage = $cache->get($cacheKey) ?? 0;

        $now = new \DateTime('now', new \DateTimeZone('Asia/Dhaka'));
        $tomorrow = new \DateTime('tomorrow', new \DateTimeZone('Asia/Dhaka'));
        $interval = $now->diff($tomorrow);
        $resetIn = $interval->format('%H:%I:%S');

        $name = htmlspecialchars($from->first_name ?? 'User');
        $username = isset($from->username) ? '@' . htmlspecialchars($from->username) : 'N/A';

        $premiumStr = $isPremium ? "<tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji>" : "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji>";
        $accessLevel = $isPremium ? "Premium Access" : "Standard Access";
        $dailyResets = $isPremium ? "Unlimited" : "2 Daily Resets";
        $usageStr = $isPremium ? "{$usage} / Unlimited" : "{$usage}/2 (" . ($usage * 50) . "%)";

        $text = "<tg-emoji emoji-id=\"6332186798365612896\">📊</tg-emoji> ACCOUNT STATUS\n"
            . "├ User Information\n"
            . "├ Name: {$name}\n"
            . "├ Username: {$username}\n"
            . "├ <tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> ID: <code>{$userId}</code>\n"
            . "├ Premium: {$premiumStr}\n"
            . "│\n"
            . "├ Access Level\n"
            . "├ {$accessLevel}\n"
            . "├ {$dailyResets}\n"
            . "├ Auto-delete: 24 hours\n"
            . "│\n"
            . "├ Today's Usage\n"
            . "├ Used: {$usageStr}\n";

        // Add progress bar (8 blocks)
        $progress = "";
        if ($isPremium) {
            $progress = "████████"; // Full bar for premium
        } else {
            $filledBlocks = $usage * 4; // 1 -> 4 blocks, 2 -> 8 blocks
            for ($i = 0; $i < 8; $i++) {
                if ($i < $filledBlocks) {
                    $progress .= "█";
                } else {
                    $progress .= "░";
                }
            }
        }

        $text .= "├ Progress: [{$progress}]\n"
            . "├ Status: <tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> Available\n"
            . "├ Reset in: {$resetIn}\n"
            . "│\n"
            . "├ Bangladesh Time\n"
            . "└ " . $now->format('Y-m-d h:i:s A');

        $this->sendMessage($chatId, $text);
    }

    private function formatKeyInfoText($key, $keyObject)
    {
        $duration = $keyObject->duration;
        $expiredDate = $keyObject->expired_date;
        $maxDevices = $keyObject->max_devices;
        $devices = $keyObject->devices;

        $deviceCount = $devices ? count(explode(',', $devices)) : 0;
        $status = $keyObject->status ? "<tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> Active" : "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> Blocked/Inactive";

        $durationStr = "";
        if ($duration == 1) {
            $durationStr = "1 Hour";
        } else if ($duration >= 2 && $duration < 24) {
            $durationStr = "{$duration} Hours";
        } else if ($duration == 24) {
            $durationStr = "1 Day";
        } else if ($duration > 24) {
            $days = $duration / 24;
            $durationStr = "{$days} Days";
        }

        $timeInfo = "";
        if (!$expiredDate) {
            $timeInfo = "<tg-emoji emoji-id=\"6215493851193285904\">⏳</tg-emoji> Not Activated Yet\n├ Total Duration: {$durationStr}";
        } else {
            $now = new \DateTime();
            $exp = new \DateTime($expiredDate);
            if ($now < $exp) {
                $diff = $now->diff($exp);
                $totalDays = $diff->days;
                $hours = $diff->h;
                $minutes = $diff->i;
                $timeInfo = "<tg-emoji emoji-id=\"6215493851193285904\">⏳</tg-emoji> Expires in: {$totalDays}d {$hours}h {$minutes}m\n└ Expiry Date: {$expiredDate}";
            } else {
                $timeInfo = "<tg-emoji emoji-id=\"6091404678279470371\">⚠️</tg-emoji> EXPIRED\n└ Expired on: {$expiredDate}";
            }
        }

        return "<tg-emoji emoji-id=\"6089037760457354764\">🔑</tg-emoji> <b>KEY INFORMATION</b>\n"
            . "├ <b>Key:</b> <code>{$key}</code>\n"
            . "├ <b>Status:</b> {$status}\n"
            . "├ <b>Devices:</b> {$deviceCount} / {$maxDevices}\n"
            . "│\n"
            . "├ <b>Validity</b>\n"
            . "└ {$timeInfo}";
    }

    private function sendKeyInfoMessage($chatId, $key)
    {
        $keyObject = $this->keysModel->getKeys($key);
        if (!$keyObject) {
            $this->sendMessage($chatId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> Key <code>{$key}</code> not found.");
            return;
        }

        $text = $this->formatKeyInfoText($key, $keyObject);
        $this->sendMessage($chatId, $text);
    }

    private function sendContactMessage($chatId, $userId, $from, $messageText)
    {
        $name = htmlspecialchars($from->first_name ?? 'User');
        $username = isset($from->username) ? '@' . htmlspecialchars($from->username) : 'N/A';

        $textForOwner = "<tg-emoji emoji-id=\"5253742260054409879\">📩</tg-emoji> <b>NEW CONTACT MESSAGE</b>\n"
            . "<tg-emoji emoji-id=\"5402421675515986446\">👤</tg-emoji> <b>From:</b> {$name} ({$username})\n"
            . "<tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> <b>User ID:</b> <code>{$userId}</code>\n"
            . "<tg-emoji emoji-id=\"5253742260054409879\">💬</tg-emoji> <b>Message:</b>\n" . htmlspecialchars($messageText);

        $this->notifyOwner($textForOwner);
        $this->sendMessage($chatId, "<tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> Your message has been successfully sent to the owner. They will contact you shortly.");
    }


    private function notifyOwner($text)
    {
        $ownerId = env('OWNER_TELEGRAM_ID');
        if (!$ownerId || $ownerId === 'YOUR_OWNER_ID_HERE') {
            return;
        }
        $this->sendMessage($ownerId, $text);
    }

    private function forwardToOwner($fromChatId, $messageId, $fromUser)
    {
        $ownerId = env('OWNER_TELEGRAM_ID');
        if (!$ownerId || $ownerId === 'YOUR_OWNER_ID_HERE') {
            return;
        }

        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token || $token === 'YOUR_BOT_TOKEN_HERE') {
            return;
        }

        // Send user info first
        $userInfo = "<tg-emoji emoji-id=\"5253742260054409879\">📥</tg-emoji> <b>Incoming Message from:</b>\n<tg-emoji emoji-id=\"5402421675515986446\">👤</tg-emoji> <b>User:</b> " . htmlspecialchars($fromUser->first_name ?? 'Unknown') . " (@" . htmlspecialchars($fromUser->username ?? 'N/A') . ")\n<tg-emoji emoji-id=\"5213225028937597732\">🆔</tg-emoji> <b>ID:</b> <code>{$fromUser->id}</code>";
        $this->sendMessage($ownerId, $userInfo);

        // Forward the actual message
        $url = "https://api.telegram.org/bot{$token}/forwardMessage";
        $data = [
            'chat_id' => $ownerId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId
        ];

        $client = \Config\Services::curlrequest();
        try {
            $client->post($url, ['json' => $data]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to forward message: ' . $e->getMessage());
        }
    }

    private function answerCallbackQuery($callbackQueryId, $text = '')
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";

        $data = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text
        ];

        $client = \Config\Services::curlrequest();
        try {
            $client->post($url, ['json' => $data]);
        } catch (\Exception $e) {
        }
    }

    private function sendMessageWithKeyboard($chatId, $text, $keyboard)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($keyboard)
        ];

        $client = \Config\Services::curlrequest();
        try {
            $client->post($url, ['json' => $data]);
        } catch (\Exception $e) {
        }
    }

    private function editMessageText($chatId, $messageId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/editMessageText";

        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $client = \Config\Services::curlrequest();
        try {
            $client->post($url, ['json' => $data]);
        } catch (\Exception $e) {
        }
    }

    private function sendMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token || $token === 'YOUR_BOT_TOKEN_HERE') {
            log_message('error', 'Telegram Bot Token not set in .env');
            return;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $client = \Config\Services::curlrequest();
        try {
            $client->post($url, [
                'json' => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to send message to Telegram: ' . $e->getMessage());
        }
    }

    private function saveTelegramUser($chatId)
    {
        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('telegram_bot_users')) {
                $db->query("CREATE TABLE IF NOT EXISTS telegram_bot_users (
                    chat_id VARCHAR(50) PRIMARY KEY,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            }

            $builder = $db->table('telegram_bot_users');
            $exists = $builder->where('chat_id', (string) $chatId)->countAllResults();
            if ($exists == 0) {
                $builder->insert(['chat_id' => (string) $chatId]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error saving telegram user: ' . $e->getMessage());
        }
    }

    private function getTelegramUsers()
    {
        try {
            $db = \Config\Database::connect();
            $ids = [];

            if ($db->tableExists('telegram_bot_users')) {
                $users = $db->table('telegram_bot_users')->get()->getResult();
                foreach ($users as $u) {
                    $ids[] = $u->chat_id;
                }
            }

            // Also include all premium users if they exist
            if ($db->tableExists('telegram_premium')) {
                $premium = $db->table('telegram_premium')->get()->getResult();
                foreach ($premium as $p) {
                    if (!empty($p->telegram_id)) {
                        $ids[] = (string) $p->telegram_id;
                    }
                }
            }

            return array_unique($ids);
        } catch (\Exception $e) {
        }
        return [];
    }

    private function handleBroadcast($message, $chatId, $textCmd)
    {
        $users = $this->getTelegramUsers();
        if (empty($users)) {
            $this->sendMessage($chatId, "<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> No users found to broadcast.");
            return;
        }

        $this->sendMessage($chatId, "<tg-emoji emoji-id=\"6215493851193285904\">⏳</tg-emoji> Broadcasting to " . count($users) . " users...");

        $success = 0;
        $failed = 0;
        $token = env('TELEGRAM_BOT_TOKEN');
        $client = \Config\Services::curlrequest();

        $isReply = isset($message->reply_to_message);

        foreach ($users as $userChatId) {
            if ($isReply) {
                $targetMsg = $message->reply_to_message;
                $url = "https://api.telegram.org/bot{$token}/copyMessage";
                $data = [
                    'chat_id' => $userChatId,
                    'from_chat_id' => $chatId,
                    'message_id' => $targetMsg->message_id
                ];
            } else {
                $broadcastText = trim(substr($textCmd, 10)); // remove '/broadcast'
                if (empty($broadcastText) && !isset($message->photo) && !isset($message->video) && !isset($message->document)) {
                    continue; // empty broadcast
                }

                if (isset($message->photo) || isset($message->video) || isset($message->document)) {
                    $url = "https://api.telegram.org/bot{$token}/copyMessage";
                    $data = [
                        'chat_id' => $userChatId,
                        'from_chat_id' => $chatId,
                        'message_id' => $message->message_id,
                        'caption' => $broadcastText,
                        'parse_mode' => 'HTML'
                    ];
                } else {
                    $url = "https://api.telegram.org/bot{$token}/sendMessage";
                    $data = [
                        'chat_id' => $userChatId,
                        'text' => $broadcastText,
                        'parse_mode' => 'HTML'
                    ];
                }
            }

            try {
                $res = $client->post($url, ['json' => $data, 'http_errors' => false]);
                if ($res->getStatusCode() == 200) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $this->sendMessage($chatId, "<tg-emoji emoji-id=\"6334666613698075229\">✅</tg-emoji> <b>Broadcast Completed!</b>\n\n<tg-emoji emoji-id=\"5341715473882955310\">🎯</tg-emoji> Success: {$success}\n<tg-emoji emoji-id=\"5210952531676504517\">❌</tg-emoji> Failed: {$failed}");
    }
}
