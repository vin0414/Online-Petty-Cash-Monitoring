<?php

namespace App\Controllers;
use App\Libraries\Hash;

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
        // pcf
        $fileModel = new \App\Models\fileModel();
        $pending = $fileModel->WHERE('Status',0)->countAllResults();
        $approve = $fileModel->WHERE('Status',5)->countAllResults();
        $total = $fileModel->countAllResults();
        $files = $fileModel->orderBy('requestID', 'DESC')->findAll(10);

        $data = ['title'=>$title,'pending'=>$pending,'approve'=>$approve,'total'=>$total,'files'=>$files];
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
        $builder = $this->db->table('tblrequest a');
        $builder->select('a.*,b.DateTagged,b.Status as tag');
        $builder->join('tblmonitor b','b.requestID=a.requestID','LEFT');
        $builder->WHERE('a.accountID',$user);
        $builder->groupBy('a.requestID');
        $files = $builder->get()->getResult();
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

    public function manageCash()
    {
        $title = "Manage Cash";
        //file
        $fileModel = new \App\Models\fileModel();
        $files = $fileModel->WHERE('Status',5)->findAll();
        //balance
        $balanceModel = new \App\Models\balanceModel();
        $balance = $balanceModel->findAll();
        //data
        $data = ['title'=>$title,'files'=>$files,'balance'=>$balance];
        return view('manage-cash',$data);
    }

    public function configure()
    {
        if(session()->get('role')=="Admin")
        {
            $title = "PCF Settings";
            //logs
            $builder = $this->db->table('tbl_log a');
            $builder->select('a.*,b.Fullname');
            $builder->join('tblaccount b','b.accountID=a.accountID','LEFT');
            $builder->groupBy('a.logID');
            $log = $builder->get()->getResult();
            //accounts
            $accountModel = new \App\Models\accountModel();
            $account = $accountModel->WHERE('Status',1)->findAll();
            //data
            $data = ['title'=>$title,'log'=>$log,'account'=>$account];
            return view('configure',$data);
        }
        return redirect("/dashboard");
    }

    public function account()
    {
        $title = "My Account";
        //data
        $data = ['title'=>$title];
        return view('account',$data);
    }

    public function fetchAssign()
    {
        $assignModel = new \App\Models\assignModel();
        $searchTerm = $_GET['search']['value'] ?? '';
        //assigned
        $builder = $this->db->table('tblassign a');
        $builder->select('a.assignID,a.DateCreated,a.Role,b.Fullname,b.Username');
        $builder->join('tblaccount b','b.accountID=a.accountID','LEFT');
        $builder->WHERE('a.accountID<>',0);
        $builder->groupBy('a.assignID');
        if ($searchTerm) {
            // Add a LIKE condition to filter based on school name or address or any other column you wish to search
            $builder->groupStart()
                    ->like('a.DateCreated', $searchTerm)
                    ->orLike('a.Role', $searchTerm)
                    ->orLike('b.Fullname', $searchTerm)
                    ->orLike('b.Username', $searchTerm)
                    ->groupEnd();
        }
        $records = $builder->get()->getResult();

        $totalRecords = $assignModel->countAllResults();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => count($records),
            'data' => [] 
        ];
        foreach ($records as $row) {
            $response['data'][] = [
                'date' => date('Y-M-d', strtotime($row->DateCreated)),
                'fullname' => htmlspecialchars($row->Fullname, ENT_QUOTES),
                'username' => htmlspecialchars($row->Username, ENT_QUOTES),
                'role' => htmlspecialchars($row->Role, ENT_QUOTES),
                'action' => '<button class="btn btn-primary btn-sm delete" value="' . htmlspecialchars($row->assignID, ENT_QUOTES) . '"><i class="bi bi-x-square"></i>&nbsp;Remove</button>'
            ];
        }
        // Return the response as JSON
        return $this->response->setJSON($response);
    }

    public function fetchUser()
    {
        $accountModel = new \App\Models\accountModel();
        $searchTerm = $_GET['search']['value'] ?? '';
        if ($searchTerm) {
            $accountModel->like('Fullname', $searchTerm)
                        ->orLike('Username',$searchTerm)
                        ->orLike('Role',$searchTerm);
        }
        //account
        $account = $accountModel->WHERE('accountID<>',0)->findAll();        

        $totalRecords = $accountModel->countAllResults();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => count($account),
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
                'action' =>($row['Status'] == 1) ?'<button type="button" class="btn btn-primary btn-sm remove" value="' . htmlspecialchars($row['accountID'], ENT_QUOTES) . '"><i class="bi bi-x-square"></i>&nbsp;Remove</button>':''
            ];
        }
        // Return the response as JSON
        return $this->response->setJSON($response);
    }

    public function saveUser()
    {
        $accountModel = new \App\Models\accountModel();
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
            $defaultPassword = Hash::make("Fastcat_01");
            $data = ['Username'=>$this->request->getPost('username'), 
                    'Password'=>$defaultPassword,
                    'Fullname'=>$this->request->getPost('fullname'),
                    'Role'=>$this->request->getPost('role'),
                    'Status'=>1,
                    'DateCreated'=>date('Y-m-d')];
            $accountModel->save($data);
            
            date_default_timezone_set('Asia/Manila');
            $logModel = new \App\Models\logModel();
            //create log
            $data = ['Date'=>date('Y-m-d H:i:s a'),'Activity'=>'Added new user account','accountID'=>session()->get('loggedUser')];
            $logModel->save($data);
            return $this->response->SetJSON(['success' => 'Successfully submitted']);
        }
    }

    public function saveAssign()
    {
        $assignModel = new \App\Models\assignModel();
        $validation = $this->validate([
            'csrf_test_name'=>'required',
            'user'=>'required|is_unique[tblassign.accountID]',
            'level'=>'required|is_unique[tblassign.Role]',
        ]);

        if(!$validation)
        {
            return $this->response->SetJSON(['error' => $this->validator->getErrors()]);
        }
        else
        {
            $data = ['accountID'=>$this->request->getPost('user')
                    , 'Role'=>$this->request->getPost('level'),
                    'DateCreated'=>date('Y-m-d')];
            $assignModel->save($data);

            date_default_timezone_set('Asia/Manila');
            $logModel = new \App\Models\logModel();
            //create log
            $data = ['Date'=>date('Y-m-d H:i:s a'),'Activity'=>'Assigned new user account','accountID'=>session()->get('loggedUser')];
            $logModel->save($data);
            return $this->response->SetJSON(['success' => 'Successfully submitted']);
        }
    }

    public function removeAssignment()
    {
        $assignModel = new \App\Models\assignModel();
        $val = $this->request->getPost('value');
        $data = ['accountID'=>0,'Role'=>'N/A'];
        $assignModel->update($val,$data);

        date_default_timezone_set('Asia/Manila');
        $logModel = new \App\Models\logModel();
        //create log
        $data = ['Date'=>date('Y-m-d H:i:s a'),'Activity'=>'Remove the assigned user account','accountID'=>session()->get('loggedUser')];
        $logModel->save($data);
        return $this->response->SetJSON(['success' => 'Successfully submitted']);
    }

    public function removeUser()
    {
        $accountModel = new \App\Models\accountModel();
        $val = $this->request->getPost('value');
        $data = ['Status'=>0];
        $accountModel->update($val,$data);
        
        date_default_timezone_set('Asia/Manila');
        $logModel = new \App\Models\logModel();
        //create log
        $data = ['Date'=>date('Y-m-d H:i:s a'),'Activity'=>'Removed new user account','accountID'=>session()->get('loggedUser')];
        $logModel->save($data);
        return $this->response->SetJSON(['success' => 'Successfully submitted']);
    }
}
