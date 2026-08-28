<?php
namespace App\Controllers;

use App\Models\KeysModel;

class Connect extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KeysModel();
    }

    private function respondJson(array $payload, int $statusCode = 200)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"status":false,"message":"JSON encode error"}';
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody($json);
    }

    public function index()
    {
        $method = strtolower($this->request->getMethod());
        if (!in_array($method, ['post', 'get'], true)) {
            return $this->respondJson([
                'status'  => false,
                'reason'  => 'Method Not Allowed'
            ], 405);
        }

        return $this->index_post();
    }

    public function index_post()
    {
        $game   = trim((string) ($this->request->getVar('game') ?? ''));
        $uKey   = trim((string) ($this->request->getVar('User_Key') ?? $this->request->getVar('key') ?? ''));
        $serial = trim((string) ($this->request->getVar('serial') ?? $this->request->getVar('hwid') ?? ''));

        if ($game === '' || $uKey === '' || $serial === '') {
            return $this->respondJson([
                'status'  => false,
                'message' => 'Bad Parameter'
            ]);
        }

        if (!preg_match('/^[A-Za-z0-9_\-@.]+$/', $uKey) || strlen($uKey) > 99) {
            return $this->respondJson([
                'status'  => false,
                'message' => 'Bad Parameter'
            ]);
        }

        $time  = new \CodeIgniter\I18n\Time;
        $model = $this->model;

        // ==================== END HARDCODE ====================

        // Tìm key trong DB - chỉ tìm theo key (không phân biệt game nữa nếu bạn muốn)
        // Nếu bạn lưu key ở cột khác (ví dụ: key, user_key, license...), sửa ở đây
        $findKey = $model->where('username', trim($uKey)) // giả sử cột là username
                         ->where('game', $game)
                         ->first();

        // Nếu vẫn không tìm thấy, thử tìm chỉ theo key (bỏ qua game)
        if (!$findKey) {
            $findKey = $model->where('username', trim($uKey))->first();
        }

        // Nếu vẫn không có → báo sai key
        if (!$findKey) {
            return $this->respondJson([
                'status' => false,
                'reason' => 'USER OR GAME NOT REGISTERED'
            ]);
        }

        $getField = function ($row, string $key, $default = null) {
            if (is_array($row)) {
                return $row[$key] ?? $default;
            }
            if (is_object($row)) {
                return $row->{$key} ?? $default;
            }
            return $default;
        };

        // Check block
        if ((int) $getField($findKey, 'status', 0) !== 1) {
            return $this->respondJson([
                'status'  => false,
                'message' => 'USER BLOCKED'
            ]);
        }

        $id_keys  = $getField($findKey, 'id');
        $duration = (int) $getField($findKey, 'duration', 0);
        $expired  = $getField($findKey, 'expired_date');
        if (is_string($expired) && $expired !== '') {
            $expired = $time::parse($expired);
        }
        $max_dev  = (int) $getField($findKey, 'max_devices', 1);
        $devices  = (string) $getField($findKey, 'devices', '');

        // Xử lý expire
        if (!$expired) {
            $setExpired = $time::now()->addHours($duration);
            $model->update($id_keys, ['expired_date' => $setExpired]);
            $expired = $setExpired;
        } elseif ($time::now()->isAfter($expired)) {
            return $this->respondJson([
                'status'  => false,
                'message' => 'EXPIRED KEY'
            ]);
        }

        // Bind hwid
        $lsDevice = $devices ? explode(",", $devices) : [];
        $deviceOn = in_array($serial, $lsDevice);

        if (!$deviceOn) {
            if (count($lsDevice) >= $max_dev) {
                return $this->respondJson([
                    'status'  => false,
                    'message' => 'MAX DEVICE REACHED'
                ]);
            }
            $lsDevice[] = $serial;
            $setDevice = implode(",", array_unique($lsDevice));
            $model->update($id_keys, ['devices' => $setDevice]);
        }

        $rng = time();
        $expireDate = $expired instanceof \CodeIgniter\I18n\Time
            ? $expired->format('Y-m-d H:i:s')
            : (string) $expired;
        $tail = "饾棧岽渟蕼岽樶磤 啶班ぞ啶溼瓌 饾棳饾棫 禄禄 YOGZZNICH";
        $real = $game . '-' . $uKey . '-' . $serial . '-' . $tail;
        $token = md5($real);

        // Response thành công
        return $this->respondJson([
            'status' => true,
            'data'   => [
                'real'  => $real,
                'token' => $token,
                'rng'   => $rng,
                'EXP'   => $expireDate
            ]
        ]);
    }
}