<?php

namespace App\Models;

use CodeIgniter\Model;

class listModel extends Model
{
    protected $table      = 'tbl_list';
    protected $primaryKey = 'listID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['accountID', 'requestID','Fullname','Department','Particulars','Amount','Status','Date','DateCreated'];
}