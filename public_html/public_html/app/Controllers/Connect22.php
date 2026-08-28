<?php

namespace App\Controllers;

use App\Models\KeysModel;

class Connect extends BaseController
{
    protected $model, $game, $uKey, $sDev ,$user;

    public function __construct()
    {
        $this->maintenance = false;
        $this->model = new KeysModel();
        $this->staticWords = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";
    }

    public function index()
    {
        if ($this->request->getPost()) {
            return $this->index_post();
        } else {
            $nata = [
                "web_info" => [
                    "_client" => BASE_NAME,
                    "license" => "Qp5KSGTquetnUkjX6UVBAURH8hTkZuLM",
                    "version" => "1.0.0",
                ],
                "web__dev" => [
                    "author" => "Telegram:@Thaicodm",
                    "telegram" => "https://t.me/trungthaicodm"
                ],
            ];

            return $this->response->setJSON($nata);
        }
    }

    public function index_post()
    {
        $isMT = $this->maintenance;
        $game = $this->request->getPost('game');
        $uKey = $this->request->getPost('U_KEY');
        $sDev = $this->request->getPost('serial');
        

        $form_rules = [
            'game' => 'required|alpha_dash',
            'U_KEY' => 'required|string|min_length[4]|max_length[100]',
            'serial' => 'required|alpha_dash'
        ];

        if (!$this->validate($form_rules)) {
            $data = [
                'status' => false,
                'reason' => "Bad Parameter",
            ];
            return $this->response->setJSON($data);
        }

        if ($isMT) {                  
            $data = [
                'status' => false,
                'reason' => 'Maintenance Mode'
            ];
        } else {
            if (!$game or !$uKey or !$sDev) {
                $data = [
                    'status' => false,
                    'reason' => 'INVALID PARAMETER'
                ];
            } else {
                $time = new \CodeIgniter\I18n\Time;
                $model = $this->model;
                $findKey = $model
                    ->where(['username' => $uKey, 'game' => $game])
                    ->get()
                    ->getRowObject();

                if ($findKey) {
                    if ($findKey->status != 1) {
                        $data = [
                            'status' => false,
                            'reason' => 'USER NOT USING'
                        ];
                    } else {
                        $id_keys = $findKey->id;
                        $duration = $findKey->duration;
                        $expired = $findKey->expired_date;
                        $max_dev = $findKey->max_devices;
                        $devices = $findKey->devices;
                        $seller =  $findKey->id;
                        $UserKey = $findKey->username;
                        $GameV = $findKey->game;
                        $TestE = 'Many features will active after purchase on this menu';
                        $TestV = 'Nhiều tính năng sẽ được kích hoạt sau khi dùng vip key';
                        $TipE ="PURECHASE VIP KEY FOR\nACTIVE FULL FEATURES\n\nMUA KEY VIP ĐỂ KÍCH HOẠT \nTẤT CẢ TÍNH NĂNG";
                        
                        /*
                        $TipE ="Grand all permissons for CODM\nTurn off battery optimize\nDownload all resources for skins mod!\nOwner :@ThaiCodm Seller @CODMVNN \n@NONNN6789 @TRUNGTHAIRESSELER";*/
                        $TipV = "Nhớ cấp tất cả quyền cho game CODM\nTắt tối ưu pin cho CODM\nTải tất cả tài nguyên hiếm,sở hữu,chiến \ndịch viên,vũ khí cho mod skins\nChủ sở hữu :@ThaiCodm Zalo 0369978877";
                       $PhienBan = 14;
                       $FeaturesO = 1;
                       $CrackO = 1;
    
                        function checkDevicesAdd($serial, $devices, $max_dev)
                        {
                            $lsDevice = explode(",", $devices);
                            $cDevices = isset($devices) ? count($lsDevice) : 0;
                            $serialOn = in_array($serial, $lsDevice);
    
                            if ($serialOn) {
                                return true;
                            } else {
                                if ($cDevices < $max_dev) {
                                    array_push($lsDevice, $serial);
                                    $setDevice = reduce_multiples(implode(",", $lsDevice), ",", true);
                                    return ['devices' => $setDevice];
                                } else {
                                    // ! false - devices max
                                    return false;
                                }
                            }
                        }
    
                        if (!$expired) {
                            $setExpired = $time::now()->addDays($duration);
                            $model->update($id_keys, ['expired_date' => $setExpired]);
                            $data['status'] = true;
                        } else {
                            if ($time::now()->isBefore($expired)) {
                                $data['status'] = true;
                                
                            } else {
                                $data = [
                                    'status' => false,
                                    'reason' => 'EXPIRED KEY'
                                ];
                            }
                        }
    
                        if ($data['status']) {
                            $modname = 'MOD';
                        
                            $devicesAdd = checkDevicesAdd($sDev, $devices, $max_dev);
                            if ($devicesAdd) {
                                if (is_array($devicesAdd)) {
                                    $model->update($id_keys, $devicesAdd);
                                }
                                // ? game-U_KEY-serial-word di line 15
                                $real = "$game-$uKey-$sDev-$this->staticWords";
                                $data = [
                                    'status' => true,
                                    'data' => [
                                        // 'real' => $real,
                                       // 'exp' => $expired
                                        'name' => $modname,                          
                                        'token' => md5($real),
                                        'rng' => $time->getTimestamp(),
                                        'exp' => $expired,
                                        'namekey' => $UserKey,
                                        'teste' => $TestE,
                                        'testv' => $TestV,
                                         'tipe' => $TipE,
                                        'tipv' => $TipV,
                                        'pbo' => $PhienBan,
                                        'Fo' => $FeaturesO,
                                        'seller' => $seller,
                                        'cracko' => $CrackO,
                                        'GameV' => $GameV,
                                    ],
                                ];
                            } else {
                                $data = [
                                    'status' => false,
                                    'reason' => 'MAX DEVICE REACHED'
                                ];
                            }
                        }
                    }
                } else {
                    $data = [
                        'status' => false,
                        'reason' => 'Password not exists ! chat @ ThaiCodm /Zalo 0369978877'
                    ];
                }
            }
        }
        return $this->response->setJSON($data);
    }
}
