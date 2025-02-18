<?php

namespace App\Controllers;

class Home extends BaseController
{
    private $db;
    public function __construct()
    {
        helper(['form']);
        $this->db = db_connect();
    }
    public function index()
    {
        return view('welcome_message');
    }

    public function dashboard()
    {
        $title = "Dashboard";
        $data = ['title'=>$title];
        return view('dashboard',$data);
    }

    public function newRequest()
    {
        $title = "New PCF";
        $accountModel = new \App\Models\accountModel();
        $account = $accountModel->WHERE('Role','Department Head')->findAll();

        $data = ['title'=>$title,'account'=>$account];
        return view('new',$data);
    }

    public function manageRequest()
    {
        $title = "Manage";
        //request
        $user = session()->get('loggedUser');
        $fileModel = new \App\Models\fileModel();
        $files = $fileModel->WHERE('accountID',$user)->findAll();
        //data
        $data = ['files'=>$files,'title'=>$title];
        return view('manage',$data);
    }

    public function reviewRequest()
    {
        $title = "PCF Review";
        //data
        $data = ['title'=>$title];
        return view('review',$data);
    }

    public function configure()
    {
        if(session()->get('role')=="Admin")
        {
            $title = "PCF Settings";
            //data
            $data = ['title'=>$title];
            return view('configure',$data);
        }
        return redirect("/dashboard");
    }

    public function fetchAssign()
    {
        $assignModel = new \App\Models\assignModel();
        //assigned
        $builder = $this->db->table('tblassign a');
        $builder->select('a.assignID,a.DateCreated,a.Role,b.Fullname,b.Username');
        $builder->join('tblaccount b','b.accountID=a.accountID','LEFT');
        $builder->groupBy('a.assignID');
        $records = $builder->get()->getResult();

        $totalRecords = $assignModel->countAllResults();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
            'data' => [] 
        ];
        foreach ($records as $row) {
            $response['data'][] = [
                'Date' => date('Y-M-d', strtotime($row->DateCreated)),
                'fullname' => htmlspecialchars($row->Fullname, ENT_QUOTES),
                'username' => htmlspecialchars($row->Username, ENT_QUOTES),
                'role' => htmlspecialchars($row->Role, ENT_QUOTES),
                'actions' => '<button class="btn btn-success btn-sm view" value="' . htmlspecialchars($row->assignID, ENT_QUOTES) . '"><i class="fa-regular fa-pen-to-square"></i>&nbsp;Edit</button>'
            ];
        }
        // Return the response as JSON
        return $this->response->setJSON($response);
    }

    public function fetchUser()
    {
        $accountModel = new \App\Models\accountModel();
        //account
        $account = $accountModel->findAll();        

        $totalRecords = $accountModel->countAllResults();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
            'data' => [] 
        ];
        foreach ($account as $row) {
            $response['data'][] = [
                'date' => date('Y-M-d', strtotime($row['DateCreated'])),
                'username' => htmlspecialchars($row['Username'], ENT_QUOTES),
                'fullname' => htmlspecialchars($row['Fullname'], ENT_QUOTES),
                'role' => htmlspecialchars($row['Role'], ENT_QUOTES),
                'status' => ($row['Status'] == 0) ? '<span class="badge bg-danger">Inactive</span>' : 
                '<span class="badge bg-success">Active</span>',
                'action' => '<button type="button" class="btn btn-primary btn-sm edit" value="' . htmlspecialchars($row['accountID'], ENT_QUOTES) . '"><i class="bi bi-pencil-square"></i>&nbsp;Edit</button>'
            ];
        }
        // Return the response as JSON
        return $this->response->setJSON($response);
    }

    public function saveUser()
    {
        $validation = $this->validate([
            'csrf_test_name'=>'required',
            'fullname'=>'required|is_unique[tblaccount.Fullname]',
            'username'=>'required|is_unique[tblaccount.Username]',
            'role'=>'required'
        ]);

        if(!$validation)
        {
            return $this->response->SetJSON(['error' => $this->validator->getErrors()]);
        }
        else
        {
            
        }
    }
}
