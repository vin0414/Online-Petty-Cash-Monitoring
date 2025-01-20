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
                return redirect()->to('/new')->with('success', 'Form submitted successfully');
            }
            else
            {
                $validation = ['file','No file was selected or the file is invalid'];
                return view('new', ['validation' => $validation]);
            }
        }
    }
}