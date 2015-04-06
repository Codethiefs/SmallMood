<?php
namespace Small;

class Trace
{
    //运行开始时间
    static $starttime = null;
    //运行结束时间
    static $stoptime = null;
    //错误信息
    static $errors = array();
    //运行信息
    static $infos = array();
    //自动包含文件
    static $incfiles = array();
    //SQL语句
    static $sqls = array();

    static $msg = array(
        E_WARNING => '警告',
        E_NOTICE => '提醒',
        E_STRICT => '警告',
        E_USER_ERROR => '错误',
        E_USER_WARNING => '警告',
        E_USER_NOTICE => '提醒',
    );

    /* 开始记时 */
    public static function start()
    {
        self::$starttime = microtime(true);
    }

    /* 结束记时 */
    public static function stop()
    {
        self::$stoptime = microtime(true);
    }

    public static function timetake()
    {
        return round(self::$stoptime - self::$starttime, 5);
    }

    /*
     * 捕捉错误信息并处理
     *  */
    public static function catcher($errno, $errstr, $errfile, $errline)
    {
        if (!isset(self::$msg[$errno])) {
            $errno = 'Unkown';
        }

        if ($errno == E_NOTICE || $errno == E_USER_NOTICE)
            $color = "#000088";
        else
            $color = "red";

        $mess = '<font color=' . $color . '>';
        $mess .= '<b>' . self::$msg[$errno] . "</b>[在文件 {$errfile} 中,第 $errline 行]:";
        $mess .= $errstr;
        $mess .= '</font>';
        self::addmsg($mess);
    }

    /*
     * 向信息池中添加消息，
     * type=0;错误信息
     * type=1;运行时信息
     * type=2;自动包含文件
     * type=3;SQL语句
     *
     *  */
    public static function addmsg($msg, $type = 0)
    {
        //只有在调试模式开启时才记录信息；
        if (!APP_DEBUG) {
            return;
        }

        switch ($type) {
            case 0 :
                self::$errors[] = $msg;
                break;
            case 1 :
                self::$infos[] = $msg;
                break;
            case 2 :
                self::$incfiles[] = $msg;
                break;
            case 3 :
                self::$sqls[] = $msg;
                break;

        }
    }

    /* 显示消息
     *  */
    public static function trace()
    {
        echo '<div style="clear:both;text-align:left;font-size:14px;color:#888;width:95%;margin:10px auto;padding:10px;background:#F5F5F5;border:1px dotted #778855;z-index:100">';
        echo '<div style="float:left;width:100%;"><span style="float:left;width:200px;"><b>运行信息</b>( <font color="red">' . self::timetake() . ' </font>秒):</span><span onclick="this.parentNode.parentNode.style.display=\'none\'" style="cursor:pointer;float:right;width:35px;background:#500;border:1px solid #555;color:white">关闭X</span></div><br>';
        echo '<ul style="clear:both;margin:0px;padding:0 10px 0 10px;list-style:none">';

        if (count(self::$errors) > 0) {
            echo '<br>［错误信息］';
            foreach (self::$errors as $error) {
                echo '<li style="color:red;">&nbsp;&nbsp;&nbsp;&nbsp;' . $error . '</li>';
            }
        }
        if (count(self::$infos) > 0) {
            echo '<br>［系统信息］';
            foreach (self::$infos as $info) {
                echo '<li >&nbsp;&nbsp;&nbsp;&nbsp;' . $info . '</li>';
            }
        }
        if (count(self::$incfiles) > 0) {
            echo '<br>［自动包含］';
            foreach (self::$incfiles as $file) {
                echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;' . $file . '</li>';
            }
        }
        if (count(self::$sqls) > 0) {
            echo '<br>［SQL语句］';
            foreach (self::$sqls as $sql) {
                echo '<li >&nbsp;&nbsp;&nbsp;&nbsp;' . $sql . '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }
}