<?php

namespace App\Models;

use CodeIgniter\Model;

class departmentModel extends Model
{
    protected $table      = 'tbldepartment';
    protected $primaryKey = 'departmentID';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['departmentName', 'token','DateCreated'];
}