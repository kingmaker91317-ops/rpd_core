<?php

namespace App\Controllers;

use App\Models\KeysModel;
use App\Models\UserModel;

/**
 * UserBot Controller
 * ─────────────────────────────────────────────────────────
 * Handles per-user Telegram bot webhooks at:
 *   /webhook/{seller_key}
 *
 * Each Reseller / Admin / Owner can create their own Telegram bot
 * and paste its token in Settings. Their bot will ONLY reset keys
 * that belong to them (registrator = their username).
 *
 * The existing global bot at /webhook is NOT touched.
 * ─────────────────────────────────────────────────────────
 */
class UserBot extends BaseController
{
    protected $keysModel;
    protected $userModel;

    /** The owner of this bot (resolved from seller_key) */
    protected $botOwner   = null;
    /** The bot token for this user */
    protected $botToken   = null;

    public function __construct()
    {
        $this->keysModel = new KeysModel();
        $this->userModel = new UserModel();
    }

    // ─────────────────────────────────────────────────────
    // ENTRY POINT  /webhook/{seller_key}
    // ─────────────────────────────────────────────────────
    public function index($seller_key = null)
    {
        // 1. Find the user who owns this webhook
        if (!$seller_key) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No seller key']);
        }

        $owner = $this->userModel
            ->where('seller_key', $seller_key)
            ->where('status', 1)
            ->first();

        if (!$owner || empty($owner['telegram_bot_token'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Bot not configured']);
        }

        $this->botOwner = (object) $owner;
        $this->botToken = $owner['telegram_bot_token'];

        // 2. Parse Telegram update
        $update = $this->request->getJSON();
        if (!$update) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No data']);
        }

        log_message('debug', '[UserBot] Webhook hit for seller_key=' . $seller_key . ' user=' . $owner['username']);

        $message       = $update->message       ?? null;
        $callbackQuery = $update->callback_query ?? null;

        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery);
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (!$message) {
            return $this->response->setJSON(['status' => 'ok']);
        }

        $chatId    = $message->chat->id    ?? null;
        $userId    = $message->from->id    ?? null;
        $messageId = $message->message_id  ?? null;

        if (!$chatId) {
            return $this->response->setJSON(['status' => 'ok']);
        }

        $text    = $message->text    ?? '';
        $caption = $message->caption ?? '';
        $textCmd = $text ? $text : $caption;

