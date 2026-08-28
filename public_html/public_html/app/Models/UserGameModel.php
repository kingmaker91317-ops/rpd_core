<?php

namespace App\Models;

use CodeIgniter\Model;

class UserGameModel extends Model
{
    protected $table = 'user_games';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'game_id'];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    /**
     * Get games assigned to a specific user
     * 
     * @param int $userId User ID
     * @return array Game IDs
     */
    public function getUserGames($userId): array
    {
        $result = $this->where('user_id', $userId)->findAll();
        return array_column($result, 'game_id');
    }

    /**
     * Assign games to a user
     * 
     * @param int $userId User ID
     * @param array $gameIds Array of game IDs
     * @return bool
     */
    public function assignGamesToUser($userId, array $gameIds): bool
    {
        // Delete existing assignments
        $this->where('user_id', $userId)->delete();
        
        // Insert new assignments
        if (empty($gameIds)) {
            return true; // No games to assign
        }
        
        $data = [];
        foreach ($gameIds as $gameId) {
            $data[] = [
                'user_id' => $userId,
                'game_id' => $gameId
            ];
        }
        
        return $this->insertBatch($data) !== false;
    }

    /**
     * Get users assigned to a specific game
     * 
     * @param int $gameId Game ID
     * @return array User IDs
     */
    public function getGameUsers($gameId): array
    {
        $result = $this->where('game_id', $gameId)->findAll();
        return array_column($result, 'user_id');
    }

    /**
     * Check if user has access to a game
     * 
     * @param int $userId User ID
     * @param int $gameId Game ID
     * @return bool
     */
    public function hasAccess($userId, $gameId): bool
    {
        $result = $this->where('user_id', $userId)
                      ->where('game_id', $gameId)
                      ->first();
        return $result !== null;
    }
}

