<?php

namespace App\Controllers;

class FileController extends BaseController
{
    private $db;
    public function __construct()
    {
        helper(['form']);
        $this->db = db_connect();
    }
    public function save()
    {
        $fileModel = new \App\Models\fileModel();
        $approveModel = new \App\Models\approveModel();
        //data
        $fullname = $this->request->getPost('fullname');
        $department = $this->request->getPost('department');
        $date = $this->request->getPost('date');
        $purpose = $this->request->getPost('purpose');
        $amount = $this->request->getPost('amount');
        $file = $this->request->getFile('file');

        $validation = $this->validate([
            'csrf_test_name'=>'required',
            'fullname'=>'required',
            'department'=>'required',
            'date'=>'required',
            'amount'=>'required',
            'approver'=>'required'
        ]);

        if(!$validation)
        {
            return view('new',['validation'=>$this->validator]);
        }
        else
        {
            if ($file->isValid() && ! $file->hasMoved())
            {
                $filename = $file->getClientName();
                $file->move('files/',$filename);
                $user = session()->get('loggedUser');
                $status = 0;
                $amt = str_replace(",","",$amount);
                $data = ['Fullname'=>$fullname, 'Department'=>$department,'date'=>$date,
                        'Purpose'=>$purpose,'Amount'=>$amt,'File'=>$filename,'Status'=>$status,'accountID'=>$user];
                $fileModel->save($data);
                //get the requestID
                $requestFile = $fileModel->WHERE('Fullname',$fullname)
                                         ->WHERE('Department',$department)
                                         ->WHERE('Amount',$amt)
                                         ->WHERE('accountID',$user)
                                         ->first();
                //send to approver
                $record = ['accountID'=>$this->request->getPost('approver'), 
                            'requestID'=>$requestFile['requestID'],
                            'DateReceived'=>date('Y-m-d'),
                            'DateApproved'=>'',
                            'Status'=>0];
                $approveModel->save($record);
                return redirect()->to('/new')->with('success', 'Form submitted successfully');
            }
            else
            {
                $validation = ['file','No file was selected or the file is invalid'];
                return view('new', ['validation' => $validation]);
            }
        }
    }

    public function forReview()
    {
        $approveModel = new \App\Models\approveModel();
        $searchTerm = $_GET['search']['value'] ?? '';
        $totalRecords = $approveModel->countAllResults();
        //request
        $builder = $this->db->table('tblapprove a');
        $builder->select('a.DateReceived,a.DateApproved,a.Status,a.approveID,b.requestID,b.Fullname,b.Department,b.Amount,b.Purpose,b.File');
        $builder->join('tblrequest b','b.requestID=a.requestID','LEFT');
        $builder->WHERE('a.accountID',session()->get('loggedUser'));
        $builder->groupBy('a.approveID');
        if ($searchTerm) {
            // Add a LIKE condition to filter based on school name or address or any other column you wish to search
            $builder->groupStart()
                    ->like('a.DateReceived', $searchTerm)
                    ->orLike('a.DateApproved', $searchTerm)
                    ->orLike('b.requestID', $searchTerm)
                    ->orLike('b.Fullname', $searchTerm)
                    ->orLike('b.Department', $searchTerm)
                    ->orLike('b.Amount', $searchTerm)
                    ->orLike('b.Purpose', $searchTerm)
                    ->groupEnd();
        }
        $request = $builder->get()->getResult();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => count($request),
            'data' => [] 
        ];

        foreach ($request as $row) {
            $response['data'][] = [
                'date' => date('Y-M-d', strtotime($row->DateReceived)),
                'code'=>'<button type="button" class="btn btn-link btn-sm view" style="padding:0px;" value='.$row->approveID.'>'."PCF-".str_pad($row->requestID, 4, '0', STR_PAD_LEFT).'</button>',
                'requestor' => $row->Fullname,
                'department' => $row->Department,
                'amount' => htmlspecialchars(number_format($row->Amount,2), ENT_QUOTES),
                'status' => ($row->Status == 0) ? '<span class="badge bg-warning">PENDING</span>' : 
                (($row->Status == 2) ? '<span class="badge bg-danger">REJECTED</span>' :
                '<span class="badge bg-success">APPROVED</span>'),
                'approve' => empty($row->DateApproved) ? '-' : date('Y-M-d', strtotime($row->DateApproved)),
            ];
        }

