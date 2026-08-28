<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoryModel extends Model
{
    protected $table      = 'history';
    protected $primaryKey = 'id_history';
    protected $allowedFields = ['keys_id', 'user_do', 'info'];
    protected $useTimestamps = true;

    public function getAll($limit = 10, $orderBy = "DESC")
    {
        return $this->limit($limit)
            ->orderBy('id_history', $orderBy)
            ->get()->getResultObject();
    }
    public function insertHistory($data)
    {
        $this->insert($data);
        $this->cleanupHistory();
    }
    public function cleanupHistory($limit = 50)
    {
        $totalRecords = $this->countAllResults();

        if ($totalRecords > $limit) {
            $recordsToDelete = $totalRecords - $limit;
            $idsToDelete = $this->orderBy('id_history', 'ASC')
                ->limit($recordsToDelete)
                ->get()
                ->getResultArray();

            foreach ($idsToDelete as $record) {
                $this->delete($record['id_history']);
            }
        }
    }
}
