<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\KeyFreeSettingsModel;

class KeyFreeSettings extends BaseController
{
    protected $userModel, $settingsModel, $user;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->settingsModel = new KeyFreeSettingsModel();
        $this->user = $this->userModel->getUser();
        
        // Check access cho cả admin và tenant
        if (!isset($this->user->level) || !in_array($this->user->level, [1, 3])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function index()
    {
        if ($this->request->getMethod() === 'post') {
            return $this->saveSettings();
        }

        // Lấy settings của admin/tenant hiện tại
        $settings = $this->settingsModel->where('admin_id', $this->user->id_users)->first();
        
        $data = [
            'title' => 'Key Free Settings',
            'user' => $this->user,
            'settings' => $settings
        ];

        return view('Keys/key_free_settings', $data);
    }

    private function saveSettings()
    {
        $rules = [
            'max_keys_per_day' => 'required|integer|greater_than[0]',
            'key_duration' => 'required|integer|greater_than[0]',
            'max_devices' => 'required|integer|greater_than[0]',
            'shortlinks' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('msgDanger', $this->validator->listErrors());
        }

        $data = [
            'admin_id' => $this->user->id_users,
            'max_keys_per_day' => $this->request->getPost('max_keys_per_day'),
            'key_duration' => $this->request->getPost('key_duration'),
            'max_devices' => $this->request->getPost('max_devices'),
            'shortlinks' => $this->request->getPost('shortlinks'),
            'status' => $this->request->getPost('status') ? 1 : 0
        ];

        $existing = $this->settingsModel->where('admin_id', $this->user->id_users)->first();
        if ($existing) {
            $this->settingsModel->update($existing['id'], $data);
        } else {
            $this->settingsModel->insert($data);
        }

        return redirect()->to('keys/admin/key-free-settings')->with('msgSuccess', 'Settings saved successfully');
    }
}