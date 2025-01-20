<?php

namespace App\Models;

use CodeIgniter\Model;

class fileModel extends Model
{
    protected $table      = 'tblrequest';
    protected $primaryKey = 'requestID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['Fullname', 'Department','date','Purpose','Amount','File','Status','accountID'];
}