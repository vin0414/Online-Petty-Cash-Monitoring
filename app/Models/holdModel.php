<?php

namespace App\Models;

use CodeIgniter\Model;

class holdModel extends Model
{
    protected $table      = 'tblhold';
    protected $primaryKey = 'holdID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['requestID', 'status','accountID','date','done'];
}