<?php

namespace App\Controllers;

use App\Models\UserModel;

class SellerContracts extends BaseController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    public function index()
    {
        // Chỉ admin mới được vào
        if (session('userid') && $this->userModel->getUser(session('userid'))->level == 1) {
            $sellers = $this->userModel
                ->where('level >', 1)
                ->orderBy('username', 'ASC')
                ->findAll();
            
            $data = [
                'user' => $this->userModel->getUser(session('userid')),
                'sellers' => $sellers
            ];
            
            return view('seller_contracts', $data);
        }
        
        return redirect()->to('dashboard')->with('msgDanger', 'Chỉ admin mới có quyền truy cập!');
    }
    
    public function update($sellerId)
    {
        // Chỉ admin mới được vào
        if (!session('userid') || $this->userModel->getUser(session('userid'))->level != 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Forbidden'])->setStatusCode(403);
        }
        
        $post = $this->request->getPost();
        $contractExpiredAt = $post['contract_expired_at'] ?? null;
        
        // Validate date format
        if ($contractExpiredAt) {
            $d = \DateTime::createFromFormat('Y-m-d', $contractExpiredAt);
            if (!$d || $d->format('Y-m-d') !== $contractExpiredAt) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Định dạng ngày không hợp lệ (phải là YYYY-MM-DD)'
                ]);
            }
        }
        
        // Update seller
        $data = [
            'contract_expired_at' => $contractExpiredAt ? $contractExpiredAt . ' 23:59:59' : null
        ];
        
        if ($this->userModel->update($sellerId, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cập nhật hạn hợp đồng thành công!'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Lỗi cập nhật database'
        ]);
    }
}
