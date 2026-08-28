<?php

namespace App\Models;

use CodeIgniter\Model;

class GameModel extends Model
{
    protected $table = 'games';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'package', 'status', 'maintenance', 'maintenance_msg', 'version', 'link_update', 'allowed_levels', 'require_password'];

    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    public function addIfNotExists($gameName, $packageName)
    {
        $existingGame = $this->where('package', $packageName)->first();
        if (!$existingGame) {
            $this->insert(['name' => $gameName, 'package' => $packageName]);
        }
    }

    public function getGames()
    {
        return $this->findAll();
    }

    public function getGameMap($default = []): array
    {
        $games = [];
        $games = array_merge($games, $default);
        $result = $this->findAll();
        foreach ($result as $row) {
            $games[$row['package']] = $row['name'];
        }
        return $games;
    }

    public function getGame($package) {
        $result = $this->where('package', $package)
            ->get()
            ->getRowObject();
        return $result;
    }

    /**
     * Get games that are allowed for specific user level
     * 
     * @param int $userLevel User level (1=Admin, 2=Reseller, 3=Tenant)
     * @return array Games array
     */
    public function getGamesForLevel($userLevel): array
    {
        $games = $this->findAll();
        $result = [];
        
        foreach ($games as $game) {
            $allowedLevels = $game['allowed_levels'] ?? '';
            
            // If allowed_levels is empty, game is available for all levels
            if (empty($allowedLevels)) {
                $result[] = $game;
                continue;
            }
            
            // Check if user level is in allowed levels
            $levelsArray = explode(',', $allowedLevels);
            if (in_array((string)$userLevel, $levelsArray)) {
                $result[] = $game;
            }
        }
        
        return $result;
    }
}