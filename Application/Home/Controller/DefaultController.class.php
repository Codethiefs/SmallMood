<?php
namespace Home\Controller;

class DefaultController
{
    public function index()
    {
        $xxx = $bbb/0;
        //new \ReflectionMethod('aaa', 'bbb');
    }

    public function error(){
        echo '404';
    }

}