<?php
namespace Home\Controller;

use Small\Controller;

class DefaultController extends Controller
{
    public function index()
    {
        $this->assign(['ttt'=>123])->display('');
    }


}