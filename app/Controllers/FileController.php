<?php

namespace App\Controllers;

class FileController extends BaseController
{
    public function save()
    {
        $fileModel = new \App\Models\fileModel();
        //data
        $token = $this->request->getPost('csrf_test_name');
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
            'file'=>'required|file',
            'approver'=>'required'
        ]);
        if(!$validation)
        {
            return view('new',['validation'=>$this->validator]);
        }
        else
        {
            $user = session()->get('loggedUser');
            $status = 0;
            $amt = str_replace(",","",$amount);
            $data = ['Fullname'=>$fullname, 'Department'=>$department,'date'=>$date,
                    'Purpose'=>$purpose,'Amount'=>$amt,'File'=>'N/A','Status'=>$status,'accountID'=>$user];
            $fileModel->save($data);
            session()->setFlashdata('success','Great! Successfully submitted');
            return redirect()->to('/new')->withInput();
        }
    }
}