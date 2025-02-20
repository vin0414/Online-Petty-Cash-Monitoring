<?php

namespace App\Models;

use CodeIgniter\Model;

class monitorModel extends Model
{
    protected $table      = 'tblmonitor';
    protected $primaryKey = 'monitorID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['requestID','DateTagged','Status','accountID'];
}