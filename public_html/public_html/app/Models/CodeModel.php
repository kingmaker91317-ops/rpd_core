<?php

namespace App\Models;

use CodeIgniter\Model;

class CodeModel extends Model
{
    protected $table      = 'referral_code';
    protected $primaryKey = 'id_reff';
    protected $allowedFields = ['code', 'set_saldo', 'used_by', 'created_by', 'contract_expired_at'];
    protected $useTimestamps = true;

    public function getCode($limit = 10, $order_by = 'DESC')
    {
        $this->limit($limit);
        $this->orderBy($this->primaryKey, $order_by);
        return $this->get()->getResultObject();
    }

    public function useReferral($code, $username = false)
    {
        $code = $this->checkCode($code);
        if ($code and $username) {
            $ok = $this->update($code->id_reff, ['used_by' => $username]);
            if ($ok) return true;
        }
        return false;
    }

    /**
     * checkCode
     *
     * @param  mixed $code
     * @param  mixed $dehash default true
     * @return object
     */
    public function checkCode($code, $dehash = true)
    {
        $code = $dehash ? create_password($code, false) : $code;
        return $this->getWhere(['code' => $code])
            ->getRowObject();
    }

    /**
     * isCodeExpired
     *
     * @param  mixed $code string or object
     * @return bool
     */
    public function isCodeExpired($code)
    {
        // If $code is already an object, use it directly
        if (is_object($code)) {
            $codeData = $code;
        } else {
            // Otherwise, fetch the code by string
            $codeData = $this->checkCode($code);
        }
        
        if (!$codeData) {
            return true;
        }
        
        if (empty($codeData->contract_expired_at)) {
            return false;
        }
        
        return strtotime($codeData->contract_expired_at) < time();
    }
}
