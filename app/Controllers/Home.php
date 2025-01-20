<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function __construct()
    {
        helper(['form']);
    }
    public function index()
    {
        return view('welcome_message');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function newRequest()
    {
        return view('new');
    }

    public function manageRequest()
    {
        //request
        $user = session()->get('loggedUser');
        $fileModel = new \App\Models\fileModel();
        $files = $fileModel->WHERE('accountID',$user)->findAll();
        //data
        $data = ['files'=>$files];
        return view('manage',$data);
    }
}
