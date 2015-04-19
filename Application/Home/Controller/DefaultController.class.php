<?php
namespace Home\Controller;

use Small\Controller;

class DefaultController extends Controller
{
    public function index()
    {
        $ttt = 123;
        $list = [
            [['id'=>1,'name' =>'fffff'],['id'=>2,'name' =>'']],
        ];
        $this->assign(['list'=>$list,'ttt'=>$ttt])->display('');
    }


}