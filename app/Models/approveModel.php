<?php

namespace App\Models;

use CodeIgniter\Model;

class approveModel extends Model
{
    protected $table      = 'tblapprove';
    protected $primaryKey = 'approveID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['accountID', 'requestID','DateReceived','DateApproved','Status','Comment'];
}