<?php

namespace App\Models;

use CodeIgniter\Model;

class logModel extends Model
{
    protected $table      = 'tbl_log';
    protected $primaryKey = 'logID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['Date','Activity','accountID'];
}