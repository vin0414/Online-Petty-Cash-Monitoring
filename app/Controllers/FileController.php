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
        $approver = $this->request->getPost('approver');

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
        $totalRecords = $approveModel->countAllResults();
        //request
        $builder = $this->db->table('tblapprove a');
        $builder->select('a.DateReceived,a.DateApproved,a.Status,a.approveID,b.requestID,b.Fullname,b.Department,b.Amount,b.Purpose,b.File');
        $builder->join('tblrequest b','b.requestID=a.requestID','LEFT');
        $builder->WHERE('a.accountID',session()->get('loggedUser'));
        $builder->groupBy('a.approveID');
        $request = $builder->get()->getResult();

        $response = [
            "draw" => $_GET['draw'],
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
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

    }

    public function reject()
    {

    }

    public function viewDetails()
    {
        $val = $this->request->getGet('value');
        $builder = $this->db->table('tblapprove a');
        $builder->select('a.approveID,b.*');
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
                <div class="col-lg-12">
                    <button type="button" class="btn btn-primary accept" value="<?php echo $data->approveID ?>">
                        <span class="bi bi-check-circle"></span>&nbsp;Approve
                    </button>
                    <button type="button" class="btn btn-danger reject" value="<?php echo $data->approveID ?>">
                        <span class="bi bi-x-square"></span>&nbsp;Reject
                    </button>
                </div>
            </div>
            <?php
        }
    }
}