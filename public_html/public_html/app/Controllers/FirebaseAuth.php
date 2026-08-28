<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class FirebaseAuth extends Controller
{
    private function errorResponse(int $code, string $message)
    {
        $errorResponse = [
            "error" => [
                "code"    => $code,
                "message" => $message,
                "errors"  => [
                    [
                        "message" => $message,
                        "domain"  => "global",
                        "reason"  => "invalid"
                    ]
                ]
            ]
        ];

        return $this->response
            ->setStatusCode($code)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($errorResponse));
    }

    public function verifyPassword()
    {
        header('Content-Type: application/json');

        // Lấy JSON từ body
        $data     = $this->request->getJSON(true);
        $email    = $data['email']    ?? '';
        $password = $data['password'] ?? '';

        // Fake "database"
        $users = [
            "hsusue@shield.com" => password_hash("jsjsjsjsn", PASSWORD_DEFAULT),
            "demo@test.com"     => password_hash("123456", PASSWORD_DEFAULT)
        ];

        // Nếu email không tồn tại
        if (!isset($users[$email])) {
            return $this->errorResponse(400, "EMAIL_NOT_FOUND");
        }

        // Nếu sai mật khẩu
        if (!password_verify($password, $users[$email])) {
            return $this->errorResponse(400, "INVALID_PASSWORD");
        }

        // Nếu login thành công → trả JSON giống Firebase
        $success = [
            "idToken"      => base64_encode(random_bytes(24)),
            "email"        => $email,
            "refreshToken" => base64_encode(random_bytes(24)),
            "expiresIn"    => "3600",
            "localId"      => uniqid("user_")
        ];

        return $this->response->setJSON($success);
    }
}