        return $this->response->setJSON($response);
    }

    public function accept()
    {
        date_default_timezone_set('Asia/Manila');
        $fileModel = new \App\Models\fileModel();
        $approveModel = new \App\Models\approveModel();
        $assignModel = new \App\Models\assignModel();
        //data
        $val = $this->request->getPost('value');
        $status = 1;
        $approveDate = date('Y-m-d');
        $user = session()->get('loggedUser');
        //update the status in approver module
        $approver = $approveModel->WHERE('accountID',$user)
                                ->WHERE('requestID',$val)
                                ->WHERE('Status',0)
                                ->first();
        $data = ['DateApproved'=>$approveDate,'Status'=>$status];
        $approveModel->update($approver['approveID'],$data);
        //update the request status
        $file = $fileModel->WHERE('requestID',$approver['requestID'])->first();
        switch($file['Status'])
        {
            case 0:
                $record = ['Status'=>1];
                $fileModel->update($approver['requestID'],$record);
                //send to first assigned personnel
                $assign = $assignModel->WHERE('Role','Verifier')->first();
                $record = ['accountID'=>$assign['accountID'], 
                            'requestID'=>$approver['requestID'],
                            'DateReceived'=>date('Y-m-d'),
                            'DateApproved'=>'',
                            'Status'=>0];
                $approveModel->save($record);
                break;
            case 1:
                $record = ['Status'=>3];
                $fileModel->update($approver['requestID'],$record);
                //send to first assigned personnel
                $assign = $assignModel->WHERE('Role','Pre-Final Approver')->first();
                $record = ['accountID'=>$assign['accountID'], 
                            'requestID'=>$approver['requestID'],
                            'DateReceived'=>date('Y-m-d'),
                            'DateApproved'=>'',
                            'Status'=>0];
                $approveModel->save($record);
                break;
            case 3:
                $record = ['Status'=>4];
                $fileModel->update($approver['requestID'],$record);
                //send to first assigned personnel
                $assign = $assignModel->WHERE('Role','Final Approver')->first();
                $record = ['accountID'=>$assign['accountID'], 
                            'requestID'=>$approver['requestID'],
                            'DateReceived'=>date('Y-m-d'),
                            'DateApproved'=>'',
                            'Status'=>0];
                $approveModel->save($record);
                break;
            case 4:
                $record = ['Status'=>5];
                $fileModel->update($approver['requestID'],$record);
                break;
        }
        
        //create log
        $logModel = new \App\Models\logModel(); 
        $data = ['Date'=>date('Y-m-d H:i:s a'),
                'Activity'=>'Approved PCF Number :'."PCF-".str_pad($approver['requestID'], 4, '0', STR_PAD_LEFT),
                'accountID'=>session()->get('loggedUser')];
        $logModel->save($data);
        echo "success";
    }

    public function reject()
    {
        date_default_timezone_set('Asia/Manila');
        $fileModel = new \App\Models\fileModel();
        $approveModel = new \App\Models\approveModel();
        //data
        $val = $this->request->getPost('value');
        $msg = $this->request->getPost('message');
        $status = 2;
        $user = session()->get('loggedUser');
        //approver
        $approver = $approveModel->WHERE('accountID',$user)->first();
        $data = ['Status'=>$status,'Comment'=>$msg];
        $approveModel->update($val,$data);
        //update the status of the request
        $record = ['Status'=>$status];
        $fileModel->update($approver['requestID'],$record);
        //create log
        $logModel = new \App\Models\logModel(); 
        $data = ['Date'=>date('Y-m-d H:i:s a'),
                'Activity'=>'Rejected PCF Number :'."PCF-".str_pad($approver['requestID'], 4, '0', STR_PAD_LEFT),
                'accountID'=>session()->get('loggedUser')];
        $logModel->save($data);
        echo "success";
    }

    public function viewDetails()
    {
        $val = $this->request->getGet('value');
        $builder = $this->db->table('tblapprove a');
        $builder->select('a.approveID,a.Status,b.requestID,b.Fullname,b.Department,b.Purpose,b.Amount,b.File');
        $builder->join('tblrequest b','b.requestID=a.requestID','LEFT');
        $builder->WHERE('a.approveID',$val);
        $data = $builder->get()->getRow();
        if($data)
        {
            ?>
            <div class="row g-3">
                <div class="col-lg-12">
                    <label>Complete Name</label>
                    <input type="text" class="form-control" value="<?php echo $data->Fullname ?>" readonly/>
                </div>
                <div class="col-lg-12">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label>Department</label>
                            <input type="text" class="form-control" value="<?php echo $data->Department ?>" readonly/>
                        </div>
                        <div class="col-lg-6">
                            <label>Amount</label>
                            <input type="text" class="form-control" value="<?php echo number_format($data->Amount,2) ?>" readonly/>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <label>Details</label>
                    <textarea name="details" class="form-control"><?php echo $data->Purpose ?></textarea>
                </div>
                <div class="col-lg-12">
                    <label>Attachment</label>
                    <a class="form-control" href="files/<?php echo $data->File ?>" target="_blank"><?php echo $data->File ?></a>
                </div>
                <?php if($data->Status==0){ ?>
                <div class="col-lg-12">
                    <button type="button" class="btn btn-primary accept" value="<?php echo $data->requestID ?>">
                        <span class="bi bi-check-circle"></span>&nbsp;Approve
                    </button>
                    <button type="button" class="btn btn-danger reject" value="<?php echo $data->approveID ?>">
                        <span class="bi bi-x-square"></span>&nbsp;Reject
                    </button>
                </div>
                <?php } ?>
            </div>
            <?php
        }
    }

    public function approveFile()
    {
        $fileModel = new \App\Models\fileModel();
        $searchTerm = $_GET['search']['value'] ?? '';
        $totalRecords = $fileModel->WHERE('Status',5)->countAllResults();
        //request
        $builder = $this->db->table('tblrequest a');
        $builder->select('a.*,b.DateTagged,b.Status as tag');
        $builder->join('tblmonitor b','b.requestID=a.requestID','LEFT');
        $builder->WHERE('a.Status',5);
        $builder->groupBy('a.requestID');
        if ($searchTerm) {
            // Add a LIKE condition to filter based on school name or address or any other column you wish to search
            $builder->groupStart()
                    ->like('a.requestID', $searchTerm)
                    ->like('a.Fullname', $searchTerm)
                    ->orLike('a.Department', $searchTerm)
                    ->orLike('a.Purpose', $searchTerm)
                    ->orLike('a.Amount', $searchTerm)
                    ->groupEnd();
        }
        $request = $builder->get()->getResult();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => count($request),
            'data' => [] 
        ];

        foreach ($request as $row) {
            $response['data'][] = [
                'date'=>date('Y-M-d', strtotime($row->Date)),
                'code'=>"PCF-".str_pad($row->requestID, 4, '0', STR_PAD_LEFT),
                'fullname'=>$row->Fullname,
                'department'=>$row->Department,
                'amount'=>number_format($row->Amount,2),
                'purpose'=>$row->Purpose,
                'release'=>($row->tag==0)?'<span class="badge bg-danger">No</span>':'<span class="badge bg-success">Yes</span>',
                'when'=>$row->DateTagged,
                'action'=>($row->tag==0)?'<button type="button" class="btn btn-primary btn-sm tag" value='.$row->requestID.'><span class="ri ri-logout-box-r-line"></span>&nbsp;Release</button>':'-'
            ];
        }

        return $this->response->setJSON($response);
    }

    public function release()
    {
        $monitorModel = new \App\Models\monitorModel();
        $balanceModel = new \App\Models\balanceModel();
        $val = $this->request->getPost('value');
        $data = ['requestID'=>$val,
                'DateTagged'=>date('Y-m-d'),
                'Status'=>1,
                'accountID'=>session()->get('loggedUser')];
        $monitorModel->save($data);
        //auto deduct the amount to the cash on hand
        $builder = $this->db->table('tblbalance');
        $builder->select('NewBal');
        $builder->orderBy('balanceID','DESC')->limit(1);
        $balance = $builder->get()->getRow();
        if($balance)
        {
            //get the approved amount
            $fileModel = new \App\Models\fileModel();
            $request = $fileModel->WHERE('requestID',$val)->first();
            //compute the new balance
            $newBalance = $balance->NewBal-$request['Amount'];
            $data = ['Date'=>date('Y-m-d'), 
                    'BeginBal'=>$balance->NewBal,
                    'NewAmount'=>-$request['Amount'],
                    'NewBal'=>$newBalance];
            $balanceModel->save($data);
        }
        echo "success";
    }

    public function addItem()
    {
        $listModel = new \App\Models\listModel();
        $fileModel = new \App\Models\fileModel();
        $validation = $this->validate([
            'csrf_test_name'=>'required',
            'date'=>'required',
            'request'=>'required|is_unique[tbl_list.requestID]',
            'particulars'=>'required',
        ]);

        if(!$validation)
        {
            return $this->response->SetJSON(['error' => $this->validator->getErrors()]);
        }
        else
        {  
            $record = $fileModel->WHERE('requestID',$this->request->getPost('request'))->first(); 
            $data = ['accountID'=>session()->get('loggedUser'), 
                    'requestID'=>$this->request->getPost('request'),
                    'Fullname'=>$record['Fullname'],
                    'Department'=>$record['Department'],
                    'Particulars'=>$this->request->getPost('particulars'),
                    'Amount'=>$record['Amount'],
                    'Status'=>0,
                    'Date'=>$this->request->getPost('date'),
                    'DateCreated'=>date('Y-m-d')];
            $listModel->save($data);
            return $this->response->SetJSON(['success' => 'Successfully submitted']);
        }
    }

    public function fetchItem()
    {
        $listModel = new \App\Models\listModel();
        $list = $listModel->WHERE('Status',0)->findAll();
        foreach($list as $row)
        {
            ?>
            <tr>
                <td><?php echo $row['Date'] ?></td>
                <td><?php echo $row['Fullname'] ?></td>
                <td><?php echo $row['Department'] ?></td>
                <td><?php echo $row['Particulars'] ?></td>
                <td><?php echo number_format($row['Amount'],2) ?></td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm close" value="<?php echo $row['listID'] ?>">
                        <span class="bi bi-x-square"></span>&nbsp;Close
                    </button>
                </td>
            </tr>
            <?php
        }
    }

    public function closeItem()
    {
        $listModel = new \App\Models\listModel();
        $val = $this->request->getPost('value');
        $data = ['Status'=>1];
        $listModel->update($val,$data);
        echo "success";
    }

    public function unSettle()
    {
        $sql = "Select a.* from tblrequest a INNER JOIN tblmonitor c ON c.requestID=a.requestID WHERE a.Status=5 AND c.Status=1
        AND NOT EXISTS (Select b.requestID from tbl_list b WHERE a.requestID=b.requestID)";
        $query = $this->db->query($sql);
        $unsettle = $query->getResult();
        foreach($unsettle as $row)
        {
        ?>
        <a href="#" class="list-group-item list-group-item-action" aria-current="true">
            <p class="mb-1"><b><?php echo $row->Fullname ?></b></p>
            <small>PhP <?php echo number_format($row->Amount,2) ?></small>
        </a>
        <?php
        }
    }

    public function addAmount()
    {
        $balanceModel = new \App\Models\balanceModel();
        $cash = $this->request->getPost('amount');
        $amt = trim(str_replace(',', '', $cash));
        $validation = $this->validate(['amount'=>'required']);
        if(!$validation)
        {
            return $this->response->SetJSON(['error' => $this->validator->getErrors()]);
        }
        else
        {
            $balance = $balanceModel->first();
            if(empty($balance))
            {
                //save
                $data = ['Date'=>date('Y-m-d'), 'BeginBal'=>$amt,'NewAmount'=>0,'NewBal'=>$amt];
                $balanceModel->save($data);
            }
            else
            {
                $builder = $this->db->table('tblbalance');
                $builder->select('NewBal');
                $builder->orderBy('balanceID','DESC')->limit(1);
                $record = $builder->get()->getRow();
                //add with new amount
                $newAmt = $record->NewBal + $amt;
                $data = ['Date'=>date('Y-m-d'), 'BeginBal'=>$record->NewBal,'NewAmount'=>$amt,'NewBal'=>$newAmt];
                $balanceModel->save($data);
            }
            return $this->response->SetJSON(['success' => 'Successfully submitted']);
        }
    }
}