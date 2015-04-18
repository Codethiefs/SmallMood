<?php
namespace Home\Controller;

use Small\Controller;

class DefaultController extends Controller
{
    public function index()
    {
        $list = [
            [['id'=>1,'name' =>'fffff'],['id'=>2,'name' =>'bbbbb']],
        ];
        $this->assign(['list'=>$list])->display('');
    }


}