<?php

namespace App\Controllers;

use App\Models\FileModel;
use App\Models\UserModel;
use App\Models\GameModel;

class LibOnline extends BaseController
{
   protected $userid, $model, $user;
   protected $uploadPath = WRITEPATH . 'uploads';
   protected $hidden_extensions = ['php', 'html'];

   public function __construct()
   {
       $this->userid = session()->userid;
       $this->model = new FileModel();
       $this->user = (new UserModel())->getUser($this->userid);
   }

   private function checkPermission()
   {
       if (!session()->has('userid')) {
           return redirect()->to('login')->send();
       }

       if ($this->user->level != 1) {
           return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!')->send();
       }
   }

   public function index()
   {
       $this->checkPermission();
       return $this->defaultPage();
   }

public function defaultPage()
{
    $this->checkPermission();
    $gameModel = new GameModel();
    
    // Lấy tất cả các file từ bảng libonline
    $files = $this->model->findAll();

    foreach ($files as &$file) {
        $file['size'] = filesize(WRITEPATH . 'uploads/' . $file['name']);
        $file['mtime'] = filemtime(WRITEPATH . 'uploads/' . $file['name']);
        $file['permissions'] = substr(sprintf('%o', fileperms(WRITEPATH . 'uploads/' . $file['name'])), -4);
        $file['is_dir'] = is_dir(WRITEPATH . 'uploads/' . $file['name']);
        $file['is_deleteable'] = is_writable(WRITEPATH . 'uploads/' . $file['name']);

        // Lấy tên game từ bảng game dựa trên game_package
        $game = $gameModel->where('package', $file['game_package'])->first();
        $file['game_name'] = $game ? $game['name'] : $file['game_package'];
    }

    $data = [
        'title' => 'Libraries Manager',
        'user' => $this->user,
        'uploadPath' => $this->uploadPath,
        'files' => $files, // Truyn danh sách file vào view
        'games' => $gameModel->findAll()
    ];

    return view('Server/libonline', $data);
}

public function upload()
{
    $this->checkPermission();
    
    try {
        $rules = [
            'file' => [
                'label' => 'Library File',
                'rules' => [
                    'uploaded[file]',
                    'mime_in[file,application/x-sharedlib]',
                ]
            ],
            'game' => [
                'label' => 'Game Package',
                'rules' => 'required'
            ],
            'type' => [
                'label' => 'Type',
                'rules' => 'required|in_list[vip,free]'
            ],
            'architecture' => [
                'label' => 'Architecture',
                'rules' => 'required|in_list[armeabi-v7a,arm64-v8a]'
            ],
            'version' => [
                'label' => 'Version',
                'rules' => 'required'
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $this->validator->listErrors()
            ]);
        }

        $file = $this->request->getFile('file');
        $gamePackage = $this->request->getPost('game');
        $type = $this->request->getPost('type');
        $architecture = $this->request->getPost('architecture');
        $version = $this->request->getPost('version');

        $gameModel = new GameModel();
        $game = $gameModel->where('package', $gamePackage)->first();

        if (!$game) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Game package not found in database.'
            ]);
        }

        if ($file->isValid() && !$file->hasMoved()) {
            $newFileName = $gamePackage . '_' . $type . '_' . $architecture . '_v' . $version . '.so';
            $fullPath = $this->uploadPath . '/' . $newFileName;

            if (file_exists($fullPath)) {
                unlink($fullPath);
                $this->model->where('name', $newFileName)->delete();
            }

            $file->move($this->uploadPath, $newFileName);

            $fileData = [
                'game_package' => $gamePackage,
                'name' => $newFileName,
                'type' => $type,
                'architecture' => $architecture,
                'version' => $version,
                'status' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $existingFile = $this->model->where('name', $newFileName)->first();
            if ($existingFile) {
                $this->model->update($existingFile['id'], $fileData);
                $message = 'Library updated successfully';
            } else {
                $this->model->insert($fileData);
                $message = 'Library uploaded successfully';
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message . ' as ' . $newFileName
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'File upload failed.'
        ]);

    } catch (\Exception $e) {
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Upload error: ' . $e->getMessage()
        ]);
    }
}

   public function delete($encodedPath)
    {
        $this->checkPermission();

        // Giải mã đường dẫn
        $filePath = base64_decode($encodedPath);

        // Kiểm tra file có tồn tại không
        if (!file_exists(WRITEPATH . 'uploads/' . $filePath)) {
            return $this->Error('File not found.');
        }

        // Xóa file
        if (unlink(WRITEPATH . 'uploads/' . $filePath)) {
            // Xóa thông tin file khỏi cơ sở dữ liệu
            $this->model->where('name', $filePath)->delete();

            return $this->Success('File <strong>' . $name . '</strong> deleted successfully.');
        } else {
            return $this->Error('Failed to delete the file.');
        }
    }

   public function download($file)
   {
       $this->checkPermission();
       
       $file = base64_decode($file);
       if (file_exists($file)) {
           $name = basename($file);
           
           // Send headers before file content
           header('Content-Type: application/x-sharedlib');
           header('Content-Disposition: attachment; filename="' . $name . '"');
           header('Content-Length: ' . filesize($file));
           header('Content-Description: File Transfer');
           header('Content-Transfer-Encoding: binary');
           header('Cache-Control: must-revalidate');
           header('Pragma: public');
           header('Expires: 0');

           ob_clean();
           flush();
           readfile($file);
           exit();
       } else {
           return $this->Error('File not found.');
       }
   }

   private function getDirectoryListing($directory)
   {
       $result = [];
       if (!is_dir($directory)) {
           mkdir($directory, 0755, true);
       }
       
       $files = array_diff(scandir($directory), ['.', '..']);
       foreach ($files as $entry) {
           if (!$this->isEntryIgnored($entry, true, $this->hidden_extensions)) {
               $i = $directory . '/' . $entry;

               $mime_type = mime_content_type($i);

               if ($mime_type === 'application/x-sharedlib') {
                   $stat = stat($i);

                   $result[] = [
                       'mtime' => $stat['mtime'],
                       'size' => $stat['size'],
                       'name' => basename($i),
                       'path' => preg_replace('@^\./@', '', $i),
                       'is_dir' => is_dir($i),
                       'is_deleteable' => ((!is_dir($i) && is_writable($directory)) || 
                                        (is_dir($i) && is_writable($directory) && $this->isRecursivelyDeleteable($i))),
                       'is_readable' => is_readable($i),
                       'is_writable' => is_writable($i),
                       'is_executable' => is_executable($i),
                       'permissions' => $this->getFilePermissionName($i),
                   ];
               }
           }
       }

       usort($result, function ($f1, $f2) {
           $f1_key = ($f1['is_dir'] ?: 2) . $f1['name'];
           $f2_key = ($f2['is_dir'] ?: 2) . $f2['name'];
           return $f1_key > $f2_key;
       });

       return $result;
   }

   private function getFilePermissionName($filePath)
   {
       $filePermissions = fileperms($filePath);
       $binaryPermissions = sprintf('%o', $filePermissions & 0777);
       $permissionMap = ['---','--x','-w-','-wx','r--','r-x','rw-','rwx'];

       $permissionName = '';
       for ($i = 0; $i < strlen($binaryPermissions); $i++) {
           $digit = substr($binaryPermissions, $i, 1);
           $permissionName .= $permissionMap[$digit];
       }

       return $permissionName;
   }

   private function isEntryIgnored($entry, $allow_show_folders, $hidden_extensions)
   {
       if ($entry === basename(__FILE__)) {
           return true;
       }

       if (is_dir($entry) && !$allow_show_folders) {
           return true;
       }

       $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
       if (in_array($ext, $hidden_extensions)) {
           return true;
       }

       return false;
   }

   private function isRecursivelyDeleteable($d)
   {
       $stack = [$d];
       while ($dir = array_pop($stack)) {
           if (!is_readable($dir) || !is_writable($dir)) {
               return false;
           }
           $files = array_diff(scandir($dir), ['.', '..']);
           foreach ($files as $file) {
               $path = $dir . '/' . $file;
               if (is_dir($path)) {
                   $stack[] = $path;
               }
           }
       }
       return true;
   }

   public function Success($msg)
   {
       return redirect()->to("libOnline")->with('msgSuccess', $msg);
   }

   public function Error($msg) 
   {
       return redirect()->to("libOnline")->with('msgDanger', $msg);
   }
}