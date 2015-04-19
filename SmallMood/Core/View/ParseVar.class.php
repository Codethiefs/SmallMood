<?php
namespace Small\View;

trait ParseVar {

    public function autoBuildVar($var) {
        if ('Small.' == substr($var, 0, 6)) {
            // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
            $name = $this->parseSmallVar($var);
        } elseif (false !== strpos($var, '.')) {
            //支持 {$var.property}
            $vars = explode('.', $var);
            $var = array_shift($vars);
            $name = '$' . $var;
            foreach ($vars as $key => $val) {
                $name .= '["' . $val . '"]';
            }
        } elseif (false !== strpos($var, '[')) {
            //支持 {$var['key']} 方式输出数组
            $name = "$" . $var;
            preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
            $var = $match[1];
        } elseif (false !== strpos($var, ':') && false === strpos($var, '(') && false === strpos($var, '::') && false === strpos($var, '?')) {
            //支持 {$var:property} 方式输出对象的属性
            $vars = explode(':', $var);
            $var = str_replace(':', '->', $var);
            $name = "$" . $var;
            $var = $vars[0];
        } else {
            $name = "$$var";
        }
        return $name;
    }

    /**
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     * @access public
     * @param string $name 变量名
     * @param array $varArray 函数列表
     * @return string
     */
    public function parseVarFunction($name, $varArray) {
        //对变量使用函数
        $length = count($varArray);
        for ($i = 0; $i < $length; $i++) {
            $args = explode('=', $varArray[$i], 2);
            //模板函数过滤
            $fun = trim($args[0]);
            switch ($fun) {
                case 'default':  // 特殊模板函数
                    $name = '(isset(' . $name . ') && (' . $name . ' !== ""))?(' . $name . '):' . $args[1];
                    break;
                default:
                    // 通用模板函数
                    if (isset($args[1])) {
                        if (strstr($args[1], '###')) {
                            $args[1] = str_replace('###', $name, $args[1]);
                            $name = "$fun($args[1])";
                        } else {
                            $name = "$fun($name,$args[1])";
                        }
                    } else if (!empty($args[0])) {
                        $name = "$fun($name)";
                    }
            }
        }
        return $name;
    }

    /**
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     * @access public
     * @param string $varStr 变量字符串
     * @return string
     */
    public function parseSmallVar($varStr) {
        $vars = explode('.', $varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if (count($vars) >= 3) {
            $vars[2] = trim($vars[2]);
            switch ($vars[1]) {
                case 'SERVER':
                    $parseStr = '$_SERVER[\'' . strtoupper($vars[2]) . '\']';
                    break;
                case 'GET':
                    $parseStr = '$_GET[\'' . $vars[2] . '\']';
                    break;
                case 'POST':
                    $parseStr = '$_POST[\'' . $vars[2] . '\']';
                    break;
                case 'COOKIE':
                    $parseStr = isset($vars[3]) ? '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']' : 'cookie(\'' . $vars[2] . '\')';
                    break;
                case 'SESSION':
                    $parseStr = isset($vars[3]) ? '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']' : $parseStr = 'session(\'' . $vars[2] . '\')';
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\'' . strtoupper($vars[2]) . '\']';
                    break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
                    break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);
                    break;
                case 'CONFIG':
                    $config = isset($vars[3]) ? $vars[2] . '.' . $vars[3] : $vars[2];
                    $parseStr = 'Config::config("' . $config . '")';
                    break;
                default:
                    break;
            }
        } else if (count($vars) == 2) {
            switch ($vars[1]) {
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'THINK_VERSION';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if (defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }


}