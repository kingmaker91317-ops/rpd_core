<?php
namespace App\Controllers;
use App\Models\GameModel;
use App\Models\UserGameModel;
use App\Models\UserModel;

class Games extends BaseController
{
    protected $model;
    protected $userGameModel;
    protected $userModel;
    protected $user;
    
    public function __construct()
    {
        if (!session()->has('userid')) {
            return redirect()->to('login');
        }
        $user = (new UserModel())->getUser(session()->userid);
        if ($user->level != 1) {
            die('Access Denied');
        }
        $this->model = new GameModel();
        $this->userGameModel = new UserGameModel();
        $this->userModel = new UserModel();
        $this->user = $user;
    }

    public function index()
    {
        $data = [
            'title' => 'Games Manager',
            'user' => $this->user,
            'games' => $this->model->findAll()
        ];
        return view('Server/games', $data);
    }

    public function add()
    {
        $rules = [
            'name' => [
                'label' => 'Game Name',
                'rules' => 'required|min_length[3]'
            ],
            'package' => [
                'label' => 'Package Name',
                'rules' => 'required|is_unique[games.package]'
            ]
        ];
        if (!$this->validate($rules)) {
            return redirect()->to('games')->with('msgDanger', $this->validator->listErrors());
        }
        
        // Get allowed levels from checkboxes
        $allowedLevels = $this->request->getPost('allowed_levels');
        $allowedLevelsStr = is_array($allowedLevels) ? implode(',', $allowedLevels) : '';
        
        // Get require_password checkbox
        $requirePassword = (int)($this->request->getPost('require_password') === '1');
        
        $data = [
            'name' => $this->request->getPost('name'),
            'package' => $this->request->getPost('package'),
            'status' => 'active',
            'maintenance' => 0,
            'allowed_levels' => $allowedLevelsStr,
            'require_password' => $requirePassword
        ];

        if ($this->model->insert($data)) {
            return redirect()->to('games')->with('msgSuccess', 'Game added successfully');
        }
        return redirect()->to('games')->with('msgDanger', 'Failed to add game');
    }

    public function edit($id)
{
    $game = $this->model->find($id);
    if (!$game) {
        return redirect()->to('games')->with('msgDanger', 'Game not found');
    }

    $rules = [
        'name' => [
            'label' => 'Game Name',
            'rules' => 'required|min_length[3]'
        ],
        'package' => [
            'label' => 'Package Name',
            'rules' => 'required'
        ],
        'version' => [
            'label' => 'Version',
            'rules' => 'permit_empty'
        ],
        'link_update' => [
            'label' => 'Link Update',
            'rules' => 'permit_empty|valid_url_strict'
        ]
    ];

    if ($game['package'] != $this->request->getPost('package')) {
        $rules['package']['rules'] .= '|is_unique[games.package]';
    }

    if (!$this->validate($rules)) {
        return redirect()->to('games')->with('msgDanger', $this->validator->listErrors());
    }

    // Get allowed levels from checkboxes
    $allowedLevels = $this->request->getPost('allowed_levels');
    $allowedLevelsStr = is_array($allowedLevels) ? implode(',', $allowedLevels) : '';

    // Get require_password checkbox
    $requirePassword = (int)($this->request->getPost('require_password') === '1');

    $data = [
        'name' => $this->request->getPost('name'),
        'package' => $this->request->getPost('package'),
        'version' => $this->request->getPost('version'),
        'link_update' => $this->request->getPost('link_update'),
        'allowed_levels' => $allowedLevelsStr,
        'require_password' => $requirePassword
    ];

    if ($this->model->update($id, $data)) {
        return redirect()->to('games')->with('msgSuccess', 'Game updated successfully');
    }
    return redirect()->to('games')->with('msgDanger', 'Failed to update game');
}

    public function toggle($id)
    {
        $game = $this->model->find($id);
        if (!$game) {
            return redirect()->to('games')->with('msgDanger', 'Game not found');
        }

        $newStatus = ($game['status'] == 'active') ? 'pause' : 'active';
        
        if ($this->model->update($id, ['status' => $newStatus])) {
            return redirect()->to('games')->with('msgSuccess', 'Game status updated successfully');
        }
        
        return redirect()->to('games')->with('msgDanger', 'Failed to update game status');
    }

    public function maintenance($id)
    {
        $game = $this->model->find($id);
        if (!$game) {
            return redirect()->to('games')->with('msgDanger', 'Game not found');
        }

        // Get maintenance state from checkbox
        $maintenance = (int)$this->request->getPost('maintenance') === 1;
        $data = [
            'maintenance' => $maintenance ? 1 : 0,
            'maintenance_msg' => $this->request->getPost('maintenance_msg')
        ];
        
        if ($this->model->update($id, $data)) {
            return redirect()->to('games')
                ->with('msgSuccess', 'Maintenance settings updated successfully');
        }
        
        return redirect()->to('games')
            ->with('msgDanger', 'Failed to update maintenance settings');
    }

    public function delete($id)
    {
        if (!$this->model->find($id)) {
            return redirect()->to('games')->with('msgDanger', 'Game not found');
        }
        
        // Delete user-game assignments for this game
        $this->userGameModel->where('game_id', $id)->delete();
        
        if ($this->model->delete($id)) {
            return redirect()->to('games')->with('msgSuccess', 'Game deleted successfully');
        }
        return redirect()->to('games')->with('msgDanger', 'Failed to delete game');
    }

    /**
     * Assign game to specific users
     */
    public function assignUsers($gameId)
    {
        $game = $this->model->find($gameId);
        if (!$game) {
            return redirect()->to('games')->with('msgDanger', 'Game not found');
        }

        if ($this->request->getMethod() === 'post') {
            $userIds = $this->request->getPost('user_ids') ?? [];
            
            // Convert to integers
            $userIds = array_map('intval', $userIds);
            
            // Delete existing assignments for this game
            $this->userGameModel->where('game_id', $gameId)->delete();
            
            // Insert new assignments
            if (!empty($userIds)) {
                $data = [];
                foreach ($userIds as $userId) {
                    $data[] = [
                        'user_id' => $userId,
                        'game_id' => $gameId
                    ];
                }
                $this->userGameModel->insertBatch($data);
            }
            
            return redirect()->to("games/assign/{$gameId}")
                ->with('msgSuccess', 'Users assigned successfully');
        }

        // Get all users (level 2 and 3 only)
        $allUsers = $this->userModel->whereIn('level', [2, 3])->findAll();
        
        // Get currently assigned users
        $assignedUserIds = $this->userGameModel->getGameUsers($gameId);
        
        $data = [
            'title' => 'Assign Users to Game',
            'user' => $this->user,
            'game' => $game,
            'all_users' => $allUsers,
            'assigned_user_ids' => $assignedUserIds
        ];
        
        return view('Server/game_assign_users', $data);
    }
}