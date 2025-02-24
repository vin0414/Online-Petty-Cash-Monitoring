<?php

namespace App\Controllers;

class Compute extends BaseController
{
    private $db;
    public function __construct()
    {
        helper(['form']);
        $this->db = db_connect();
    }

    public function unliquidated()
    {
        $sql = "Select SUM(a.Amount)total from tblrequest a INNER JOIN tblmonitor c ON c.requestID=a.requestID WHERE a.Status=5 AND c.Status=1
        AND NOT EXISTS (Select b.requestID from tbl_list b WHERE a.requestID=b.requestID)";
        $query = $this->db->query($sql);
        $unsettle = $query->getRow();
        echo number_format($unsettle->total,2);

    }

    public function unsettle()
    {
        $builder = $this->db->table('tbl_list');
        $builder->select('IFNULL(SUM(Amount),0)total');
        $builder->WHERE('Status',0);
        $data = $builder->get()->getRow();
        echo number_format($data->total,2);
    }

    public function settle()
    {
        $builder = $this->db->table('tbl_list');
        $builder->select('IFNULL(SUM(Amount),0)total');
        $builder->WHERE('Status',1)->WHERE('DateCreated',date('Y-m-d'));
        $data = $builder->get()->getRow();
        echo number_format($data->total,2);
    }

    public function cashOnHand()
    {
        $builder = $this->db->table('tblbalance');
        $builder->select('IFNULL(NewBal,0)total');
        $builder->orderBy('balanceID','DESC')->limit(1);
        $data = $builder->get()->getRow();
        if(empty($data))
        {
            echo number_format(0,2);
        }
        else
        {
            echo number_format($data->total,2);
        }
    }

    public function total()
    {
        //unsettle
        $builder = $this->db->table('tbl_list');
        $builder->select('IFNULL(SUM(Amount),0)total');
        $builder->WHERE('Status',0);
        $data = $builder->get()->getRow();
        $unsettle = $data->total;
        //settle
        $builder = $this->db->table('tbl_list');
        $builder->select('IFNULL(SUM(Amount),0)total');
        $builder->WHERE('Status',1)->WHERE('DateCreated',date('Y-m-d'));
        $data = $builder->get()->getRow();
        $settle = $data->total;
        //unliquidated
        $sql = "Select SUM(a.Amount)total from tblrequest a INNER JOIN tblmonitor c ON c.requestID=a.requestID WHERE a.Status=5 AND c.Status=1
        AND NOT EXISTS (Select b.requestID from tbl_list b WHERE a.requestID=b.requestID)";
        $query = $this->db->query($sql);
        $data = $query->getRow();
        $unliquidated = $data->total;
        //cash on hand
        $cash = 0;
        $builder = $this->db->table('tblbalance');
        $builder->select('IFNULL(NewBal,0)total');
        $builder->orderBy('balanceID','DESC')->limit(1);
        $cashData = $builder->get()->getRow();
        if(empty($cashData))
        {
            $cash = 0;
        }
        else
        {
            $cash = $cashData->total;
        }

        $total =  $settle+$unsettle+$unliquidated+$cash;
        echo number_format($total,2);
    }
}