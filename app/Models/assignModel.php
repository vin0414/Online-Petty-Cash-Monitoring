<?php

namespace App\Models;

use CodeIgniter\Model;

class assignModel extends Model
{
    protected $table      = 'tblassign';
    protected $primaryKey = 'assignID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['accountID', 'Role','DateCreated'];
}