        // Cooldown
        $cache    = \Config\Services::cache();
        $cacheKey = 'ubot_cooldown_' . $this->botOwner->id_users . '_' . $userId;
        if ($cache->get($cacheKey)) {
            $this->sendMessage($chatId, "⏳ Please wait 30 seconds before sending another request.");
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (!$text) {
            return $this->response->setJSON(['status' => 'ok']);
        }

        $text = trim($text);

        // Commands
        if ($text === '/start' || strtolower($text) === 'start') {
            $this->sendWelcomeMessage($chatId);
            return $this->response->setJSON(['status' => 'ok']);
        }

        if (strpos(strtolower($text), '/check ') === 0 || strpos(strtolower($text), '/info ') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) > 1) {
                $this->sendKeyInfoMessage($chatId, trim($parts[1]));
            } else {
                $this->sendMessage($chatId, "⚠️ Usage: <code>/check YOUR_KEY</code>");
            }
            return $this->response->setJSON(['status' => 'ok']);
        }

        // Treat as key — search user's keys (or all keys if owner level=1)
        $query = $this->keysModel->where('user_key', $text);
        if ($this->botOwner->level != 1) {
            $query->where('registrator', $this->botOwner->username);
        }
        $keyObject = $query->first();

        if ($keyObject) {
            $this->askForResetConfirmation($chatId, $userId, $message->from, $text, (object)$keyObject);
        } else {
            $this->sendMessage($chatId, "❌ Key <code>" . htmlspecialchars($text) . "</code> not found.");
        }

        return $this->response->setJSON(['status' => 'ok']);
    }

    // ─────────────────────────────────────────────────────
    // CALLBACK QUERY (Inline button press)
    // ─────────────────────────────────────────────────────
    private function handleCallbackQuery($callbackQuery)
    {
        $userId    = $callbackQuery->from->id;
        $chatId    = $callbackQuery->message->chat->id;
        $messageId = $callbackQuery->message->message_id;
        $data      = $callbackQuery->data;

        if (strpos($data, 'ubot_cancel_') === 0) {
            $this->editMessageText($chatId, $messageId, "❌ Reset cancelled.");
        } elseif (strpos($data, 'ubot_reset_') === 0) {
            $key = substr($data, strlen('ubot_reset_'));
            $this->processKeyReset($chatId, $userId, $callbackQuery->from, $key, $messageId);
        }

        $this->answerCallbackQuery($callbackQuery->id);
    }

    /**
     * Check if the Telegram User ID belongs to the Bot Owner (or Level 1 Owner)
     */
    private function isBotOwner($userId)
    {
        if (empty($userId)) return false;
        if (!empty($this->botOwner->level) && $this->botOwner->level == 1) return true;
        if (!empty($this->botOwner->telegram_id) && (string)$userId === (string)$this->botOwner->telegram_id) {
            return true;
        }
        return false;
    }

    // ─────────────────────────────────────────────────────
    // ASK CONFIRMATION before reset
    // ─────────────────────────────────────────────────────
    private function askForResetConfirmation($chatId, $userId, $from, $key, $keyObject)
    {
        $isOwner = $this->isBotOwner($userId);
        $cache   = \Config\Services::cache();
        $cacheKeyResetCount = 'ubot_reset_cnt_' . md5($key) . '_' . date('Ymd');
        $keyUsage = $cache->get($cacheKeyResetCount) ?? 0;

        $usageText = $isOwner
            ? "📊 <b>Key Reset Usage:</b> Unlimited 👑 (Bot Owner)"
            : "📊 <b>Daily Key Reset Usage:</b> {$keyUsage} / 2";

        $infoText = $this->formatKeyInfoText($key, $keyObject);
        $text = $infoText . "\n\n" . $usageText . "\n\nAre you sure you want to reset this key?";

        $keyboard = [
            'inline_keyboard' => [
                [[
                    'text'          => '✅ Reset Key',
                    'callback_data' => 'ubot_reset_' . $key,
                ]],
                [[
                    'text'          => '❌ Cancel',
                    'callback_data' => 'ubot_cancel_' . $key,
                ]],
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    // ─────────────────────────────────────────────────────
    // PROCESS RESET — only this user's keys
    // ─────────────────────────────────────────────────────
    private function processKeyReset($chatId, $userId, $from, $text, $messageId)
    {
        $startTime = microtime(true);

        // Find key
        $query = $this->keysModel->where('user_key', $text);
        if ($this->botOwner->level != 1) {
            $query->where('registrator', $this->botOwner->username);
        }
        $keyObject = $query->first();

        if (!$keyObject) {
            $this->editMessageText($chatId, $messageId, "❌ Key <code>" . htmlspecialchars($text) . "</code> not found or does not belong to this bot.");
            return;
        }

        $keyObject = (object)$keyObject;

        // Cooldown check (30s)
        $cache    = \Config\Services::cache();
        $cacheKey = 'ubot_cooldown_' . $this->botOwner->id_users . '_' . $userId;
        if ($cache->get($cacheKey)) {
            $this->editMessageText($chatId, $messageId, "⏳ Please wait 30 seconds before sending another request.");
            return;
        }

        if (!$keyObject->devices) {
            $this->editMessageText($chatId, $messageId, "ℹ️ This key is already reset (no devices registered).");
            return;
        }

        // Limit Check for Non-Owners (Max 2 resets per key per day)
        $isOwner = $this->isBotOwner($userId);
        $cacheKeyResetCount = 'ubot_reset_cnt_' . md5($text) . '_' . date('Ymd');
        $keyUsage = $cache->get($cacheKeyResetCount) ?? 0;

        if (!$isOwner) {
            if ($keyUsage >= 2) {
                $this->editMessageText($chatId, $messageId, "❌ <b>DAILY LIMIT REACHED</b>\n\nThis key has reached its maximum limit of <b>2 resets per day</b>.\nPlease try again tomorrow or contact the bot owner.");
                return;
            }
        }

        // Do the reset
        $this->keysModel->update($keyObject->id_keys, ['devices' => null]);

        // Increment usage if non-owner
        if (!$isOwner) {
            $keyUsage++;
            $cache->save($cacheKeyResetCount, $keyUsage, 86400); // 24 Hours
        }

        // Set cooldown 30s
        $cache->save($cacheKey, true, 30);

        $endTime    = microtime(true);
        $durationMs = round(($endTime - $startTime) * 1000);

        $usageStr = $isOwner ? "Unlimited (Bot Owner)" : "{$keyUsage} / 2 Today";

        $successMsg = "✅ <b>RESET SUCCESSFUL</b>\n\n"
            . "🎯 Operation Complete\n"
            . "├ Status: 🟢 Success\n"
            . "├ Speed: ✅ Fast ({$durationMs}ms)\n"
            . "├ Usage: {$usageStr}\n"
            . "└ Key: <code>" . htmlspecialchars($text) . "</code>";

        $this->editMessageText($chatId, $messageId, $successMsg);

        log_message('info', '[UserBot] Key reset: key=' . $text . ' by TG_user=' . $userId . ' owner=' . $this->botOwner->username . ' isOwner=' . ($isOwner ? 'yes' : 'no'));
    }

    // ─────────────────────────────────────────────────────
    // KEY INFO
    // ─────────────────────────────────────────────────────
    private function formatKeyInfoText($key, $keyObject)
    {
        $deviceCount = $keyObject->devices ? count(explode(',', $keyObject->devices)) : 0;
        $status      = $keyObject->status
            ? "✅ Active"
            : "❌ Inactive";

        $duration    = (int) $keyObject->duration;
        if ($duration == 24)        $durationStr = "1 Day";
        elseif ($duration > 24)     $durationStr = ($duration / 24) . " Days";
        elseif ($duration >= 2)     $durationStr = "{$duration} Hours";
        else                        $durationStr = "1 Hour";

        $expiredDate = $keyObject->expired_date;
        if (!$expiredDate) {
            $timeInfo = "⏳ Not Activated Yet\n├ Total Duration: {$durationStr}";
        } else {
            $now = new \DateTime();
            $exp = new \DateTime($expiredDate);
            if ($now < $exp) {
                $diff     = $now->diff($exp);
                $timeInfo = "⏳ Expires in: {$diff->days}d {$diff->h}h {$diff->i}m\n└ Expiry Date: {$expiredDate}";
            } else {
                $timeInfo = "⚠️ EXPIRED\n└ Expired on: {$expiredDate}";
            }
        }

        return "🔑 <b>KEY INFORMATION</b>\n"
            . "├ <b>Key:</b> <code>{$key}</code>\n"
            . "├ <b>Status:</b> {$status}\n"
            . "├ <b>Devices:</b> {$deviceCount} / {$keyObject->max_devices}\n"
            . "│\n"
            . "├ <b>Validity</b>\n"
            . "└ {$timeInfo}";
    }

    private function sendKeyInfoMessage($chatId, $key)
    {
        $query = $this->keysModel->where('user_key', $key);
        if ($this->botOwner->level != 1) {
            $query->where('registrator', $this->botOwner->username);
        }
        $keyObject = $query->first();

        if (!$keyObject) {
            $this->sendMessage($chatId, "❌ Key <code>{$key}</code> not found.");
            return;
        }
        $this->sendMessage($chatId, $this->formatKeyInfoText($key, (object)$keyObject));
    }

    // ─────────────────────────────────────────────────────
    // WELCOME MESSAGE
    // ─────────────────────────────────────────────────────
    private function sendWelcomeMessage($chatId)
    {
        $ownerUsername = htmlspecialchars($this->botOwner->username);
        $text = "⚡ <b>RAPID CORE — KEY RESET BOT</b>\n"
            . "──────────────────────────────\n"
            . "👤 <b>Bot Owner:</b> <code>{$ownerUsername}</code>\n"
            . "🟢 <b>Status:</b> Online & Ready\n"
            . "──────────────────────────────\n\n"
            . "🤖 <b>Available Commands:</b>\n"
            . "├ 🔑 Send key directly to reset device (HWID)\n"
            . "└ 🔍 <code>/check YOUR_KEY</code> — Check key details & validity\n\n"
            . "✨ <i>Automated 24/7 HWID Reset Service</i>";
        $this->sendMessage($chatId, $text);
    }

    // ─────────────────────────────────────────────────────
    // TELEGRAM API HELPERS (use per-user botToken)
    // ─────────────────────────────────────────────────────
    private function sendMessage($chatId, $text)
    {
        if (!$this->botToken) return;

        $client = \Config\Services::curlrequest();
        try {
            $client->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'json' => ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML']
            ]);
        } catch (\Exception $e) {
            log_message('error', '[UserBot] sendMessage failed: ' . $e->getMessage());
        }
    }

    private function sendMessageWithKeyboard($chatId, $text, $keyboard)
    {
        if (!$this->botToken) return;

        $client = \Config\Services::curlrequest();
        try {
            $client->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'json' => [
                    'chat_id'      => $chatId,
                    'text'         => $text,
                    'parse_mode'   => 'HTML',
                    'reply_markup' => json_encode($keyboard),
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', '[UserBot] sendMessageWithKeyboard failed: ' . $e->getMessage());
        }
    }

    private function editMessageText($chatId, $messageId, $text)
    {
        if (!$this->botToken) return;

        $client = \Config\Services::curlrequest();
        try {
            $client->post("https://api.telegram.org/bot{$this->botToken}/editMessageText", [
                'json' => [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => $text,
                    'parse_mode' => 'HTML',
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', '[UserBot] editMessageText failed: ' . $e->getMessage());
        }
    }

    private function answerCallbackQuery($callbackQueryId, $text = '')
    {
        if (!$this->botToken) return;

        $client = \Config\Services::curlrequest();
        try {
            $client->post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
                'json' => ['callback_query_id' => $callbackQueryId, 'text' => $text]
            ]);
        } catch (\Exception $e) {}
    }
}
