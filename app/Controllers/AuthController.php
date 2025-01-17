<?php

namespace App\Controllers;
use App\Libraries\Hash;

class AuthController extends BaseController
{
    private $db;
    public function __construct()
    {
        helper(['form']);
        $this->db = db_connect();
    }

    public function auth()
    {
        $accountModel = new \App\Models\accountModel();
        //data
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $token = $this->request->getPost('csrf_test_name');

        $validation = $this->validate([
            'csrf_test_name'=>'required',
            'username'=>'required|is_not_unique[tblaccount.Username]',
            'password'=>'required|min_length[8]|max_length[12]|regex_match[/[A-Z]/]|regex_match[/[a-z]/]|regex_match[/[0-9]/]'
        ]);
        if(!$validation)
        {
            return view('welcome_message',['validation'=>$this->validator]);
        }
        else
        {
            $user_info = $accountModel->where('Username', $username)->WHERE('Status',1)->first();
            $check_password = Hash::check($password, $user_info['Password']);
            if(!$check_password || empty($check_password))
            {
                session()->setFlashdata('fail','Invalid Username or Password!');
                return redirect()->to('/')->withInput();
            }
            else
            {
                session()->set('loggedUser', $user_info['accountID']);
                session()->set('fullname', $user_info['Fullname']);
                session()->set('role',$user_info['Role']);
                return redirect()->to('/dashboard');
            }
        }
    }

    public function logout()
    {
        if(session()->has('loggedUser'))
        {
            session()->remove('loggedUser');
            session()->destroy();
            return redirect()->to('/?access=out')->with('fail', 'You are logged out!');
        }
    }
}
