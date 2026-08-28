<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if ($session->userid && $session->time_login) {
            $userModel = new UserModel();
            $user = $userModel->getUser($session->userid);
            if ($user) {
                // Cho phép level 1 (admin) và level 3 (tenant)
                if (!in_array($user->level, [1, 3])) {
                    return redirect()
                        ->to('dashboard')
                        ->with('msgWarning', "Access Denied!");
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
