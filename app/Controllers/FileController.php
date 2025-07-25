<?php

namespace App\Controllers;
use Dompdf\Dompdf;
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
            return $this->response->SetJSON(['error' => $this->validator->getErrors()]);
        }
        else
        {
            $amt = str_replace(",","",$amount);
            if($amt > 2500)
            {
                $validation = ['amount'=>'The amount must not exceed 2,500.00'];
                return $this->response->SetJSON(['error' => $validation]);
            }
            else
            {
                if ($file->isValid() && ! $file->hasMoved())
                {
                    $filename = date('YmdHis').$file->getClientName();
                    $file->move('files/',$filename);
                    $user = session()->get('loggedUser');
                    $status = 0;
                    $data = ['Fullname'=>$fullname, 'Department'=>$department,'Date'=>$date,
                            'Purpose'=>$purpose,'Amount'=>$amt,'File'=>$filename,'Status'=>$status,'accountID'=>$user];
                    $fileModel->save($data);
                    //get the requestID
                    $lastInsertId = $fileModel->insertID();
                    //send to approver
                    $record = ['accountID'=>$this->request->getPost('approver'), 
                                'requestID'=>$lastInsertId,
                                'DateReceived'=>date('Y-m-d'),
                                'DateApproved'=>'',
                                'Status'=>0];
                    $approveModel->save($record);
                    return $this->response->SetJSON(['success' => 'Successfully submitted']);
                }
                else
                {
                    $validation = ['file'=>'No file was selected or the file is invalid'];
                    return $this->response->SetJSON(['error' => $validation]);
                }
            }
        }
    }

    public function edit()
    {
        $fileModel = new \App\Models\fileModel();
        $approveModel = new \App\Models\approveModel();
        //data
        $id = $this->request->getPost('requestID');
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
        ]);

        if(!$validation)
        {
            return view('new',['validation'=>$this->validator]);
        }
        else
        {
            //get the previous status
            $holdModel = new \App\Models\holdModel();
            $hold = $holdModel->WHERE('requestID',$id)->WHERE('done','No')->first();
            $filename = date('YmdHis').$file->getClientName();
            if(empty($file->getClientName()))
            {
                $amt = str_replace(",","",$amount);
                $data = ['Fullname'=>$fullname, 'Department'=>$department,'Date'=>$date,
                        'Purpose'=>$purpose,'Amount'=>$amt,'Status'=>$hold['status']];
                $fileModel->update($id,$data);
            }
            else
            {
                $file->move('files/',$filename);
                $amt = str_replace(",","",$amount);
                $data = ['Fullname'=>$fullname, 'Department'=>$department,'Date'=>$date,
                        'Purpose'=>$purpose,'Amount'=>$amt,'File'=>$filename,'Status'=>$hold['status']];
                $fileModel->update($id,$data);   
            }
            //update the hold status
            $datas = ['done'=>'Yes'];
            $holdModel->update($hold['holdID'],$datas);
            //send to approver
            $approver = $approveModel->WHERE('requestID',$id)
            ->WHERE('accountID',$hold['accountID'])
            ->WHERE('status',3)
            ->first();
            $record = ['Status'=>0];
            $approveModel->update($approver['approveID'],$record);
            return redirect()->to('/manage')->with('success', 'Form submitted successfully');
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
        $builder->groupBy('a.approveID')->orderBy('a.status');
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
                'status' => ($row->Status == 0) ? '<span class="badge bg-warning">Pending</span>' : 
                (($row->Status == 3) ? '<span class="badge bg-danger">On Hold</span>' :
                (($row->Status == 2) ? '<span class="badge bg-danger">Rejected</span>' :
                '<span class="badge bg-success">Approved</span>')),
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
                $assign = $assignModel->WHERE('Role','Prior Approver')->first();
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
        $approver = $approveModel->WHERE('accountID',$user)->WHERE('approveID',$val)->first();
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

    public function hold()
    {
        date_default_timezone_set('Asia/Manila');
        $fileModel = new \App\Models\fileModel();
        $approveModel = new \App\Models\approveModel();
        //data
        $val = $this->request->getPost('value');
        $msg = $this->request->getPost('message');
        $status = 3;
        $user = session()->get('loggedUser');
        //approver
        $approver = $approveModel->WHERE('accountID',$user)->WHERE('approveID',$val)->first();
        $data = ['Status'=>$status,'Comment'=>$msg];
        $approveModel->update($val,$data);
        //get the previous status
        $prevStatus = $fileModel->WHERE('requestID',$approver['requestID'])->first();
        //update the status of the request
        $record = ['Status'=>6];
        $fileModel->update($approver['requestID'],$record);
        //create on hold log
        $holdModel = new \App\Models\holdModel();
        $datas = ['requestID'=>$approver['requestID'], 'status'=>$prevStatus['Status'],'accountID'=>$user,'date'=>date('Y-m-d'),'done'=>'No'];
        $holdModel->save($datas);
        //create log
        $logModel = new \App\Models\logModel(); 
        $data = ['Date'=>date('Y-m-d H:i:s a'),
                'Activity'=>'Hold the PCF Number :'."PCF-".str_pad($approver['requestID'], 4, '0', STR_PAD_LEFT),
                'accountID'=>session()->get('loggedUser')];
        $logModel->save($data);
        echo "success";
    }

    public function viewDetails()
    {
        $val = $this->request->getGet('value');
        $builder = $this->db->table('tblapprove a');
        $builder->select('a.approveID,a.Status,b.requestID,b.Fullname,b.Department,b.Purpose,b.Amount,b.File,a.Comment');
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
                    <button type="button" class="btn btn-warning hold" style="float:right;" value="<?php echo $data->approveID ?>">
                        <span class="bi bi-exclamation-triangle"></span>&nbsp;Hold
                    </button>
                </div>
                <?php }else if($data->Status==2||$data->Status==3){ ?>
                <div class="col-lg-12">
                    <h5>Reason</h5>
                    <textarea class="form-control" readonly><?php echo $data->Comment ?></textarea>
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
        $builder->groupBy('a.requestID')->orderBy('b.Status');
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
        $list = $listModel->WHERE('Status<>',2)->findAll();
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
                    <?php if($row['Status']==0){ ?>
                    <button type="button" class="btn btn-primary btn-sm close" value="<?php echo $row['listID'] ?>">
                        <span class="bi bi-x-square"></span>&nbsp;Close
                    </button>
                    <?php }else if($row['Status']==1){ ?>
                    <button type="button" class="btn btn-success btn-sm settle" value="<?php echo $row['listID'] ?>">
                        <span class="bi bi-check-square"></span>&nbsp;Settle
                    </button>
                    <?php } ?>
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

    public function settleItem()
    {
        $balanceModel = new \App\Models\balanceModel();
        $listModel = new \App\Models\listModel();
        $val = $this->request->getPost('value');
        $data = ['Status'=>2];
        $listModel->update($val,$data);
        //return the amount 
        $builder = $this->db->table('tblbalance');
        $builder->select('NewBal');
        $builder->orderBy('balanceID','DESC')->limit(1);
        $balance = $builder->get()->getRow();
        if($balance)
        {
            //get the approved amount
            $list = $listModel->WHERE('listID',$val)->first();
            //compute the new balance
            $newBalance = $balance->NewBal+$list['Amount'];
            $data = ['Date'=>date('Y-m-d'), 
                    'BeginBal'=>$balance->NewBal,
                    'NewAmount'=>$list['Amount'],
                    'NewBal'=>$newBalance];
            $balanceModel->save($data);
        }
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
        <tr>
            <td><?php echo $row->Date ?></td>
            <td><?php echo $row->Fullname ?></td>
            <td><?php echo $row->Department ?></td>
            <td><?php echo $row->Purpose ?></td>
            <td><?php echo number_format($row->Amount,2) ?></td>
        </tr>
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

    public function print($id)
    {
        $dompdf = new Dompdf();
        $accountModel = new \App\Models\accountModel();
        $approveModel = new \App\Models\approveModel();
        //approvers
        $assignModel = new \App\Models\assignModel();
        $verifier = $assignModel->where('Role','Verifier')->first();
        $prior = $assignModel->where('Role','Prior Approver')->first();
        $final = $assignModel->where('Role','Final Approver')->first();
        //get the names
        $verifierName = $accountModel->where('accountID',$verifier['accountID'])->first();
        $priorName = $accountModel->where('accountID',$prior['accountID'])->first();
        $finalName = $accountModel->where('accountID',$final['accountID'])->first();
        //get the signatures
        $vpath = 'signatures/' . ($verifierName['Username'] ?? 'blank') . '.png';
        if (!file_exists($vpath)) {
            $vpath = 'signatures/blank.png';
        }
        $vtype = pathinfo($vpath, PATHINFO_EXTENSION);
        $vimg = file_get_contents($vpath);
        $verifierImg = 'data:image/' . $vtype . ';base64,' . base64_encode($vimg);

        $ppath = 'signatures/' . ($priorName['Username'] ?? 'blank') . '.png';
        if (!file_exists($ppath)) {
            $ppath = 'signatures/blank.png';
        }
        $ptype = pathinfo($ppath, PATHINFO_EXTENSION);
        $pimg = file_get_contents($ppath);
        $priorImg = 'data:image/' . $ptype . ';base64,' . base64_encode($pimg);

        $fpath = 'signatures/' . ($finalName['Username'] ?? 'blank') . '.png';
        if (!file_exists($fpath)) {
            $fpath = 'signatures/blank.png';
        }
        $ftype = pathinfo($fpath, PATHINFO_EXTENSION);
        $fimg = file_get_contents($fpath);
        $finalImg = 'data:image/' . $ftype . ';base64,' . base64_encode($fimg);

        $fileModel = new \App\Models\fileModel();
        $files = $fileModel->where('requestID',$id)->first();
        $preparedBy = $accountModel->where('accountID',$files['accountID'])->first();
        $preparedBypath = 'signatures/' . ($preparedBy['Username'] ?? 'blank') . '.png';
        if (!file_exists($preparedBypath)) {
            $preparedBypath = 'signatures/blank.png';
        }
        $preparedBytype = pathinfo($preparedBypath, PATHINFO_EXTENSION);
        $preparedByimg = file_get_contents($preparedBypath);
        $preparedByImage = 'data:image/' . $preparedBytype . ';base64,' . base64_encode($preparedByimg);
        //first approver
        $Approver = $approveModel->where('requestID',$files['requestID'])->first();
        $firstApprover = $accountModel->where('accountID',$Approver['accountID'])->first();
        $firstpath = 'signatures/' . ($firstApprover['Username'] ?? 'blank') . '.png';
        if (!file_exists($firstpath)) {
            $firstpath = 'signatures/blank.png';
        }
        $firsttype = pathinfo($firstpath, PATHINFO_EXTENSION);
        $firstimg = file_get_contents($firstpath);
        $firstApproverImg = 'data:image/' . $firsttype . ';base64,' . base64_encode($firstimg);

        if(!empty($files))
        {
            $template = '';
            $template.="<head>
                            <style>
                            table{font-family: sans-serif; font-size:12px;}
                            #vendor {
                                border-collapse: collapse;
                                width: 100%;
                                page-break-inside: auto;
                            }
                            
                            #vendor td, #vendor th {
                                border: 1px solid #000;
                                padding: 5px;font-size:12px;
                            }
                            #vendor thead {
                                display: table-header-group;
                                }
                            #vendor tbody {
                                    page-break-inside: auto;
                                }
                            #vendor tr {
                                    page-break-inside: avoid;
                                    page-break-after: auto;
                                }
                            
                            #vendor tr:hover {background-color: #000;}
                            
                            #vendor th {
                                padding-top: 12px;
                                padding-bottom: 12px;
                                text-align: left;
                                color: #000000;
                            }
                            </style>
                        </head><body>";
            $template.="<table style='width:100%;' id='vendor'>
                            <tr>
                                <td><img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABLAAAAT3CAYAAAD5fIkbAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAABmJLR0QA/wD/AP+gvaeTAACAAElEQVR42uzdeXwddb3/8fdnzpI0bZLudF8QKFBBpS0g4oICKosI2tIiIGuaJi0u140W9VyhRVyvQEtbxQUtYPG6X++96k/UiyxdUECkWBC6kJQCbZZuyTkzn98fKVCgLV2Sc+ac83o+Hii0Sc7Me+bMme87M9+RAAAAAAAAAAAAAAAAAAAHxogAABBXW2amj8mF0RkkAfSMRKD7qufn/kwSAAAg7pJEAACIqzCMrjPZB0gC6BlRpCd9ssbZXQpJAwAAxFlABACAONpU12uUZGeSBNCj3tAyKH0OMQAAgLijwAIAxFIyGc6QlCAJoIdPBt0/RwoAACDumAMLABA7PksVbbnUWkmDSQPoeUHg72QuLAAAEOvzFSIAAMRNWzY1RZRXQN5EUfAZUgAAAHFGgQUAiB/TTEIA8snPaGusOIIcAABAXFFgAQBiZXN96i2SjicJIK/Mo2g6MQAAgLiiwAIAxO2D6SpSAArisqY6VREDAACI6TgBAIB4aJmhfjJNIQm
                                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
                                YR8/bk0fUPr+mnbOrQ0O1QU3So3A416VCXDu3sjMYoCIIXN5O/ZrMBABA7OXM7f85JI58p5ZWkwAKw9xFWRsm2Z+1KkgDixaQf1d6izSS
                                xD8cx13yLT4G12U3fNQULaxd0PMHW6Vk33L9+RC4VjPfQjrEgGi+38ZIOizrUr+t99PLlU1RTAIDiPdexq+ccP/RPZXD+CwB71lKf/pC
                                Z/4QkgHgJZW/qf0vnwySxb1pnpO6XdEIBF+EJmW7eXpX9zpCvaStbpHtlVjQNTMmOketoyY+RNF7SG7Vz8mYAAEqVSXddPXHo+WZW8r+L4QosAH
                                sVmDfyW2kgdv6P8mo/uebLClJg/cUsuKF6Qcevma+se1y/ct1hURRMlNtEmb1Z8jfKdQjxAgDK0IOpdHBpOZRXEgUWgL1on54+OpK/iySAmHFbQAj7pyaV
                                XdqWS31NXU+J62lbJd1mod1Ys7hzFekfuBseXDssm0tMMNMEd00wsxOiSIMk7byPgNIKAFCurCkZhud8+k3DyubKbgosAHsUmTeKW42BuJ2sbKh5ofOn5LCf
                                qd2kjrYZ9h2Xz+7Bl3leZvPNOm+qma8XSH3/ZB7aMDiV9UnyaJJkEyVNzIU6xHZ+ChmfRgAAvGhLoOisz544Yn05rTQFFoDdeu4yVct0IUkAsbPY7lInMey/rCU
                                WJj33mR44/3lapv/Ymst+e9hibSPpfXPDg2uHZaPU24LIT3bT29QZHSfJ+L0JAAB7Fbn8wqsnDf9rua04BRaA3UqnUx+VVEMSQKzkcsnEt4nhwAxYsH1dy4zkL012X
                                nf8PJMedtPXawZnb7eMciS8d9cvf/bQSNHJkr9N0mm5UGNNLqevAgBgn7np09dMHP6Lclx3CiwArz0oStZmmkESQLyY+88H3LR9PUkc1AFuvkwHW2DdI/O51Qty/8v
                                E7LuXcQ9SK5vf7K53mOkdcp0cKRxEMgAAHJTF10wc9o1yXXkKLACv0VqfPMWko0kCiBcz3UwKB6fvwtwfWmak/m7SGw/g2/9iFtxQs6DjVyT5WpmHNgxOZv2d5n6qVj
                                SfLWko86wDANBt/pSt2jyrnAOgwAKwm1GyGgkBiJ1/9Lkl92di6I5DnBZK+1UG/sXdv9B3Ye4PpPeyzN2eTFY3n2huZ0l+6stzWAEAgG62KqjInpsZP76s50GlwALwCi
                                /M6jXCcrkPkAQQM6b53K7WPTo7srelK1LztPd5/lyy/5L7tbULs8tIrUvXPFbhqSad6mp+r1w17JYAAPTsEC0IwrOvPnb05nIPggILwCsPCtmwXsaxAYiZ9lwi+yNi6B6D
                                vqv21gbdJtfMPXzJ72WaXbugc3m5Z5W525Op3s+8U2bnSnZWpHC0RGUFAECebDf5OVdPGPkEUVBgAdiFT1a6LfArGJkAMWP6wYCb1EYQ3SgMblQQNWrXW95M93nk15T7rYKZ
                                u5+qTPZOn2ZmZ0nN50jBIewwAADk/2zFXBfOPn74X4iiCwUWgJe0DUhPljsDFSBmgsgWkUL3ql3Usbp1Rur/STrVXY/I7Nq+CzrvKtc8vvrQht4dHeG7ZTbZpA9KqmYvAQCgYN
                                zcr5x9/PCfEsXLKLAAvMy8gRCAmL0tpT9UL+z8O0l0v8h9bkLBgpqFnT8vx/nFMiuaBqbcznCPJnd2RqebWZq9AgCAWPi32ccP/x4xvOa8GACkzQ2pNweuv5IEEC+R2Yf6Lejkt
                                2/oFl9esak29O0fMtlFLr1DUkAqAADEh5t/6ZqJw79IEq/FFVgAJEkmzSIFIHanME19c9lfkQMOxtKlnnh8bNMpJrs49B3nSdabqQ4BAIjjoMwXUF7tGQUWAG3+uPpah6aSBBAv
                                blpgi5UlCRyI61c2TfBQFz+h5qmBbDCJAAAQ5xM/3Z6dOIyLCvaCAguAEp3JK1yqIgkgVjqTnbnvEAP2x3UPNo22yKfKgyuiyA9jsggAAIrC77K9N1+asWERUewZBRZQ5lyyNr
                                c6kgBi5yd9vqNniQGv54Z7nqvOVmY/Yq7LFGpS1xSn3CQIAECRuCdr+mBm/PhOotg7CiygzLVNT7xf0uEkAcSLmc8nBezN3BVNx8k1PafsBebqQyIAABQXNz2US3ScnXnL2G2k
                                8foosIByl0g0yvlNPRAnJj1UsyB3L0ng1TJ3P1WZrK48O3Cvc9epJAIAQHFy00M56dTMW8a2kMa+ocACylhLXcUb5NH7SAKInZuJALuat6LpSHe7RPIr5d6fXzsAAFDU/paTTs
                                tMHPY8Uew7CiygrI8A0Qy5AoIAYqVlW+/sHcSAG1evrmhr7fOBnVdbvUdypmQHAKDIufTXIIpOy5ww4gXS2M/hKxEAZXrg/IR6te3QJSQBxO6s5ntDvqatBFG+brh//YhsEDS2
                                t+hKkw/gaisAAErG8lyy43RuGzwwFFhAmWrvSF0gaQBJALHilggWEkN5un5l04Qw0sdy0lSTUiQCAEBJWZnNhu/LTKK8OlAUWEC5jpJdM0gBiBv735r5Hf8kh/KxaIWnntOGD5
                                r7x6NIJ3GPIAAAJenebFjx/sxJA9qI4sBRYAFlqG168q0uTSAJIF7Mw/mkUB4yf32qbzpX0fC8b2g0+TASAQCgZN2T7EidMefkAe1EcXAosIAy5AlrFJOqAHGzpvqF8L+JobRl
                                HtowONUZNihnH3OprzgYAwBQyu5JdqTO+OzJgyivugEFFlBm2mdpUJTTh0kCiBczX2B3KSSJ0nT9ynWHRVHi0+qMPipZBYkAAFDy7k6ng7M/PWkQD+fpJhRYQJkJw2SdSQyegH
                                jpsCj3fWIoPXNXrjvWo8SnokjTOO8CAKA8uPuvq3LRlE9OGradNLoPJ1JAOR1IJyvR5nYlSQCxc2f1Qm0khtJx/bLmSZH5FxXpTCZmBwCgjMZcrh/ktg674pOnWI40uhcFFlBGW
                                galzwncR5MEEDMmJm8vEXNXrjvWosQ1kfzDkuiuAAAor5O6m3KThnw8YxaRRfejwALKSMK9kemCgZhxPVh7S3Y5QRS3L63YcEzSo897pA87xRUAAGXIbpgzaejnyKHnUGABZaK9
                                IX1U5H4KSQAxO9Ux3UgKxev65c+8JVKQkUdnU1wBAFCWciavnz1p2K1E0bMosIAyEbk3isEVEDfPV2/P/pgYis+XVzSNCl1zIukKyQMSAQCgLG2V2fmzJw77L6LoeRRYQBnY2
                                KA+cl1EEkC8mOxW+752kETxyKxoGphy+1To/nHxRFcAAMqWSxsSgc66esLQlaSRHxRYQBmo8NRHJdWQBBArUZQIFhFDcfjqQxt6d2bDmXLNlpzjKQAAZc2eSAS59189YeQTZJE/
                                FFhAGXCpnnsHgdi9M/+r7807niKHeFu0wlPPedP0bEf0eZkNJhEAAMren7LZ3HmZk0ZuIor8osACStzmhuQp5nojSQAx45pPCPF27fJnTn3Bm//DZOOd3wIAAMDpm3Rrrmpz
                                Q2b8+E7SyD8KLKDEJVyNTgxA3DxZMyT3O2KIp3krmo5019ckncnxEwAASAplPueaicNvIIrCocACStjWmVXDcmH2AyQBxIxpvmUUEUS8ZO5d1z+dTHzRXQ2cIwEAgJ22mPwjsyc
                                O/yVRFBYnZ0AJy4W5GZJSJAHEh0vbrSP7A5KIj0UrPPW8N8+U9AWX+pIIAADY6Ukp+ODsSUP+ThSFFxABUKKD5MlKS7qCJIDYWVJ7q5j0MybmLWt+5wve/FdJ3xDlFQAAeGlA
                                pf/NZsPj51BexQZXYAElqm1g+sOSDyEJIHYWEkHhfWXZxiGdlvuKyy+UxBTtAABgV4uzW4c2Zk6xHFHEBwUWULK8kQyA2PlL31uyK4mhcDLuQWpF8xVZ5b5iUi2JAACAXeyQ
                                bPqcSUNvI4r4ocACStDmhtSb5TqJJICYMZtPCIUz74H1b/UVzQskvZk0AADAK8/TtFoWfnjOhJEPE0Y8UWABpXjsdc0kBSB2nqtJdP6UGPIv89en+qZyFTd417yAzP8JAABe
                                OX6S7urMVVyROXFAG2nEFwUWUGI2f1x9rUNTSQKI3anRIrtJHeSQX/Me2HCW56JbJI0gDQAA8Co5mV9z9YRhXzEzJ454o8ACSkyiI3mZS71JAoiVMAqC7xBD/rw8SXt0EWkAA
                                IDdWGdRdP7sE0bcN4csigKX0QMlxCVz2XSSAOL23vRf9Ju/Yw1J5CFrd5u3/JnLs5Z7zCTKKwAA8Fqmn2Wz4ZtnnzDiPsIops0GoGS0zki8Xwp+QxJAvLj7e/ouzP2BJHrW
                                9SvXHRZFicWSTiENAACwG9vd9MlrJg5bSBTFhyuwgJKSaCQDIHYeq12Yu5sYeo6729zlTXVRlPirKK8AAMDumP6hIDyxmMurI5auG17Om5ACCygRmxsrR0v+PpIAYsa1wCQm
                                Be0h1y17ZuT1K5p/K2mRpD4kAgAAXn02ZrKbs+0dE+ZMGPlwsa7E+CVPvdly4bhy3pBM4g6UCIuimZISJAHEypYwyv6QGHrGdSuaJ5v7Qpf6kwYAAHg1lzbIvW7O8cN+Vczrc
                                cySNf06A934eGJUWV9pToEFlMKB+RPq1bbDLyUJIHZnTbf1X6xWguhemYc2DE5lo4VyP5c0AADA7ph0l0XRjNknjHihuM8n3XJ3rv2eue7WFAvLeZtSYAEloH17appMA0gCi
                                JdE0pggtJvNW9Z0nndGCyUNIg0AALAbz7tZw5yJQ+8qhZUZ9+N1s106WxZ9vNw3LAUWUAI80Axm2AHixaQ/9rm58xGS6B5ffWhD745s9E13XUkaAABgD2dgv0x5YvpnJg3eUA
                                prM+7HT59ikf+7TP+1aurYp8t961JgAUWudUbqRLkmkgQQL5HZfFLoHnOXb3hjZ2d0h0lvJA0AALAbmyRdPWfS0MWlskLjlz41JAxtiaSERbqJTcxTCIFS0EgEQNxYc22u8x
                                fkcHDc3eYua/qYFK0Q5RUAANjdWZd0lyXsyDmThpVMeTVh0YpUFAZ3SRoq1+OPTRv1e7Y0V2ABRa19lgZFOX2YJIC4iRbaYmXJ4cDNXfHc0Hkrmn8g02mkAQAAdmOdyepnTxr
                                6m1Jbsa3VA78s6WRJUuA3y4wJY8QVWEBRC3PJKyVVkgQQK9mUp24lhgM3b3nzGfLsQxLlFQAAeI2cSd9IdqTGl2J5ddQdT58rs09IkptacrnsbWzyLlyBBRQpn6xEm4zJjI
                                H4+WnVwu3PEMP+W7rUE6vHNn3e5Z8Xv2QDAACvtcIjzZhzwrAVpbhyRy1Zd7gr+p4kkyRzX/DEhYe3sdm7UGABRap9QPoDko8hCSBeTM7k7Qcg89CGwU90Ni+R7FTSAAAAr7JZ
                                rn/PThp6U8YsKsUVHPeLVdW+LfqZpNqdf7QjkXAmb98FBRZQpFzO5O1A/DxafUvuHmLYP3OXrX+POqMlLh1CGgAAYBehpO9kTddkJg17vmTXMuOBbV+7RNL4l//Qbn10ytgN7
                                AIvo8ACilB7Q/qoyP3dJAHEi0s3msQkm/ual7vNW9n0GbnNlZQgEQAAsIs/JaLoY587YcRDpb6iR45be51cZ+/yRzlPRF9nF3glCiygCEXyBu28LxpAbLTu6J1dQgz7JrOiae
                                DcFc1LTHY6aQAAgF2scbNPXzNx6F3lsLJH3rmmTq6rd/0zk+5cNWXMU+wKr0SBBRSZjQ3qI9dFJAHEjOv7Q76mrQTx+q5f/sxbIumnksaQBgAA2KlV0ld7ZcNvfPKkkdvLYYX
                                H3bn2HPlr5k91md3A7vBaFFhAkUlHqYtkL03sByAe3Cy4hRhe33XLn5kWyb4jVxVpAAAASZ2Svp9NB5/PvGnIxnJZ6XE/fvoUi/xOvbqXMf36samj/s5u8VoUWEDxmUEEQM
                                yYfl+zoONxgtizpUs9sXrshrmSf5Y0AACApJxLdyRy9oWr3zr06XJa8SPu/Neb5PZTSZWv/js3cfXVHlBgAUVkc33yXWY6hiSAeDGz+aSwZ/MeWD/giaD5TkmnkgYAAGXPTf
                                qJouiaOSeM+Ge5rfyRd64/Qh7+VlLf3ZxV/vHx80f9hV1k9yiwgCKSkBp5vBkQO2urN3b+mhh2b+6KpuPc9TNJo0gDAICyFsn8J6ES131h4pBHyjGAI+94aszO8mrw7v4+kL7A
                                brJnFFhAkdg6s2pYLsyeQxJAvLh8od2lkCRe67rlTR+S6zaJ+a4AAChjkbv/JmH6wtUTh/+1XEMYv/TJUWEY/D9Jo/fwJb/9x7RR/8fusmcUWECRyEW5ekkpkgBipSOZzX2
                                XGF5r7rKmj0n6hqSANAAAKEuRSf8p0xfmTBq+qpyDOGrJv0aHYeJuSWP39DVm0RfZZfaOAgsoAl6nVJtHl0tGGEC8LO3zHT1LDC+7cfXqivaW3oslXUwaAACUpR2SbguC8KtX
                                Txj5RLmHcdSSf432YO/llaRfPzZ17P3sOntHgQUUgbZk+sNyH0YSQMwEYvL2Xcx7YP2A9pbgPyW9kzQAACg7reb6QSKZu+Gzx41qIo6uOa9cwd2Sxuzlyzwy+xJpvT4KLKAYu
                                DcSAhA7f62dn32AGLpce/+6wz0Ifi3pCNIAAKCsPCXXt9IVwXc+/aYhW4mjy+F3Pn2U3H4racTev9J+9s+po5aT2OujwAJibvP01JskvY0kgHgx6SZS6HLtyg3vDqLop5JqSQ
                                MAgLJxr9y+dfjTQ/5zyhTjgTa7OGLJuuMDD38tadDrfGkUWY6rr/YRBRYQcwlToxMDEDebt4TZHxODNG9584c9in4oqZI0AAAoeR0m/VJR9M3ZJ4y4jzhe66jb15zmiv5Tsu
                                rX/WL3pf+cduhDpLZvKLCAOI+QP66+3qELSAKIF5fdOmyxtpV7DnOXNX3M5TxpEACAkmdNUvTtrNnNmYnDnieP3Rt359pL3X2x9q1ryXkyypDavqPAAmIs6ExdKqk3SQCx4h
                                bZ4nIOYOlST6we2/QtSczPBwBA6Qpd+l/JFx/x1NBfc5vg3o27c83HzP2b2vdHx3/v8SmHPk5y+44CC4jrCFmyNlc9SQAxY/bftYs6Vpfr6t+4enXF6tam2+Q2hZ0BAICS
                                tF6yJUFOC69+69CniWPv3nW3J5ufXfMtczXsx7ftSCp5LentHwosIKbaGpLvlfM0LyB2wnB+ua76vAfWD2hvSfxK8reyIwAAUFI6JPtZpOjWcOKwP2TMIiJ5fccsWdNvQ/P
                                apWZ26n59o9n8v08dvo4E9w8FFhBXHjRKTN8OxMyTNUPD/ynHFb/u3nXDPRH8Vu5HsxsAAFAaIw5J97nbklwud2fmpJGbiGTfHbVk3eHZIPqlpCP381u3ZDs7v0KC+48CC4ihz
                                Y2VoxWF7ycJIF7M/RbLqOx+I3ntAxvGWhD9Tq43sBcAAFD0Vkn+4yiMlnz+xJGriWP/HXnn2tMjRT82V9/9P6HUV5+8+LCNpLj/KLCAOA6So6hRUoIkgPhwabslct8vt/Weu3Ld
                                sR5F/ytpCHsBAABFO8JoMvnSKNKSa04YtoI8DtyRd66pk/t8O7A+5flcrvM/SPHAUGABcRskz1JFW84vIQkgbud9uqNmvl4op1W+flnzpCjy/zZpADsAAABFZ525fhYFuis
                                3Yci9zGt1cMYv3dgnzG1fKNdHDvh00jX3iQsPbyPNA0OBBcRMey51gaRBJAHETKhbyml1r1254d1RFP1cUjUbHwCAImF62iL9Mgp015wJQ/9iZkyq2w2OunPtG8Nw+12y/Z7v
                                6iUurdvRES0kzQNHgQXEjJsamLsdiJ37+y7Kls3l9tctb/qQRdHtktJsegAAYi2UdL+Zfp1T8F9fmDjkkRf/4hqy6RZH3bnmYndfIKn3wQ30NPvpS8fuINEDR4EFxEhrY+oERZ
                                pIEkDc2PxyWdO5y5qnSL6EcwQAAGJrq7vfbWa/Snnyl585fvAGIul+Y773VGVlZXCDu67qhh/318f/Oep2Uj04nJwCcRKpkRCA2Hm+ZnvnT8phRectb7rI5d8TD5EAACBOOiUt
                                c/M/BFHwh86tQ/6SOcVyxNJzjvzRumOUiG6X9Mbu+HkW2MeVYQ6yg0WBBcREW50GujSZJIB4Mdm37fsq+cu95y5/5jKXvi0pYKsDAFBQoaS/mesvUWD35HLp/82cOICJv/PB3
                                Y788dor5dE3JVV100/9yWPnj/oz4R48CiwgJqJE8kqTKkkCiNcJZOjB4lJfybnLm+ok3SLKK8Rg6CCpRdLWnf9skdRmUujSVuu6CmHnF1q75Lld/rvFtHMWSfNI8tZ9f9WgUlK
                                vvXxFleQVO1+oj5lSLqs0eS9JSX/5YQf9ul5efdyUUtfnei82K4DXGwpIeljS3RYFf+j01J8prPJv/NInR4V3rP2+TKd044/ttCiYTbrdgwILiMPZ+mQl2mR1JAHEbSTtv+q3
                                cMfTpbyOc5c3N0p+kyRji+MgtUh6QdIml14w1yYz2+TumxX4VnNrkbQtkra5vNWCxBZTdlsQpbd4lG2tDH3bJ08aub0Ug8nc7cmKAWurEzt6VYTmVQrCfi5VuVTlbjWBRzVuq
                                pJUJQ/6SapyeVUg1URStXVdBdBbXQVZtaS+klLsckDR2izXMg/8gcCDBzqzufszJ43cRCyFM+7ONZPDUAtl6t+tP9jspsc+MnI1CXcPCiwgBtoHps+WfAxJALFT0pO3z13xzC
                                y5f0uUV9g9l6xZ8iZJz7pso+TN5tro5hsDD5oU6Tml9dxhTwzZNGWKhUS2ezvnqtncrT9zRVNVSqnayLJ9TUGtKar10PuaWa3M+0lWq0h9JdXKVCu99O/95OovrgwD8iUn6WGZ
                                3y8PHjDzZVdPGPq4mfHc8Rg4bGnzoFTYebO7pvTAj38hFfpcUu4+nLACMdA6I/U7SaeSBBArq2tuyY576ZakEjNvedN077ptkHOB8tUh01q5rXVprUlrzPS0S2sjBWvDXs+vy
                                4wf30lMpekb967rla30frkw3d896h8E6u/u/d2D/hZ4f7n6m9TfZf1d3t+k/ur6p5r0gD3KSnrcpEci0wpJy6o6w5WlenVpsRt355rJ5j5fskE98gKuq1ZdMPomku4+XIEFFF
                                jr9IrDpeg9JAHEjOvmUi2v5i5rvsTlC0R5VQ42y/1xWfAPU/S4e/C0J2yteWLNnImDmomnfO0cUG+X1LQ/37dohadao+cG7FA4IJAPkGuAJTRAkQZJGqDAB8iDAZIPkLTrPx
                                xvUFpMz7jrEckeNvdHEh49sqNP62MU//E3bunTYy20hXKd3oOHplW9259bSNrdiwILKLRENFPOSR0QM9vcsj8sxRW7bvkz0yT/jpiwvZS4pLUuPW6mx1xa5W6rgjB4bM6Jhzx
                                LPOhO0ydaVtKGnf/s2w7qbv++snlAwn2AeVehZQoGKFBX0RVpkAJ1lWHSgEhd/y/m+UIsWJPkq930T5M/IgV/z3bmHmLOqiKU8eDII9deoVBfl9SnJ18qMv/kyukTs4Teze9
                                GIgAKZ2OD+lR4ar2kWtIAYvThaFpUsyBbX2rrdd3ypg+ZdKf4BVYxe9allZL+ZqZHJa1Kp4LHP/2mIVuJBiU31rz/hZqKRG5gqGigSQPku1zVZd7fZP3d1b9r0mXrJ/mLtzkC
                                +31slbTaZavNfbWZrXYPn0hXJFdzfC0N4+5YO9Hkt0ia2OPnkdIvHps2+oOk3v04gQUKKO2pC0V5BcROzm1Bqa3TdcueOduk2/nsLx4ubTBppZuvlOvBVOgrP3viiPUkg3K
                                ROXFAm6Q2Sf/ar++7d13/igr1V5jsFyW8v0feXx70CwLfWXh5P3nQX11ze/Xzl+f3qiD1khTKtEFua02+3qX1Lq2VtM5MT2VzFU/s3NdQgsYvXdc/DKMvSt4oKZGHl+xQFHy
                                a5HsGV2ABBdQ2I/WQS8eSBBAr/1d7S/YdpbRCc5c/c5pkv2JwFmvN7r7STCtNejDKRiuvOWnkM8QC5E9mRVNVr1zUP5fy/hYm+0Xu/YPA+0ce9HtxYvud5dcAyfvpxSc7dk1
                                snybBvAtdes6k59X1/xvkei6SrTOL1pu0LnKtzW0d1rzzSaAoI++625Mbnl17mVxzJQ3M1+u67NrHp436AlugZ1BgAQXSPiP5zkj2R5IA4sXNpvZd0PnjUlmfeSs2nOAe/V4
                                9PNcD9nM3k/7h5n/ySH9WLrqHsgoobpm7n6pM11ZWWxhUKwj75SKrNo+qLbBqc1W7eV/Jas2tOnKvNlO1STXe9U/KpFqZKuSq2nm8Lqf5vyJJrZI2u9RqUptkrZK3Sd4q6Xk
                                peM6kZyP3jUFkzyut565+y5DnzczZ+/BqR96+7p2y8EbJ8n2hwPp0tuLIhy/mttOeQoEFFEjbjNRSlyaTBBCrj8Xmmuc7x9hdKoknCM27v/loT/if1TVnDAo7OHtYpj95qD/
                                lEvq/zMRhzxMLgL25/uE1/Tq8IlXRaX2yHvVKJbwycqsJ5KlQVmuRV8hUJUlu6mPyVNcAz1LutssvLbxWLz+4o9KkXpLkklnXVWSv4FKHSdv26yBnajFXJFmLy0Mzb3NZ1lx
                                b5NbpgbYqina4absFiS1B1tuiKNeaq61ozYwfvIWtje5w9O1rxkdmX5L8vEK8vpumPD519F1siR48UycCIP+21mloLpFaI56uA8TsU9H+vXZBZ6YUVuW6Zc+MNLN7JI1iw+Z
                                dKOlv5vpLFNg9uc7c/+NpVQAA9Iwj73hqjBRcLely5Weeq9dw6f8enzrqneKqwB7FRK5AAeQS6Xrt/C0ZgPi8NVNR4tulsCKZhzYMts7o96K8yqfHJP1P4PptR+/kPVxRAA
                                BAz3rjHc+MzCn3KUnTVdh5PnPyYCblVc+jwALyzOuUalV0uXEBJBArJv2sauH2op+HKHP/CzWpzo7fSDqCrdqjtpp0n7t+7Un9/Jrjhq0hEgAAet74pU8NCcPg6pxyhS6uusZ
                                3ppsfnzbyYbZMz6PAAvKsLUh/yOTDSQKIF5PPL/Z1uHH16ootLR2/cGkCW7RHPCXTLyMLfhm2HfJnnmoFAED+HLa0eVAi6vxUGGqm1DX/WwysV6/tPHUwTyiwgDxz+QyuvQJ
                                i59HqW3J/Kupji7vNXdH8bZPexebsRqZ/yP2uILBffe64oQ/yxCsAAPLrqCX/Gu1B4pMKO69QfIqrnacJftWqc45sZyvlBwUWkEftjenxUeTvIAkgXtxV9FdfzVux4XqTLm
                                JrHvzuIOk+l+5KhdFPPnviiPUv/sXVZAMAQN4cdefaN0bun3FpqmL48CuT/fdj00b/jC2VPxRYQD5HRZFfRQpA7LSHqeySYl6Bucub6iT/LJvyoM5C/yH3uyJL/vDzEw95kkA
                                AACiMI25fc3Jg+qy7n2mK7cTB26JE1MjWyi8KLCBPNn9cfb1DHyEJIGZMPxhwk9qKdfGvW/7MOZIWsCEPaNv/w6QfRpEvuWbS8HUEAgBAgWQ8OPLItWeY67MunRz/BfYvPj5l
                                zFNsuPyiwALyJOhIXSKpN0kAMXtvRraoWJf9+mXNkyL5EkkJtuQ+2yTpJ2764TUTh91DHAAAFM5hP1pdk0ympypa+zG5ji6KiSZNj/Ruff5bbL38o8AC8sAla5PqSQKI2/mH
                                /lC9sPPvxbjs1y9/9tBI4X+JYnxfdEr6lcm+27llyG95eiAAAIU1bum/xlmYaJB0mVx9VDxPuYrkXr9y+sQsWzH/KLCAPGirT54uaRxJAPESmhXl5O2Z+1+oidTxC0mD2Ip7
                                YVot+a3KJb8/58RDniUQAAAK5113e7J5w9oPmnymQntncZ5aaPFj08bcy9YsDAosID9HOib4A2LHm/rmsr8qtqXO3O3JVNC8VNIb2Ya7tUPST8ztO1dPHPJnM3MiAQCgcI5Y
                                um64heGlGzasrTdpuIrocqtXWd+RyH2OLVo4FFhAD9tU12uUlDuDJIDYucUWq+gu/071afoPyd7L5nuNZskXWyKYP/u4oc9J0mwyAQCgIA77zeqKZEv6dDNd5GF0rmRF3z24
                                2cx/TXlDK1u3cCiwgJ5+kyXCBmeCZSBuOhPZ3LeLbaHnrmi6Qs4Vna+yUrIbs1uG3M7cVgAAFNbRt68ZH5ousla/TKZBpXIZtEs/fHzqqF+whQs8tiYCoAcPdLNU0ZbzS0kC
                                iJ2f9PmOimpOpOtXNJ0euW5h00mSOuT6YRgEN35h4pBHiAMAgMI5ZsmaftmEJss1PZKO67pB0EppFZ8PE+l/Y0sXHgUW0IPac6mpkgaTBBAvZl5Uk7dfu7LpqCjSUj631SL
                                TLakoeeNnjh+8gT0ZAIDCOOxHq2sSQfoDZjo/K50uV7pU19VNDU9M6ZqeAIVFgQX05MFO3OoDxI1JD9UsyBXN02NuuOe56pxnfyKptoyPpRtMviib7PyPzFvGtrAXAwCQfy
                                OWruvVJxed6qbJJp0nqXcZrPYvH586+i62fjxQYAE9pLU+dbykSSQBxM7NxbKgGfcgu7zpdnM7uiy3lGm1yeYO1JDbp0+0LLsuAAD5NeZ7T1VWVgSnuWmyhdG5MvWx8ln9T
                                YlENJ29ID4osICeG3hx9RUQPy3bemfvKJoP6eXN/25mZ5XhdnpK0pez7UO/y8TsAADk1zFL1vTLBv5+l33AXGfIVG1lmIOZPvHolLFMWRCrITaAbtdWp4GeSK2TVEkaQIy4v
                                lm7MPvJYljU65Y/c47JflZmn9X/knRDdgvFFQAA+TR+6brDwsg/oMjPkunt4mKXX6+aNvps9ox44QosoAdEQfIKo7wC4sYtESwshgWdd3/z0S7/ocqmvLIn3D2TmzT0joxZx
                                K4KAEAPy3hw5OFr36JAZ8t0VhhGE7o+kolG0vOJRHQlMcQPBRbQ3SPkjIK2Z62OJIC4sd/WzO/4Z9yX8oZ7nqvOJbI/kVRdBhtlvVxfq+63ZeFVhx/ewT4KAEDPOWrJv0YrC
                                E6NZKeZ1r5H0sCuAQzZvOKM0e1Kbh2MJwosoJu1P1txlhSNJQkgZicjsgVxX0Z3t3krm38g11Elvjmel/nXenVGN37ypJHb2TsBAOh+x962oXe2ouOtUaRTzXSquyZ0nRNhj+di
                                sltXXTDq5yQRTxRYQHcf9CyayW8xgNhZW/18x3/FfSGvX9n8WbnOLeHt0C7ZgmyYnpc5cUAbuyUAAN1nwq+aqrZs6zzBQnuXTKd1qmOSIiVN4iqrffNUGHZ8khjiiwIL6EatDRW
                                HyaP3kAQQLy5fYHcpjPMyzlvW/E53v7ZEN8F2d30rUZn9ytXHjt7MHgkAwMEb94tV1cGOXid4pJPletvWLdm3m6yCS6wOSCQPLn3iwsP5BVuMUWAB3XrYi2bKFBAEECsdCc9
                                9L84LOPf+Zw9xi24vxc9ld/+1e+Kqz58w5Cl2RQAADtxRS/41OkoEJwduJ7v0dm3T0f7iHYGUVgd3viJ99fELRv6JJOKNAgvoJk11qpLpYpIAYufH1Qu1Ma4Lt3SpJ55INP/I
                                pWEllvvfzO3jc44fxskgAAD7afzSjX2yue1vtkAT5Jpg0ttdGmPO3YDdzU1/SwZbvkAS8UeBBXSTqiB1kaR+JAHE7axE8+O8eP8cu2GeSaeWUOIvyHXt4U8PvXnKFAvZAQEAeB
                                1LPTEut368WXSCy04w+QlhuP2owJSgrepx2wPZRY9OGd9JFPFHgQV0k8DUwOcLEDOuB2sXZpfFdfGuW/bM2Sb/dImknTXppsAqv/S5Sf1b2fkAANiNjAdHHb7+DVEienPXlVV+o
                                sK1E2TqI0lGY5XnU0X75Kqpo/5OEsWBAgvoBu2NyXdEkY4lCSBezHRjXJftunvXDTez76kEZq0w6feKosbZJ4z4J3sdAABdxv1iVbVt6XWEJTTeXRMkTZDWvsmlPua7fIqiUCc
                                w//n41FELCaJ4UGAB3SByayQFIHY2bwmzd8VxwTLuQbC86TaXBhR7xpI+d/XEod82M35lDAAoT+427q41Y4Jc8CY3f5Nkx5r8Tb5NhyqQOZ+QcfRU5fboCmIoLhRYwEHaWqehO
                                de5JAHE7lzy28MWa1scly29fMM1bvbuYs7XpLuUsMbZxw19bg67GwCgDIxf+mjac30ODwMdHbgOdelQSeN1x9pjZVbtL/0ux7kRMN5ycv/I3y4d20IUxYUCCzjYo18yPV3uKZI
                                AYiVSMojlJeHzVja93SP/fBFn+6/ANOPqicN+y24GAChFh/1o/Yh0IhznbuM88CPlGidpXBhqlEz2micBchdgUXHz2Y9PG3MfSRQfCizgYA5+GSVbn42uMD61gJix3/S9ecdTc
                                Vuq6x9e0y/q0A+L9PM3Z64FqYpg9qffNGQr+xgAoFiNWLquV1U2GppI6lD3rn8C6VA3HdpVVoV9Ikl6TVOFEhjB/c/jq0Z/nRyKEwUWcBBaN6Y/ZPLhJAHEjEXz47hYUUfqu5J
                                GF2GiDyoIL509YeTD7FwAgFjLeHDUG9YcEqaCEeYaau6jJR8jC8ZIPtZNYy2M+iqQPNp52qCdPRVlValrzmZzH1XGIqIoThRYwMGMkd2ZvB2InydrBudid3vbdSua6uX6YJFlm
                                ZPs69mqTV/IjB/fya4FACikEUvX9arOaoSSPjQKfZQCH2puI2QaLtcwSSOltUNclgxenDndXvwf33n+To5lKnT3C5+8+LCNRFG8KLCAA9TemB4fRX4ySQDxYvIFllGsfrN27Yp
                                n32AefrXIonzKAn109oSh/8deBQDoKS/ezifTsEDqZ4H6yTXUpWEu9TPXUEnDZOqnMBrigUyRZCbJd07jQSmF1z0/1JxVF4z5A0kUNwos4AC5+ywxZSMQr/eltF2due/HaZkyd
                                3syiJp+JLM+xROjvp2tSv5bZvzgLexVAIB9MX7pxj5Se39lg/45s/4y62+uAZK6/l3qH1nU3yLrL1N/Sf0lDVIYpRTseo79itKBs210A/vpY1NHfkXTSKLYUWABB2BTnWrddSF
                                JALFze+2t2hSnBUr1abpGshOLJL9nLQqumH3CkF+zKyEOJvyqqWrrpqhXlAxrAw96R6ZeiWRUo9D6uNRLgVdLkiKvNrPkzsFvXzdZ4ArcVLtzRJyyfSiRI/OURfEsmz1QZ+DaK
                                kmRtMOk7ZLkpm1ydez8qi2BLNv1NdZq1jXDj0ubJckijyxhrZLkUZRTFLTv/OGdkUVbJSkR+fZkYDskKUpt2froFG4fLlVjvvdUZXU66BWloopsNqiSRenAg94yT8uDGjevtUB
                                9XV4bRKr1wGoUqdYDrzG3Gplq5eonqUZSbRhuT0lJKdilc3rpX1wuydwopJBvq3Jhx6Vdz45EsePwARyA1obUx+T6D5IAYjbAkyb2vSW7Mi7LM2/FhhPco3tUBL8wMtmPO7O5h
                                sxJIzexJ6E7Hfaj1TWJdGqAR8HAIFR/BdEAmfrLrevKDNcAM+sv+QDvuiKjt5t6masv6cXqANsuU+7FUyFp11u1fYusqzjb+bWRXK2vOshsM71YtHV9lUstr3qVHb6zmHvp20w
                                t5rvcIObq8EDbXnXs3yxJQaQWJeShW2sQepRIBa3ZbBTJUm0Vng0HDBvV/sdTLFfIGMf9YlV1ekuvZDaXCKJkWCtJqZQqw6x6SZInvSaQJaIwSCqIqiXJIlUpUMXOf+8rk0Xyl
                                NRVtgZSrUuB3GvMLOHyarklZdZH5im5ektKS6qSVCGpl6RKdmqUw3ErDPyE1VPHPEYYpYECC9j/AbK1zUg9JmkcaQCx+kS7r3ZB9qS4LM5XH9rQu7MzelDSETFPbodcn5tz/LB
                                vsRNhfxz2m9UViS2pYZ6z4YFspBQN88BGBK7hLg13aaRJQySlSAuxPf69qjBTV6m2uys1KtVV/LyehLquSAJQ6GGb++RVF4z5T6IoHdxCCOyn1obkaeaUV0DsRDY/TovT2Rl9X
                                fEvrx6Tgilzjh/yd3YgvNr4pev6R1E4xqUxUjBG8jFyjXXTCHMNU6sGSzsnUu66OUjmL4/8+S0pikClXnslUj9iAYqfm776+DTKq1JDgQXsJ3M1kgIQO8/V7OiMzUnK3GXr3yO
                                pLtYndtIPc1XJBiZqL+eze7ejbn9qlCxxhMyPkNvhbhor+RjJxoRhVPNyDeW7fg4CABDnc5z/93gwajZJlB4KLGA/bKrrNUrKnUkSQNzOVGyxfV874rAomftfqJF1fFfxvQBlu
                                1xXX8Mtg2Vj3C9WVWtb1ThZNC7w4EjJj4hMR9ida4/wIFHV9VW2u1mXAQAoNmvCRHqaplhIFKWHAgvYnzdMImzwrrkNAMRHGCWCb8dlYVJBx9cljYppVqtCC6Z8YdKQR9htStO
                                4O9YOM/cJMk0w6Wh3jdc2HSl5IDe9OBc2V1EBAErQFvfgA09MGfocUZToeJwIgH3js1TRlvNLSQKI2XvT/Jf95u9YE4dluX5F0+mR6/JY5uT6QS5QQ2bikG3sNcXv2Ns29M6lO
                                o9z9wkyO8blx0oaL3mvFy+gcomLqQAA5SKUadrj00Y+TBSliwIL2EftudRUqWvCWgAxYorF5O2Z+1+oibzj24pfZZCT+TXXTBp+AztLcXrX3Z7c2Lx2nAea4K4JkiZ0qmOSpLR
                                emkAdAIByPh+0z6yaOurXBFHaKLCAfeRi8nYghlbXzs/9IQ4LEtNbB1+QR+fPmTTi/7GrFI8jlqw5NBHobZH5ieY2acOGtW+SKU1PBQDAbsZppu88PnXUN0ii9FFgAfugpTF1n
                                CJNIgkgdm6yGFx+Mnf5M6dJsbt18G9Bzs69+q0jnmY3ibGlnjg6t/bIKNDb3HWySe+QNNolmXP/HwAAe+PS/yWDLVxoUCYosIB9YJGuIgUgdraEYfa2Qi9EZkVTlVy3KE63Dpo
                                vTacSl3160pCt7CbxMmLpul41YXRS5HqHm95m4doTIlMfOdNVAQCwn56wzsS5j350fCdRlAcKLOB1tNVpoEvnkwQQOz/qv1ithV6IVKQvyfSGmGTikn9p9oRh/27Gc+ZiYaknj
                                syufbMFOtVdpyqMTo6kShmFFQAABzNMC1wf/MdHR7xAFOWDAgt4HVEieblJlSQBxEsou6XQy3D9yqYJUaSPxSSSdsnOnzNp2H/PYfcoHHc76sfrxkfy95j0HoVr36lANTwREAC
                                AbtMZSOf944LRjxJFeeFUCtjbOCSjoO3Z1BOSxpIGEKeOQH/uuzD7zkIuQ+ZuT6b6ND8g6bgYfJw3BYrOunrS8L+yd+TfoUufrE2FydPM/QyZvV/SEFIBAKCHTgPNL3p86pglR
                                FF+uAIL2Iv2jRVnShHlFRA3gS0o9CKkqpv+TW4FL69MeiTy6Myrjx++jh0jf45YsuZQS+hsi3SWQr1DUlrG7wUBAOjR8x7X51ZNo7wqVxRYwF64+0xSAGJ36tJcm+v8aSGX4Pr
                                7msdE7p+PQRi/6wwrPpw5cUAb+0XPGvO9pyorewWnmvsZLjtD0mi5uJYdAID8nQPe/NgFo75CDuWLAgvYg9aGisPk0akkAcSMR4tssbKFXIQo6Ysl9S5wEouzW4Y2Zk6xHDtFz
                                5jwq6aqre3Z97hpsknnyFXjNFYAAOSf6VergpEfJ4jyRoEF7HGEGs2UKSAIIFayKaW+IxWus5m7/Jmpkk4rYAYu+ZfmTBqeYXfofscsWdMvl9DZcp21dUv2DJl6U1kBAFBI/kD
                                v3umpOttCsihvnJMBu9FUp6reidR6Sf1IA4iVO2tvyU4r1IvfcM9z1bmK3CrJhxVoETokv2TOpOF3sit0nzfc9sTgZDr9IYv8PJneJX7BBwBALJj0WJAITn50yshNpAFO0IDdq
                                ApSF4nyCojfSYz5/EK+fq4i+++SClVebVWgD82ZMPx/2RMO3oil63r1jqKzzHWxpPfKPcWv9QAAiJVmV3TGo1NGU15BEgUWsFuBqcGJAYgVkx6qWZC7p1CvP3f5hjdKUaEe7N
                                Di7mdeM2H4vewJB+6w36yuSLakT3fTZAuj81T4ecwAAMDuuNoD8zP/MW3s04SBF1FgAa/SNiP5dpeOJQkgdm4u2DmUu81b0XyzpFQBXv7ZRBS993MnjHiIXeAAZDw46qi17/bQ
                                L/A2O1emvlxoBQBArG2XRR/4x7SxfyUK7IoCC3j1QFXWSApA7LRs6529o1AvPm9F06WSvbMAL70mCsPT5pw4cjW7wP457EfrR6QS4Udca6d7pLEyk3FpLQAAcddp8smPTRv7R6
                                LAq1FgAbvYWqehOek8kgDixU23DvmathbitTN/faqvcvblfL+uSY8mErnTPztpVBN7wL457DerKxJt6Q90zWsVvt+lBKkAAFA0si6bvGra6P8iCuwOBRawi1yQrFNhbhECsGce
                                WLC4UC+ezlb8u5sG5fllV3aa3pc5btTzbP7Xd+SSNRMU6GK16iOSBpAIAABFJzSzi1dNHfVLosCeMA0E8OIIOaNk67PJp002nDSAOH1S2W9qF3SeWYiXnnd/89Ge8L8pv8X2Pc
                                mO1BmfPXlQOxt/z8b9YlW1tlddaPIZch1DIgAAFK3IXR99/ILRPyIK7A1XYAE7tW5In2fmlFdA3ITh/EK9tAf+DeW3vPoL5dXeHXnn+iPcw8tsm+ok70ciAAAUNZergfIK+4IC
                                C3j52Mnk7UD8PFkzNPyfQrzwdcufOUfSe/P4klx5tSdLPXFkbu0Zkq6Sh+8xriAHAKBEhmD+6VUXjFlEENgXFFiApPbG9Pgo8reTBBAv5n6LZRTl+3Uzjz6atu32VeXvqXWUV7
                                sx7o61w0xep3DtlTINIxEAAEqJf3rVBWO+Tg7YVxRYgCR3nyV+ow/E630pbVc2971CvHZqa79/k+nwPK3o/2V7J8+YM2nQFrZ6l3G3rzvWFP2b5FMlpUkEAIASO88zu+bxqaO/R
                                hLYHxRYKHvPXaZqd11AEkDsLKm9VZvy/aJfWbZxSNZyV+fp5e7OBjorM37wNja3dMTta04OTJ+VojPFLxUAAChFLrNPPT511DeIAvuLAgtlL12ZukyuapIAYmdhIV40a7mM1PPHB
                                HP/Q2dgZ2cmDivr8mr80kfTUdRnqkuf4mmCAACUtEhS/aqpo75NFDgQFFgoay5Zm2sGSQCxc0/fW7Ir8/2i81Y0Hemuy3v+4OP3d/Z
                                OnVPOV169+XtP9d3RK6gPQ10laSi7PAAAJS0rt4+uumDUHUSBA0WBhbLWOj15qknjSAKIGbf5BXlZ19d6/rPRHs7mwjMz4weX5ZxXR9zeNDCw7Mztpo+Zqy87OwAAJa9T5tNWTRv9
                                U6LAwaDAQlkzUyMpALF7Z26oeaEz7yc41y5vfpfkZ/bsqml1Kkq8d85JQzeV21Z9w21PDE6lUg1S9hOSaszZ0wEAKAPbZHbuqqmjf0sUOFgUWChbm+p6jZLlziIJIGZMi+wudebzJTP
                                uQbCiuaefhLPOA532mYmDN5TT5nzjHc+MzCn3KUlXSurFDg4AQNlo9UBnPn7+qL8QBboDBRbKd+dPhjPclSAJIFZyqSiR94k90yuaP+LShJ76+ebaqECnzzlu2Jpy2ZBHLFlzaBDocz
                                nlPiopza4NAEA58efM9L5V5495kCzQbefURICyPJzOUkVbLrVW0mDSAGL1qbS0dkH2/Hy+5I2rV1e0t/R+XNLoHnqJTaEF7/rCxCGPlMMmPGLpuuFBGH1B0qWSUuzUAACUnfWJhN776J
                                TR/yAKdCeuwEJZasumpsgor4C4CdwX5P140NJ7hvVcebXFLDijHMqr8UvX9c+F0WcsjK4StwoCAFCeTI8kPXnm36cMX0cY6G4UWCjXA+tMQgDixaW/97kl9+d8vmbm0Y19gq25q71nrk
                                fOBq7JV08a8kApb7djb9vQO5vqmJmLos+ZeKogAABl7He5XOeHV104uo0o0BMosFB2WhpTxynS8SQBxEvg+qZJeX02XWpb9lNu1hNXY7rk9VcfP/x/SnV7jV/6aDqM+lzS6R3/LmkITx
                                UEAKB8mey7VW0b61dOn5glDfQUCiyU38E10lWkAMTO89W9snfk8wUzK5oGyvWJHjnOuGVmHz/suyW5pTIeHDlu3cVh6NdKGsGuCwBAWXOTrn5s2qgbiAI9jQILZaWtUQM80vkkAcTu1G
                                eRfVPb8/mSKddsSTXdvirSrXOOH/qlUtxMR9351Inu674p+YnstAAAlL0OyS97bNqY24kC+UCBhbISRckrTKokCSBWcrlUYmE+X/C6e9cNl1TfAz/6v3NbhtaX2gYav3TdYVEYzXPX5D
                                zf5QkAAOJpkwV27mPnj/4zUSBfKLBQNjyjoO1Zm04SQOz854Cbtq/P5wtaMpFR9z8pb2W2KjklM8lypbJhjlmypl9noM+GYfRxSRXsqgAAQNK/PBGesWrKoY8TBfKJAgtlo31jxZlSNJ
                                YkgHgx8xvz+XrXL3/20EjhR7v5xz6lMHFmZvzgLaWwTSYsWpHaWj2oPmv6okkD2EsBAMDOM7c/Rp6c/M8po58nC+QbBRbKhrs3kgIQtzemHqy5JXdvPl8yUjRHUqobf2S7FHxgzomHPF
                                sKm2Tcj58+ZWtoN8t0NDsoAADYZUD1jSFDR332j6eUztXmKC4UWCgLrQ0Vh8mj00gCiJ3/yOeLXX9f85hIfmE3/sjIouCC2ScM+Xuxb4jxS58akguDr1ikC2Uydk0AALDTdrlmrLpgzA
                                9WkQUKiAIL5cGjRkkBQQCxsrFmR/aufL5gmNQXTEp326HF9Ok5Jwz5dVFvhYwHRx659oow1FdMqmW3BAAAu3hCYXDeqgtHPkIUKDQKLJS8pjpVSbqYJICYMbvFvq8d+Xq5a1c8+wbz8K
                                Lu+nnu+sE1k4Z9o5g3wZFL1kxQsPYWuSaxQwIAgFec65h+kw514SMXjtxMGogDCiyUvKpE6kJJ/UkCiJVsKkp8O58vGCh3jWTd9bn3l5p+W4v2qabHLFnTLxsoI2mmuDoVAAC8krv0lc
                                dXjZqtjEXEgbigwELJM9d0ZnMBYufHVQu3P/P/2bvvOLmq+v/j78+dmd2U3dmSQEIghdAJIgoWlK+KAikSUTEhCUgTQjqJvbv6+1q/foX0EBCQTrAhLUG+YkFFBXsgoZPQAiGbnd0kuz
                                tz7+f3R0I1QMruzJmd1/PxQCGZnXP3fc7cOfczZ84tVmPfvnft/kliXbP3lekxi+wjsw44oKMcgz/ousfH5d0XSrYHwxAAALxKzuRnrpo47GdEgdBQwELPPvtOTf+XS28lCSAwrvnFbC
                                5OUp+3rnnPa5PFJ33xrYOfK7fID77uiQOVxEvkOlZU9QEAwH+wf6RS9rGV4wc/RBYIEQUs9PBrZJtOCkBocyP9sW5x/s/Fau6///zkYJO6Yu8rN9PHv3jk4H+WU9xHXnRPZnN2j0+6x0
                                0y9WIAAgCA/7hskuYX6jo+u2pMea4wR2WggIUeq22aBsauj5AEEJjE5hWzuUj2ae+COw+69O0vHTXo5+UU9UHXP/7uTYkukjSCgQcAALbjWZOfff/EYbcQBUJHAQs9Vpykz5Pt/kUrgK
                                7kT2WT/E+K1dq3/vREPzd9Yvefyf7vwEcHfrVcUj78imf6dmY6v6vEp4nvCwIAgO27JZ/Pn/3w6fs/SxQoB9x5CD3zErlJaTedSxJAYK9N0yJbqnzR2ktFn5LUdzefZq2lNHH8eIvLIe
                                ODrn/83Z2Z9r9KPl0UrwAAwH9qd9PsVROGjC3n4lXD+b8cQldWFlZgoUdqWVf1EZPvTRJAUDrSnYVLitVY093PZ+UdU3f3mCO3k7/w1r2C37R9n2Vre/eNk69Zos9IxgdUAADgP7nuS6
                                J40gMThv9DE8r316ifufzNhSQZImkNnVo5KGChp56Z2bwdCG6+pGtqLtG6YrWXSXXMlFS/W09imvGFt+31l9CzPfjax96lOL5MsgMZaQAAYPtTMV3ctzYz596xQzeX8y9SO/2Ofm6Fea
                                3t/Y6jWysLBSz0OK3nVR2ayN9DEkBgEi0qVlM/+MPa3u2uWb4bX6Bz6YdfPmrQJSFHOuyyR3v1qrZvSjZbbAsAAAC274nEfPIDE4bdVva/yeR7MorW/zhxv1lLj8rTtZWFAhZ63jVy5D
                                PFvi9AUNz12/qL8vcUq70tmdTHJe25G09xb6GtY0bImY5Y9vihhURXy3UEIwwAAGx/Cqar0qlo9qrxgzf0hF+ortf6RS4dZfn4o3Rv5eHTWvQoz52tWkmTSAIIjc0r3kzNTdLs3XiKNk
                                uSSU3H7tse6FTUDr7u8clxrL8YxSsAALC9mZf0sLsft3ri0NNX9pTi1awVk106R/LLWhaf2EwvVx5WYKFHqarKnCUpSxJAUNbUDey8sViNffvep0+SdMiu/ry7pnzpHfs8EGKQh13zyI
                                D89WsulWsMwwoAAGxHQdKiTL76i/88feCmnvJL1c++/QhP/EJJiVLF+2AUYaGAhR7DJctFmionCyCs16ZfZE0qFK0916d2+WdNF3/5bYOuDjHHg6977KMFt6Xm6seoAgAA2/HXVJJ8Yu
                                Wp+/69J/1SdVNvbkiS5KeSepvr5y0XjHqIrq5MFLDQY7Sclz7OXAeTBBCUfCYuXFasxr7956fflsiP2ZWfNWllfve+etgt9r/1wep0S9X35JrFcAIAANuxRdL3+uae++a95/Wwjc2bmi
                                LfkLlK8n0lKY7sArq7clHAQo9hpumkAATnp32X6uliNebmn9nFH233KJ7UdOTgoG4rPWLZ2v3jluR6SW9lKAEAgP+c/OjOVDqavHL84B65Kim74eivSD5m2+/6x7Z5I39Lp1cuCljoET
                                ZM7j1EVjiRJICwJO5LitXWt//49LBEvmt3pDHN/NKRg/8ZUnaHXPvYRwpJcqlJ9YwkAADwKk+Y6Uv3Txhypcx65CYqtbNXfFiJf/WF//bIv0O3VzYKWOgZAzkVT3EpRRJAQEyr6hcXfl
                                Os5pK0pmgXzgMm3fDFowZdEkpsL3xl0KVZxp5+AADglTa7ND+d6v3fK8fv2aYJPfOXrJn9y0MsiX8kKdr2R6taG/50M91f4df9RIBy5zNVnSv4J0gCCM4SU3FuqzDvwQer25r9LLed/t
                                FHO+Pqc0IJbP/r1uyXyvmPJR3B8AEAAC+/7JHs2kIcfe6h0/Z5oif/otk5yxtViH8he+nu8i77jpqaEoZBZaOAhbKXy2fGy7QnSQBBzbC2WEf+yqKdB5r7fsx2/jyQmNtZTe/slwshs0
                                OufmyMu18lqYERBAAAXuaviev8ByYNuavH/6ZNd6a1oeN6mfZ/2cRybWtHv2sYBqCAhfLH5u1AiK6r+6E2FO00YJq6C+eO737xbXv9puRJudsh1635rEvf0kvL5AEAAJ6WqWlVNOSHGm
                                9xJfzC2eaOeZKOe9Wc7X+1tIfdXRG7hAIWylrzlMxbJL2DJICwmKtom7d/8961hyvRu3fyx/6e793cVOqcRixb25hct/Yql0YzagAAwDZbXJqnPlu+ufqkg1sr5ZfOzrqtSf4fH0puqC
                                5U/5AhAYkCFspcJM0iBSAsJv0juyT/56I1mKR2dvVVR2zR6U0jRnSWMqcRVz96RBwnP5E0nFEDAAAkJSb9JEn551aPH/ZoJf3itTNXnC29dMfBl/j85xYd28bQgEQBC2Vs41Q1SBpPEk
                                BgMy9pUbHa+u5dz9UWlJ+0kz/2ha8eNfBfpczokOvWTIjdL5XUmxEDAEDFc0k/8VT85VXjh6+utF8+O2PFaJlfJOnVt+PJWT6ey/DACyhgoWxFlj7XXX1IAghKa74jf22xGstXFT5ueu
                                kONTvgt/mj9irdROiF/a7cv72dSRoAAKg0rjvc7AurJw65pxJ//drZtx2txJdp+7WJuS2LT2xmkOAFFLBQnuf5JkW5dTaFJIDgXLHHpSraXg2R+RTf8Ye3pEwf/5JZSW7BPGLZszXxdW
                                uvcOkjDBMAACqbSX9O3L+wetKwX1VqBvWzbz8iSZJbJNVs56/bPJOfx0jBy1HAQllqfbb6g1KyL0kAYUml7KJitfXf9zx1jLvetBNTxVmfP2qvNaXIZf+rntgnTrb8XNKRjBIAACra7+X6
                                7v0Th9wsM6/UEPrOuOVNSZLcIalhe3/vrnmt/zt2PcMFL0cBC2XJ3aeTAhCcu2oWdBZtb6nINW0nZn23felte11RilAOvvaxd0nxT+UawBABAKBi/V6u766aNPQmSdKkyg2idvotB1qUul
                                1Sv9d4yCZXciFDBq9GAQtlZ+Pk6v2k5HiSAEJTvNVXTfc81d9dH93Bh+fc/bxSJHLQNY+fJumHkqoYHwAAVByXdItZ8s37J+x7N3FINTNvPdQsukPSwNcMzbSgbd6Y50gLr0YBC+V3iRwl
                                0yVFJAEE5fnsls4fF6uxjNtZklfv0DlD+uyX3r732mIHcsi1j33Fpa+LzdoBAKg0sUk/jZLkWytP3ffvxLHVtj2vbpe0x+s8bJN32A9IC9tDAQtl5anJ6iPTGSQBhMVll9rlai9ii2ftyK
                                NM+vUXjtpr6ReLmMX77vT0M+vWLHDXeYwMAAAqakLUKtO1nop/sGr88NUE8pLszOVvT5JkuV5jz6sX525mi9qWjHyWxLA9FLBQVrEqYbwAAIAASURBVPqkM6fK1UgSQFjTtSiyS4rV2
                                Lf+9MTRLh2yAw/dbFF8rhVxg9SDblxVu+6ZtddLGs2wAACgYjxi0tLqjuSiv5+170bieKXa8287Rq5bJGXf4KFtcYe+T2J4LRSwUFYs0RS+jAOE9sLUHdmFHQ8Uq7kkFZ21QyUp1xe/cOT
                                gh4p1XAcuW7u3tiQ3u/wIBgUAABXh926auzoa8lONt5g4/lPtrOUnmesaSX12YE75A1Zf4fVQwELZyE1LH+Out5IEEJZEtqRYbf3PP57p29mZnPKGD3S/+4DHBi0o1nEddM3awy1Olkvai
                                xEBAEDPviwx6ZrEo8WrJw3+J3G8ttqZK842+UXasbrDRussXEhqeD0UsFA23G06KQChsafrC503Fau1zk4/WW+8/LwzSdnZ44v0Segh1z36TvfkZr32raABAEDZT3l0r6Slqaj3NSvH79lG
                                IK975WbZWcu/JvnXdjhe13daFp/YTHZ4PRSwUBbapmlg7PooSQDBTeaW2lLlizghOnsHHvPtrxy59/3FOJqDrl3zIXe/TlJvBgMAAD1Oi6TrU0mymLsJ7qCmZVXZDSsukezjO/FTz/Sq
                                tgUtpIc3QAELZSH29GRJVSQBhPXSTCy6rFiN/b8/PbOvlLznDR72QL6t8zvFOJ5DrnnsHJcvkZRiKAAA0GMkkv/OZD9sTUU/fmL84C1EsmNqp9/RT88XfizT+3buJ+1b674/chMJ4o1Qw
                                ELwvEnp1md0rrN5OxDYi9NvbljY/nixmoui5BPS697GwZMomtp07L7t3X0sh1z7+Odc+vYbHA8AACibeY3uk+mGJNEVD5w67BEC2Tl9Z9zyJosKN0radyd/dE3O46UkiB1BAQvBa1lX9R
                                Ez34ckgMBEKtrm7U3uke55+vTXn3fq0q8cOfBX3Tu5dTvo+jU/cNdsBgAAAGXvcUnXRK6r75s0dCVx7Jra2Ss+bIlfKalmp6dW5t/QvDEdpIgdQQELZcDZvB0Iz6PZPQu3F6ux6r88fUJ
                                iGvw6D1lfMH2+Ww9imacOum7tRSZ9gu4HAKBsrTP5T2O3ax6YOOT3MnMi2UVNTVH2+Xd+WYl/TVK085d5Wtn6dOvlBIkdRQELQWs9r+rQRP4ekgDC4vKLrElJsdpLIj9Lr/M9YpfPajpq
                                7/XddgDLPHVwYc2lMp1O7wMAUHYel3SjRbppwJ5Dfv3rY60gSZpEMLuqdvod/ay5cKVMo3fjaT6nG8bHpIkdRQELQUsinyH2mAFC05nyQtE2b//Wn57o524nvdbfu7T8y2/b+9ruan/Esp
                                VVcbz2Whl3QgUAoIw8IunmxHXDy1da3U8uu61u1oojXYUb5Du939WLTPablvkjbyFN7AwKWAjWc2erVtKpJAEExvWT2iV6tmjtRdHHJFW/xt9u8SSa1l1NH37FM307Cx0/l/lxdDwAAEHr
                                lHSXmy83pW9cNWGfB178G1ZadZnsrBUzXf597d4d4t0t+QxpYmdRwEKwqqozZ0rKkgQQlijlS4rZnr/utNO/95V3DHy0O9rd/6oHs52pjlslvZteBwAgSI+ZtDwxW56Oev3fyvF7thFJ96
                                j91E39LZ+5RPKTuuDprs3NHf0XUsXOooCFILlkOWkqSQDBua92YeG3xWrsu39dM6gQ65jX+Ou1VVWp/+mOdocve7guHWeWS/5OuhwAgGBslut3Ji2PlSx/YNK+q4ik+9XNWPF+5XWFy/fu
                                gqfrNE++SqrYFRSwEKSWqekPmHQISQCBMS0sZnP5JD3BXuOuNuaa/Zk3D9zU1W0OX/ZwXRXFKwAAQrBZ0t9cuiuKdEe+tvN3D405oINYiqTpznR2Q/uXXf4V7cpdBrdvQcv8MQ8TLnYFBS
                                yEajoRAMFpK6TyVxWzQXNNeI2/+b8vvn2vn3Z1e1uLV6kVkr+D7kZ3ctNGc7VJapPUKleLzHJyb/PI2+TWatIWk9oTaYuZ2j3RZkXqiNw3K7KOOFZ75Nry0nNGeffkxa/PRFGSZJKoZY
                                un+kYW79BeJZFHfWXJdh9raesreZXH6u2ReilRtUXqY+4ZyWrcFbmpTpJMapAkl+oiKXJ5rdzSMvWRrK/ktZLqJfXVa+9xB6DybJbrD27+G3f7dSbd9ueV40d0EkvxNZx/2+Hxho7LJXtL
                                Fz5ts8fpb5EudhUFLARnw+TeQ0yFE0kCCO2KW1f0m69csZr79l/WDU8UH7WdvyrEZnO6ur0jLnu0viOOVrj0djobO/XKkJ6V6Vm5njbpWZc2SNog8+fl2uCe2uCRP6/Yny9kCs8/Mn6/li
                                IfY3PIAR550T2Zzpo9ajozXm+dqRpLF/rKoprE1WBufWVJjbn1TaQGk9VI3lemWrkatLUIVq+tBbMGhiNQdqfQB1z2p0j+J0/s7r5tz/3z3vOOypNLCTXdma57vuNTsevr6uoPGNybWhc
                                e9zwhY1dRwEJ4gzIVT3HGJhCcVNqKu3m7xePlsu381fyvHjXwX13Z1hGXPVrf3iu6XdLb6Gm8NAa10aS1cj0u2eOSnpL0lCXJsxbp6VjRur0GDn7218dagbR23baL1WZ1QaFt62tZ9ZGsI
                                Y5UHyWqT6QGs6jeXfUmNZi83l8qetVLapTUT7t3Ry0Ab+wpSX+VdG9i/qdMlPrTyvGDNxBLOGpmrTgsam6/zM2O6oanX5Xr2GMxKWO3agVEgKAuFsapKmd+tpwsgJCY9OuaBZ3/Kur5wP
                                WR7fzxupT1+npXtrP/VQ9mO1LRClG8qkQ5mR5010MmPeKyNZGSteb2WNx3y5rVJx3c+kZPsJoMg/L3s/bdKGmjpMd29mcPunFVbdRW1ehK9VfK+ilRP0VJP8ka5eonVz8za5S8n6R+L
                                vWXtn5lEsAr38JNekTSX931N1f0t4zyf/v3pOHriCZQZ97ZK5vt+KLkn5NbNxXz/VNayuo67B4KWAjrSqJf5hS5BpAEEJZEtqiY7W27++DbtjMl/vzn39bYZV+/OvyKZ/rmUx238
                                LXBHq1N0iq5P2QWPeTuD8n8wXy+8NDDp+//LPHgBdsKlq2SHt/Rn3nfnZ5eu/bhxqgq3c8S21rgMvUz31r4ctMe5tYoJf0k66eXVnux7xd6iiclrZT7v810nyn177jPpvtW7cAHAAhDz
                                fkr3hN5xxJ17w207sjNG30raWN3UcBCWIzN24EAX5hP18WdPy9mi3GSOWnrB7ivcG/+bQOv6Ko2hl32aK+OTMeNJh1DH/cI7ZLuM+k+Sf9200qPfOXqcUMfkxnretEttn199Nlt/+ywEc
                                uerZFaGz1O9XO3/oqsnzzZWuBy6ydTv8TUaO4vFL76iT2+UDodkh6U7AGXPyj5A4psdVVB9/3r1KHNxFOe6qbe3JBkUt8x93Ol7W7Z0FUKibp+71JUJgpYCEbzlMxbJHHnLyA0nlxkS5
                                UvbpN+0qumUu6m2U1mSVc8/4hlK6viOPqxpA/QwWU5KJ8zRfck0j2Rkr9FqdS/VmqfRzXeYrJBOVg5fs8X7kC5Zod/aJmn9tczjVGhvZ+lon4Wq1G2rfBlapSsv0z9thbAvJ9cjdpa9Op
                                D4ngDeUlPubQmkh53+RpXtNZMj8jjB1atHrZGTV3z/osANDVFdRuOPsfl37StX4Xu7vfsxW3zRv2b4NEVKGAhGGaaSQpAcAoZZS6RirdH9Xfveq62YPn3ver8cM2Xjhp0V5c0sMxThXjN
                                FSZ9kO4tCzlJ/5J0r5nutUT33jdx6H2sqkLFGW/xQ9Jz2vrPTtln2drefWNvSLk3FKSGSGqwSA2J1GBb7+bY4FKDvXA3R1eDTA2SBkiKCL/stUt6Sq5HZHpE0tMyPWWmR+KCHhk0aMg
                                abkZRGbLn3/Y2bYjmufydRWqy2ePM10keXYUCFoKwcaoaTDqFJICwmPSzPku2PFnMNvNVnWNM9vL9YTanCsnnu+TJmzw6uLDmRzLON4HKSfZXSfeYdE/edM9DE4Y8/B+PmkRQwM54Yvzg
                                LZK2aOtd4HbYkRfdk9nYu74h3StqiBJriGUNkW8tdCUWbb2ro28tfrk8a6Z6ueok1UrKSupN+t1is2TrJV/npucs0XrJ1yuy59z9WSlab0rWy9LrU1HVU9tW/L2mB8izx+sz4/ZB6c
                                i/K/dTt7NFQ3f6SuvC456nB9BVKGAhCJHS5zhL3IHgmHxhCVod/Yr/cvvu5965zxNd8cwHH7R2nqRT6dlgtEj6ndx/7Rb9ZnVq8N/4GiAQjnvPOyqvXdjj6wXvu9PTzz33RDafT+ozS
                                rIepWpdSVYW1bo8a66GRNbXpN6SZyWrkdRb7rWybf8ur5W8Vm69ZKot80hjbV1VmpNrk5na3JWTWc7lmyJpUyK1yKw1SnyTmza5aaPL28yj59Kp/LO9evdef+/YQZsZndgRAz69ou+WD
                                v+ULPmMpJoiN/+33NO5JfQCuhIFLJScNynKrbMpJAEE5/6axYXfFrtRs1fsS/VEZ+Tf74rnPfjax78mOTeKKOX53rTRpN9J9mvF/ptVmSF/p2AF9Fzbvpa2Yds/XeKgG1fVpjv6ZvL5p
                                P6FP8tk1CvOv7Tay9OejWQpSfKCpTzl2Rf+LpJ6u9RrF09inW6+adu7VWe07d8tsY4k8c2SFJnaC64tkmSRtVclvkWSWjuTLY+dtW87owJF0dQUZZvfedqWTv+2TINK8ZbvkU/XDeN5j0
                                eXooCFkmt9JjVGpuEkAQTGtMCkou4z9M2/PHOYlOzz0uzHP930tr13+5Pmg65bM1XuTXRq0bXJdbeZ7ohdv6/NPfenbSs6AGCXrD7p4NZt/7qBNID/lJ112xjbYN916bDSTSF1We7C
                                0X+kN9DVKGCh5NxS04t8jQzgjbUWUvmrit9scvzLpj9//NLb9lr25d18xkOuWzPB3RfQpUURS/YXmd+WyG57YNXge7lzFQAA3a/2/NuOkds3JB1b4iur5rjTvkCPoDtQwEJJbZxcvZ+Un
                                EASQHCu6DdfuRK0+0IBK4lc59tu3mnu4KvXjHT3H4m7aHWnZ+VabpHd5h3RL1edsQ+btQIAUCQvFK7MdWwQB2T+5bYlo56lZ9AdKGChtOe3KJnOhSUQnlTKLip2m00rV1Zps94jSS77
                                0Rfevtdfduf5Drx67dsVJT+RVEWPdjV/wNxuTFK6cfX9Q/7IKisAAIoruMLV1gncX3NPt15E76C7UMBC6c5vc9Q7164zSAIIi0m/rlnQ+a9it1u1qfFoN+8rqdUs/aXdea79r1uzX+TJ
                                TZL60qNdNCWV/1lmP4/lNz44Ydj9RAIAQPHVzbrtAy5rkuuYwA4tkRI2bke3ooCFkmnpyJxmUiNJAKHNPmxRSRqO/L/kkpm+9cWj9nh6V5/mwGue6h95562S7Ulv7ibXfTLdkEpFV60c
                                P/ghAgEAoDReKFy5gitcbZ0ymF3cOm/M3fQUuhMFLJSMJZoiIwcgsFfm03Vx589L0XLiOtqkRzpbOy7c1ec48qan+mxq67xZsgPpy132V3Ndn6T9htXjhz1KHAAAlEhTU1T7/NEfNPkX
                                XDo64CNdF3Xm2bgd3Y4CFkoiNyX9bje9lSSAwHhykS1VvujNutu37nn6nZ7onKZj923fpSdZ5qlNbWuvkuwddOROe0LST1NJctnKU/f9O3EAAFA6/T97Y21ne/XZ2qA5Mh9aBhPI2S2L
                                T2ym59DdKGChNKc42XRSAIJTyChziVQoesP//denD46kf3z5HYN+tqvPcXC89kLJP0I37rCcSzea64ZV6SG3aryxZwUAACVUN2PFvknKz+ts13mS6svkwm5Fbv7o6+g9FAMFLBRd2zQN
                                jF0nkwQQGNNP+yze8mQpmo5ie0fK4zm7+vMHX/vYpyWfQSfuwDRT/lt3u2RTOvrJE+MHbyESAABKq27WiiNdfr7LJ5qXzzW6ybZIMQsTUDQUsFB0cZI+V8Zt7YHQRO6LSta2tPLz79jn
                                H7vys4dc/dgYl32HHnxdz0haZmYX3z9h6L+JAwCAEpuzrHc2qT1ZbjNcXp7bH5i+0TJvzMN0Joo35IAi8ialW59JP+pm+5AGEJT7sovzh5nk5XTQh1772FsS2e8k9aUL/0Mi1y89ssV7
                                DRh8y6+PtQKRAABQWjVzlo+wRB831zmS+pVxIeHfLe3936qlR+XpVRQLK7BQVC3PVn3YzCleAeHNQhaWW/HqkCsf2yuW3WgUr14tJ+m6VEpzV44fep8krSYTAABKpmbmrXtEik61yM72
                                2N/UA36lOIl8MsUrFBsFLBRV5D7diQEITWshlb+qnA748Cue6duZ6bjJXIPpvhf4P2W2sKqz+up/nj5wE3kAAFBCTXems893jHTTWSaNlVTl3jOuhEx+Qe7C0X+kk1FsFLBQvCvk86oO
                                TeTvJQkgOFf0m69c+UwIPerMrL1SriPpOrmbblPi/7t60rBfEQcAAKVVN2vFkW46XRs6xss0sAfu2bOqJdX6VXoapUABC0WTRD5D7LsGBCdyW1JOx3vIgY9/w2UfqfBu63Tp+pTru/dN
                                HLqSUQwAQOnUzFk+Iop9nNxOdfn+6rlfOSnIdYYuGM9djFESFLBQFM+drVpJp5IEEBaTfl27pLNs7kp30LWPf8ylL1Zwl21w2cKMFxb+e9LwdYxgAABKo27mrfu5bKJkExRrhGQ9/qN6
                                d32vdf6oP9P7KBUKWCiKqurMmZKyJAGEJTFbWC7HetA1aw83JZerIldy+nOSLerVnlz497P23cjIBQCg+GrmLB+RKuhEj3ysu72rwuYk97W2Vv8/RgFKiQIWuv+yS7KcNJUkgOBenU/V
                                FfI3lsORjli2tjGJk5965d1xcJ1JF/SpqZp/79hBmxmzAAAUUVNTVLfh6Le4krGSnaJYB7tJ8or7LK1gSXSGLj+2nUGBUqKAhW7XMjX9AZMOIQkgOBfZUgV/++P33enpZ55e82OZ9quc
                                rrE1Mv9mKmq7fOX4EZ0MVQAAiuTMO3tl69rfK7cPa4NOcvlebONr/92y4IR7GBwoNQpYKIbpRAAEJ5/xzA+lQvAH+sy6NT+Q6dgK6ZenZP7tVNS2lMIVAADFUT/7tmGeRCe4dJzUMVJubH
                                3ykr/n2vt9ixgQAgpY6FbPT+s92LxwIkkAwflpnyVbngz9IA++9rFJcs2sgP5Yb9L3W1PRvCfGD+bOPgAAdKemO9O1ze3vlOxEcx2XJHprJX4vcAd0xEl8upYelScKhIACFrpVxuMpz
                                jgDgmPy4DdvP/C6R94st4t7eFfk3Ox76ajX3JXj92xjZAIA0B3cambdPiLy5P0ye782dHxAshpyecP54tc3Lfjgv0gCoaCwgO57mxinqpz5J+RkAQRmZe3iwl0hH+ARlz1a3+HRT1zq
                                00P7oCDp0lQq+drK8fs+w5AEAKBr1c28dT9F0fvd/f3SimMlDZCxyGrH2d0tjdX/Qw4ICQUsdJtc/6rxch9AEkBY3DXfFHBp2d3ar1t7qeQ9c9N21x0W2Zz7Jwz5N6MRAICuUTfnjuG
                                eFI6RdKxc73dpyNbZDkWrXbmUs1TqVDUdWyAKhIQCFrrzKo3N24HwtLTX5K8K+QAPun7tFyX/SA/M/i+J65MPTBp6F8MQAIDdMG5Zqmaf7MGp2N7t8mMkvcfjwlCC6arLOJvWcsFxjxA
                                EQkMBC92ieUrmLZLeSRJAaBMSXT7w+9oU6uEdeu3jH0jcv97DUn/eTf9vdTRkgcZbzCAEAGDn1M++s97j/Nvd4ndJ9m5J71SsGmevku6YK16Rmz/yaoJAiChgoVuYVcRdw4Cym5KYRYt
                                DPbjDrnlkQEG6SlKqh+RdkLSoM1X46iPj92th+AEAsAMm35Op6bv+wJetrjoySToOlini64Dd7uG0Eq7jECwKWOhyG6eqwaRTSAIIjOmO7KKO1UEeW5NHBa25StLAHpL2L2Pz8x+cMOx
                                +Bh4AAK9hzrLe2aT2MPPoLZK/LZHeZlo/QrHSrK4qurzMJ26YNyZHFAgVBSx0uUjpc3rwncOAsmVmC0M9toMPXPM5mY7rATGvM9Nn758w9ApGHAAAL6mbenNDUpUaYW5HSjpS0qGK9SZ
                                JVS8Uq1hfVTru/qXWeaP/QhIIGQUsdO2Jr0lRbp1NIQkgOGtqn+28OcQDO/C6NW9T+e975S5dZZ2pOfefsc/zDDcAQKWqn31nfaz2Q5TYiMh1sJu/SbLDXRpoLKoK1R2t/f70v8SA0FH
                                AQpdqfSY1RqbhJAGExd0X2w0KbgPx4cserotiv15SpozTfcAjTVl9yrA7GWkAgEpRM2XFnqrWwakkOcSj6FC5Hyrp0CTpGGTb1lK5SayrCt5zhUinq6kpIQqEjgIWuvYyzlLTxffVgdB
                                0pFS4NMQDy8TpiyTtW6a5dpr8v/vk1n/n3vOOyjPMAAA9Td3UmxuUyQyXJcOV2PDEfLiZRkh2mNzr5JKbbV2HjLK8fHPzczZfOPppokA5oICFLrNxcvV+UnICSQDBWVa7RM+GdlCHXPP
                                YOV62N3zwf0bSmfdNHPY3hhcAoGzNvLW6NrJhJg2zRMOSyPa1RMNkGiZpf5f6aWuVSjLJZHxW3ZOYzW+dO+oXBIFyQQELXXf+SyXTJEUkAQQmUnCbtx9y9doD3JILyjDNvEs/SKc2fXX
                                l+BGdDC4AQMj6TrtlYDqd3juxZG/zaKib723u+8g0TG77StpLvvU7fm6SufjGX8VcvPk9uZbqzxEEymrYEgG6gs9R71x75glJjaQBBOVvdYvzbw3pgPa/9cHqVK7qbnMdUWZvmf+IlJz
                                FqisAQElNvifTp2rDHlVWGJB4aqBH2lOe7GmmvaRoD5MPcfk+ku0tqZrAsB3PR3F85MaFH3ycKFBOWIGFLtHanjlVFK+A4JhpXnBvPC3V/yv5EWUUY96lH9Tknv0Ke10BALrFzFur+3i
                                6X5RJGlIF7eWRBsnV4Ka9zDVIUoOkvSQNktbvKSmVKJJs2/Ipe2Fdgm/7hh/rFPCaEkmnUbxCOaKAhS7h0hRSAILT3FbILwvpgA66ds2HJJ9WRhn+1czOWDVhyL8ZTqhoTcuq6tb16St
                                J6tWrQZIK+Y6+6Shd9YrHpVLNL14hdaolStoTSWoZ0LdVTccWCBI92pl39upT39EQKVVvSdJg7g2KvEEeNUheL3mDzBrkajCpIZHqzdUgU4OkGlkixdvu3Ldtnyljvyl0MZN9tWXeyOU
                                kgfIcv8Buyk1Jv9vN7iIJICwu+5/6xZ2fDeV4Drzmqf6R5f8taUBZxCfNL9R1fvahMQd0MJpQUk1NUd26o+ry1eleGUv3Nk+yiXu1JV6bRN4nkqrlUYObV1mivm6qclPfrRe/3se3fYX
                                IzGrltu3DS2/YNtBTJmW3jfpeMu8tWSSpblvrNZIyXfwbbZL0wh5yWyRv3zYt7ZC0edu/5yVve9krMpZZ7hUvUveNspdufWayNje9uErS3LaYv/DcUmKejxS9+JyJJ51RpE0v/reiLVH
                                yssenfFOURJ0vO6fmLBXFLx5Ae/uLxbpMbVxY/72TWhms5fv6itNROpWuqlUcN8Qe10SKamRea4qyiSd1FlmN3GvdrcbM6iWvlXmt3Bok1ZusweW9CRSBT29uys0bdRKlUZQrVmChKy6
                                Sp5MCEJxEipaEdEApyy/ysihe+XMmnXX/xGG3MIzQlQZ8ekXfLbHv5flogFs8IIo0UIkaZdZgUoNLjSY1yKwhcW8wqa+kGm1QxjNSOpFchRdvALZ1w2Xb9t/+4o3CpJdfmthLn1b6i/+
                                jl/721f9RlM82+277R5Iatt/mq66tbDvHvp3jfeUlmb+YxwtJ+Muew8zk/vK/f9Xjk1c+XnJ5nLz0n5mXptGd7WllZ724oCEnKZbUIfkLBbkXil2bt/65CpK1bvsNcomS2KQON9ssSVG
                                i5q19bJtt6+NfOgpTX0u8qkjnw42yqOgXui7PmKtmO6Oi2iP1edmwqHb3Pi/r0yq5bR1b7tUy9TGpyqW+LmVMXiNZWlKtpJSkrDZIntl6FyKPty4SjCx6+aiQ2Qt33rNt485fehG+dMy
                                c5BD6RdtDUarX6RSvUM5YgYXd0jpFeyaWWSM2iARCO73fUre488RQjubgax+bJNnVZTC5u8NiP/3+jw97mjGEHdbUFPVZ/66B6ZQPTpJk78hssLkGu/neku2jrYXbQXqpaAMAQDFtTpk
                                f3Tx39D+JAuWMFVjYLYnS54niFRCgeGEoR3LIlY/t5bL5gQdWkPTNVQ8M+YaaLGH84BXOvLNXTUPHfuZ2gCU+9MXilGmw3AZrg/ZSlGTkUrRtI2Uv3momAABen9vk5nmjKF6h7FHAwq6
                                fB5uUbn1Gk1mDCgTn4eyAeEUw54p0tFDykO9S+qhZMun+CfvezdCpYE3LqupaGvdRoTBcZsPdNELuh0o+XOoYqlipF7429GJxijdAAED4V23zc/NHXU0O6AkoYGGXtTxb9WEz34ckgLC
                                YfJE1KYhVRAdfu+ZMyT8S7pzOr0ul+5y7cvyebYycylD7qZv6p/KZNyWyw1zJYZIdYK79tUH7uAr24l5LL9tDCgCAMvWHXGPrp4kBPQUFLOyyyH06Hz4DYXFpizoLl4dwLAcuW7u34uQ
                                HgUZVMOnL908a9l1GTc804NMr+m7JJ4e6dLi5Rkj2JkmHKa+BybZXy4vbm1OjAgD0PE/nU/nxahrfSRToKShgYZe0Tqs6JHF/L0kAwbmm7ofaUPKjcLfouscvkawhvIj8OXdNWDVp2K8
                                YLj1D7zk37Z32qrdZ4kdJOkzyN23p9GGSRdSmAACVxmRb3JKTtlww9knSQE9CAQu7JEl8xsvvzA0gGItDOIiDrl87RbJRAebze1c0fvWkIU8xVMpT48xbs/nIDje3IyUdKekYxdr3lRt
                                S8fYEAKhY7p58Ijdv9F+IAj0NMzzstOfOVm1VdeYJSVnSAILyh7rF+XeX+iAOWvbYvlawf8hUG1g+S1Optpkrx49gKX25mHxPpq7X84e7/BhtLVYdKekQ5i8AALzGBb7pSy1zR32LJNA
                                TsQILO62qKnOGKF4BIU5ZFpb8ENzNrnv8ssCKV5vdfPLqCcO4A0/gsnOWN3rix5jbe2X+Hvn6I5y5CgAAOzgP0xUt8yheoediUoidPy+azuOjbyA4z2XTnT8p9UEcfN2acyQLaX+8J1JJM
                                nblqfv+nSESnpopK/a0jN6hyN9truMU6y0mi154swEAADvsrpySycSAnowCFnbKxunpD1iiw0gCCIzbUpuvjlIewmHXPDKgIAV0Vz/7R1qpsf8+de+1DJAw9Jm9fK+M+zGu6Bi5v1vyt0
                                oycUtbAAB2x6NJp52sJWM6iAI9GQUs7ORFsqYTAhCcOElFF5f6IAoWXSApkLsO2k/71qQ/fu/YQZsZHiU0Z1nvukL23Umk48x1nBId6TKJihUAAF0ll8g+1LZk5LNEgZ6OAhZ22PPTeg8
                                2L4wlCSAsbv6LhoXtj5fyGA6+es1IyScGEsm8VasHz1GTJYyO4qubc8dwxfFxLh2n2Ee5qdaoVwEA0B0KZvaxtrkj/00UqAQUsLDDMh5PYTNdIECmkm7evs+ytb0tThYGUKPolOy8VROH
                                XM6gKJ5Bk2/qs6k6865tq6w+5HHhEFIBAKAIU0C3WS3zRv6SJFApKEZgh/g4VeXMP8G3PoDg3F+3sPCrUh5A3zj5mkv7lTiHDVJy8qqJ+/6aIdH9Gj+5YnAhn3xEZh9qk/5LUhWrrA
                                AAKKoftMwfuZgYUEkoYGGH5PpXjZf7AJIAAuNaZCXcUOjgq9a+SUo+WeIUHrEkGnX/qUMfZEB0n/rptwxNUqkPy3xcoeDvkhm3CQQAoDQTwKtzjX/6DDmg0lDAwo6eJNm8HQhPW5zk
                                ryxZ600eKbVmiaRMCTP4d5KKRj0wcfCTDIeuVzfnjuEeF8bKfFzi9i5JJqduBQBACd2ca9/jLDU1sdcnKg4FLLyh5mmZI+R6J0kAwbmycalaStX4IQeuneLSu0r36/tvOlPxSY+M36+F
                                odB1auYsHxHFPk6ycR4XDt0aNUUrAABKzvXH3tU2ITfvqDxhoBJRwMIbMmkmKQDhSaWsZPsejFj26MBC4t8s1b5Hbv7zTVFq0hPjh21hJOy+2um3HGjp9Mflfqpi7StRsAIAIDB/T3WkR
                                q+bf/wmokClooCF17VxqhrMNYEkgLCY9OuaBZ3/KlX7SRzNM6m+JI27X7bXwKGTf32sFRgJu65+9p31iXd8SK6PS/qAnGVWAAAE6uG4EI/OLR3FqnNUNApYeF2RpT/hrj4kAYQlMVtYqrY
                                PvfbxDyTSuFK07dJ3V08a9vlVDIFdM25Zqm5g9lg3nZ4kHSdLnN8BAAjcU5bY8ZsWffAZokClo4CF17tQtJzbZJIAQmNP1xU6byxFy++709PPPLNmbgmaTtxsxuoJQ7hd9C6ombN8hCX6u
                                LnOdIk7ygIAUB42Rq4xGxeMfJQoAApYeB25qakxkg4gCSA0yRJbqpJs3vn0M2ummTSiyM3Gkp2zesKQy+n7HVf7qZv6q5A5I3Kd6bEOIxEAAMrKJnkyeuP8Mf8gCmArClh4HanpkhMDEJZ
                                8xjM/lIq//dOIZWsb4zj5apGbjU066/6JQ66k63dM3awVRyZKJlveTpPUh7M4AABlp1PSx3Lzx9xNFMBLKGBhuzZOrt5PSkaSBBCcn/RZsuXJUjScxPF/S9aviE3m5T7x/knDfkK3v77Gm
                                bdmCxZ93KQpLj/MuIsgAADlqmCmSS1zRy0nCuCVKGBhuyyVTJMUkQQQ2GvTvCSbtx96zeMjEuncIjbZaW6n3D9p6M/p9ddWN3v5W5PEzyvIJkmqYbUVAABlLZbpjJa5o/jwDtgOClj4Dz5
                                HvXPtOpMkgOCszC4q3FWKhhPTBUV8z+iQa9z9k4bcRJdvx5l39qqrax/rbpM90XGstgIAoEeI5XZGbt7Ia4gC2D4KWPgPre2ZUyU1kgQQFpfmlaLdg659/GOSji9Sc5st0kn3nzL0Dnr8
                                lRrO/+WQ2OPzpY6z3a2eRAAA6DFimU6neAW8PgpY2N5F8hRSAILT0t43f3WxGx122aO9TPpekZrbLI/G3H/K4N/Q3S+pn337EUmSfDL2eIKkDIkAANCjxJLOys0dRfEKeAMUsPAKuWnpd7
                                nrSJIAAuO6fOD3tanYzfbuZZ9xad8iNNVpiY+7/1SKV9s63LKzlo922aeTJDmWPAAA6JEKMp1B8QrYMRSw8MpLJrfppACE99I0ixYXu9EDl63d2+Pkc0VoKu+ycatOHXprxfd007KqbHN2
                                gpIVn5VsBLtbAQDQY3Vuu9sgG7YDO4gCFl7UOlN7JAWdTBJAaOyX2cUdq4vdaipOvuNS325uJnbTqasnDPlFJfdw48xbswWLzrIN9hmX782+7AAA9GgdLp2SmzvqRqIAdhwFLLwoKaTPk1
                                RNEkBYTLaw2G0edM3aw13JpG5uxs19yqqJw26o1L7dujF74dMF2dmS+rqcAQ8AQM+2yVwfzs0fxQ1rgJ1EAQtbryKblG59Ruc5n/oDoVlTu77jlmI3ahZ/V7KoO087Lpu2atLQSyqxU7fd
                                UfALscdnS1bFMAcAoCLk3HVibv6o3xEFsPMoYEGS1PJM1Ulmvg9JAGFx98V2g+JitnnI9Wve44mP6tbfy/xzqycMXVJp/dn4yRWDC7E+HXs8WVIvRjgAABVjXeTJ6I3zx/yNKIBdQwELkq
                                TIfDpfXAGC05FS4dKituhuft3j31N3bsJk+trqCcP+p5I68oXCVaHgFK4AAKg8j0nRCRvnj3qQKIBdRwELap1WdUji/j6SAIJzfe0SPVvMBg++9vGPyuwd3djE0lUThn6jUjqQwhUAAJXN
                                pH93pvKjtlww9knSAHYPBSwoSXyGjHteAcFxFXfz9mWesnjN/+vG1Zi/GDhwyPRVFdB1FK4AAIBJv43aUx/asnRUC2kAu48CVoV77mzVynQaSQDB+Vvdkvyfi9ngwfGas106pJumcHf3rU
                                lP/PWxVujJnVYzZcWeUZV/tVDwcyWxOTsAABXLbmjJVZ2uy49tJwuga1DAqnBVVZkzJGVJAghsymOaV8z2hl32aC+XvtJNSzFXZhIfc+/YQZt7an8NmnxTn03VmZke+RfkqmMEAwBQ0RO5
                                ebmGP87RvKaEMICuQwGrwrnpPL47CASnua2QX1bMBnv3is53aXA3PPUTqVRhzL8m7tfcI3uqqSmqa37HyW1u35M0TNwNAwCAShZLOj83d+RCogC6HgWsCrZxSvr9Jh1GEkBYXHbJoKUq2m
                                qlIy57tL5d+mw3PPXzsfkJq8bvt6Yn9lPdzOXH+Qb9j0tHMGoBAKh4m9xsQuvckTcTBdA9KGBVMtN0QgCCk0jRkmI2uKVX9HmTGrv4aTs80kkPnjLs/p7WQfWzbz8iSZLvuXQ8wxUAALj0
                                RMqTD22cN+ZvpAF0H749VqE2zegzqBDnH5OUIQ0gqNPyzXWLO8cWq7URyx4dGMfRI5J6d+U8zs0/vnrCsKt7Us80zrx1n7zZV0z2CUkpxioAAJDrj3Ecf3TTog8+QxhA92IFVoUqFArTZB
                                SvgOBYUtQ9E+Ik9WnJe3fx036jJxWvBnx6Rd/NHf7F2GyOdX1WAACgbOdtujznyRQt+mAHYQDFeMmh4vg4VeX2yKyRawBpAEF5ODsgf6A1qSh3rDnwmqf6R5Z/VFJNF76p3HD/hCGnyKxHbGdeO3PFWDOfJ2kYwxMAAGwTm+tLLfNHfZcogOJhBVYFyvWrGid3ildAY
                                Ey+qFjFK0kyy39aXVi8kvSXPjWZM3tC8So76/YD5Ml8mY9kZAIAgJdpljSpZf6o5UQBFBcFrEoU+XRu9Q6ExaUt6ixcXqz2Dv7RE/3k8bQuXIf7WNrjsfeOHbq5rDtizrLe2bj2c1LyOZl6MTIBAMDL/N1S6ZNbLjjuEaIAio8CVoVpnpY5Qq6jSQIIzjV1P9SGYjVmmcIcN6vtkidzt
                                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
                                bENhrU77ImlS0olHkPvE1DuSi3PxRf6ZHAAAorZQVlkrqJAkgODNdMmJAOSqbApbPUW+Tn0WXAYG9NqUt3lm4vGgNTr4nI+lj2/mb5z2d/yo9AgBA6dUs0jOSfkYSQHAOaJmefj8xoBy
                                VTQGrtSMzSVI/ugwIztV1P9SGYjVW2/v5kZL6v/rPXfo8G7cDABAOk7OZOxAi13RCQDkqnxVYLu6YAIRpSTEbM/nJ2/njP7c23n0pXQEAQDiyiwu/M+mfJAGExdw+tHV/aaC8lEUBKzc
                                t/S5JR9JdQHB+X784f2/RWmu6My3Xia/+Yzf/FBu3AwAQnsS1iBSA4KSiODmXGFBuyqKA5bJpdBUQIivqpLTu+Y736T++Pmg3tM4dfRd9AQBAeDYn+SslNZMEENo03if7TFUTBMpJ8AW
                                s1pnaQ77dDZsBlNZz2XTnT4rZoJt95FV/1KmUf5GuAAAgTIOWarNMV5IEEJw9cvkqrrNRVoIvYMVxerJEZRgIj11k89VR1Cbdx77qTxbkLhj1EH0BAEDIE/pogSQnCCC0aoCzmTvKa8i
                                GfHA+TilzO4duAoJTKKRTFxWzwZpZKw6TafDL/qhZKX2TrgAAIGx1F3U8KNMdJAGEdsGtozdOzbDXNMpG0AWs1j2rTpQ0jG4CQnuv81/0m7/liWK2aeYjX/VHX89dMGoDvQEAQPjMbCE
                                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' height='50'></td>
                                <td><center><h1>PETTY CASH REQUEST FORM</h1></center></td>
                                <td>Control No. PCF-".str_pad($files['requestID'], 4, '0', STR_PAD_LEFT)."</td>
                            </tr>
                            <tr>
                                <td colspan='2'><b>NAME</b> : ".$files['Fullname']."</td>
                                <td><b>DATE</b> : ".$files['Date']."</td>
                            </tr>
                            <tr>
                                <td colspan='3'><b>DEPARTMENT</b> : ".$files['Department']."</td>
                            </tr>
                            <tr>
                                <td colspan='3'><b>PURPOSE</b> : 
                                <div style='height:150px;'>".$files['Purpose']."</div>
                                </td>'
                            </tr>
                            <tr>
                                <td colspan='3'><b>AMOUNT</b> : ".number_format($files['Amount'],2)."</td>
                            </tr></table>";
            $template.="<table style='width:100%;border:1px solid #000;' id='table2'>
                            <tbody>
                            <tr>
                                <td>
                                &nbsp;Prepared By<br/><br/>
                                <center><img src=".$preparedByImage." width='120'/></center>
                                <u><center><b>".$preparedBy['Fullname']."</b></center></u><br/>
                                <center>Signature Above Printed Name/Date</center>
                                <center>Position : </center>
                                </td>
                                <td></td>
                                <td>
                                Noted By<br/><br/>
                                <center><img src=".$firstApproverImg." width='120'/></center>
                                <u><center><b>".$firstApprover['Fullname']."</b></center></u><br/>
                                <center>Signature Above Printed Name/Date</center>
                                <center>Department Manager</center>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                Verified By<br/><br/>
                                <center><img src=".$verifierImg." width='120'/></center>
                                <u><center><b>".$verifierName['Fullname']."</b></center></u><br/>
                                <center>Signature Above Printed Name/Date</center>
                                <center>Executive Assistant</center>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                &nbsp;Approved By<br/><br/>
                                <center><img src=".$priorImg." width='120'/></center>
                                <u><center><b>".$priorName['Fullname']."</b></center></u><br/>
                                <center>Signature Above Printed Name/Date</center>
                                <center>Fuel Manager</center>
                                </td>
                                <td></td>
                                <td>
                                &nbsp;<br/><br/>
                                <center><img src=".$finalImg." width='120'/></center>
                                <u><center><b>".$finalName['Fullname']."</b></center></u><br/>
                                <center>Signature Above Printed Name/Date</center>
                                <center>General Manager</center>
                                </td>
                            </tr>
                            </tbody>
                        </table>";
            $template.="<table style='width:100%;' id='vendor'>
                            <tr>
                                <td>Reference Document / Form# : FA2025-001</td>
                            </tr>
                        </table>";
            $template.="</body>";
            $dompdf->loadHtml($template);
            $dompdf->setPaper('Letter', 'portrait');
            $dompdf->render();
            $dompdf->stream("PCF-".str_pad($files['requestID'], 4, '0', STR_PAD_LEFT).".pdf");
            exit();
        }
        else
        {
            echo "No Record(s) found";
        }
        
    }
}