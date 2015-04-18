<?php





function dump($var) {
    echo '<pre>';
    print_r($var);
    echo '<pre>';
}

function redirect($url, $msg = '', $time = 0) {
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg)) {
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    }
    // 通过header跳转
    if (!headers_sent()) {
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    }

    // 通过页面自动刷新跳转
    $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
    if ($time != 0) {
        $str .= $msg;
    }

    exit($str);

}


/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id 数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xml_encode($data, $root = 'root', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8') {
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * 数据XML编码
 * @param mixed $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id 数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item = 'item', $id = 'id') {
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}


/**
 * URL组装 支持不同URL模式
 * @param string $url URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @param string|boolean $suffix 伪静态后缀，默认为true表示获取配置值
 * @param boolean $domain 是否显示域名
 * @return string
 */
function urlgen($url = '', $vars = '', $suffix = true, $domain = false) {

    return $url;
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 */
function addslashes_deep($value) {
    if (empty($value))
        return $value;
    else
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
}

/*
 * 实例化一个模型；
 *
 * */

function M($model = '', $prefix = TABPREFIX) {
    if (!$model) {
        return new model();
    } else {
        $modelname = strtolower($model) . 'Model';
        if (file_exists(APPPA . 'model/' . $modelname . '.class.php')) {
            return new $modelname;
        } else {
            return new model($model, $prefix);
        }

    }
}


/* 
 * 文件缓存和读取
 *  */

function F($name, $value = '', $path = RUNTIME) {
    $filename = $path . $name . '.php';
    //如果$value为空则试着读取缓存
    if ($value === '') {
        if (is_file($filename)) {
            $v = include $filename;
        } else {
            $v = false;
        }
        return $v;
        //如果$value=null则 删除对应的缓文件
    } elseif (is_null($value)) {
        return unlink($filename);
        //其他情况写缓存
    } else {
        $dir = dirname($filename);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        $value = var_export($value, true);
        return file_put_contents($filename, "<?php \n return " . $value . ";\n?>");
    }
}


function throw_exception($msg, $code = 0) {
    throw new \Small\Exception($msg, $code);
}


