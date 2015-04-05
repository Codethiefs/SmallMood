<?php
namespace Home\Controller;

class IndexController
{
    public function index($name,$sex=1)
    {
        echo $name,' ', $sex;
    }

}