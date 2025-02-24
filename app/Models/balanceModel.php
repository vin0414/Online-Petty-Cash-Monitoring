<?php

namespace App\Models;

use CodeIgniter\Model;

class balanceModel extends Model
{
    protected $table      = 'tblbalance';
    protected $primaryKey = 'balanceID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['Date', 'BeginBal','NewAmount','NewBal'];
}