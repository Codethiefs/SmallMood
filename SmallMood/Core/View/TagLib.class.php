<?php
namespace Small\View;

/**
 * 标签库TagLib类
 */
class TagLib {
    use ParseVar;

    /**
     * 标签库定义XML文件
     * @var string
     * @access protected
     */
    protected $xml = '';

    // 标签定义
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） level 嵌套层次
        'php' => ['close' => 1, 'level' => 1, 'attr' => ''],
        'volist' => ['close' => 1, 'level' => 3, 'attr' => 'name,id,offset,length,key,mod'],
        'foreach' => ['close' => 1, 'level' => 3, 'attr' => 'name,item,key'],
        'if' => ['close' => 1, 'level' => 2, 'attr' => 'condition'],
        'elseif' => ['close' => 0, 'level' => 1, 'attr' => 'condition'],
        'else' => ['close' => 0, 'level' => 1, 'attr' => ''],
        'switch' => ['close' => 1, 'level' => 2, 'attr' => 'name'],
        'case' => ['close' => 1, 'level' => 1, 'attr' => 'value,break'],
        'default' => ['close' => 0, 'level' => 1, 'attr' => ''],
        'compare' => ['close' => 1, 'level' => 3, 'attr' => 'name,value,type'],
        'eq' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'neq' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'gt' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'egt' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'lt' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'elt' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'range' => ['close' => 1, 'level' => 3, 'attr' => 'name,value,type'],
        'in' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'notin' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'between' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'notbetween' => ['close' => 1, 'level' => 3, 'attr' => 'name,value'],
        'empty' => ['close' => 1, 'level' => 3, 'attr' => 'name'],
        'notempty' => ['close' => 1, 'level' => 3, 'attr' => 'name'],
        'present' => ['close' => 1, 'level' => 3, 'attr' => 'name'],
        'notpresent' => ['close' => 1, 'level' => 3, 'attr' => 'name'],
        'defined' => ['close' => 1, 'level' => 3, 'attr' => 'name'],
        'notdefined' => ['close' => 1, 'level' => 3, 'attr' => 'name'],
        'assign' => ['close' => 0, 'level' => 1, 'attr' => 'name,value'],
        'define' => ['close' => 0, 'level' => 1, 'attr' => 'name,value'],
        'for' => ['close' => 1, 'level' => 3, 'attr' => 'start,end,name,comparison,step'],
    ];


    /**
     * 标签库标签列表
     * @var string
     * @access protected
     */
    protected $tagList = [];

    /**
     * 标签库分析数组
     * @var string
     * @access protected
     */
    protected $parse = [];

    /**
     * 标签库是否有效
     * @var string
     * @access protected
     */
    protected $valid = false;

    protected $comparison = array(' nheq ' => ' !== ', ' heq ' => ' === ', ' neq ' => ' != ', ' eq ' => ' == ', ' egt ' => ' >= ', ' gt ' => ' > ', ' elt ' => ' <= ', ' lt ' => ' < ');

    protected static $instance;

    /**
     * 单例
     * @return TagLib
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // 获取标签定义
    public function getTags() {
        return $this->tags;
    }


    /**
     * 解析条件表达式
     * @access public
     * @param string $condition 表达式标签内容
     * @return array
     */
    public function parseCondition($condition) {
        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is', '$\\1->\\2 ', $condition);
        $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"] ', $condition);
        if (false !== strpos($condition, '$Small')){
            $that = $this;
            $condition = preg_replace_callback('/(\$Small.*?)\s/is', function($match) use($that){
                $varStr = $match[1];
                return $that->parseSmallVar($varStr);
            }, $condition);
        }

        return $condition;
    }

    /**
     * php标签解析
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _php($attrs, $content) {
        $parseStr = '<?php ' . $content . ' ?>';
        return $parseStr;
    }


    /**
     * volist标签解析 循环输出数据集
     * 格式：
     * <volist name="userList" id="user" empty="" >
     * {user.username}
     * {user.email}
     * </volist>
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string|void
     */
    public function _volist($attrs, $content) {
        $name = $attrs['name'];
        $id = $attrs['id'];
        $empty = isset($tag['empty']) ? $tag['empty'] : '';
        $key = !empty($tag['key']) ? $tag['key'] : 'i';
        $mod = isset($tag['mod']) ? $tag['mod'] : '2';
        // 允许使用函数设定数据集 <volist name=":fun('arg')" id="vo">{$vo.name}</volist>
        $parseStr = '<?php ';
        if (0 === strpos($name, ':')) {
            $parseStr .= '$_result=' . substr($name, 1) . ';';
            $name = '$_result';
        } else {
            $name = $this->autoBuildVar($name);
        }
        $parseStr .= 'if(is_array(' . $name . ')): $' . $key . ' = 0;';
        if (isset($tag['length']) && '' != $tag['length']) {
            $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $tag['offset'] . ',' . $tag['length'] . ',true);';
        } elseif (isset($tag['offset']) && '' != $tag['offset']) {
            $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $tag['offset'] . ',null,true);';
        } else {
            $parseStr .= ' $__LIST__ = ' . $name . ';';
        }
        $parseStr .= 'if( count($__LIST__)==0 ) : echo "' . $empty . '" ;';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach($__LIST__ as $key=>$' . $id . '): ';
        $parseStr .= '$mod = ($' . $key . ' % ' . $mod . ' );';
        $parseStr .= '++$' . $key . ';?>';
        $parseStr .= ($content);
        $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';

        return $parseStr;
    }

    /**
     * foreach标签解析 循环输出数据集
     * @access public
     * @param string $content 标签内容
     * @return string|void
     */
    public function _foreach($attr, $content) {
        $name = $attr['name'];
        $item = $attr['item'];
        $key = !empty($attr['key']) ? $attr['key'] : 'key';
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(is_array(' . $name . ')): foreach(' . $name . ' as $' . $key . '=>$' . $item . '): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; ?>';

            return $parseStr;
    }

    /**
     * if标签解析
     * 格式：
     * <if condition=" $a eq 1" >
     * <elseif condition="$a eq 2" />
     * <else />
     * </if>
     * 表达式支持 eq neq gt egt lt elt == > >= < <= or and || &&
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _if($tag, $content) {
        $condition = $this->parseCondition($tag['condition']);
        $parseStr = '<?php if(' . $condition . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * 格式：见if标签
     * @access public
     * @param array $tag 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _elseif($tag, $content) {
        $condition = $this->parseCondition($tag['condition']);
        $parseStr = '<?php elseif(' . $condition . '): ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * @access public
     * @param array $tag 标签属性
     * @return string
     */
    public function _else($attrs) {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }

    /**
     * switch标签解析
     * 格式：
     * <switch name="a.name" >
     * <case value="1" break="false">1</case>
     * <case value="2" >2</case>
     * <default />other
     * </switch>
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _switch($attrs, $content) {
        $name = $attrs['name'];
        $varArray = explode('|', $name);
        $name = array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if (count($varArray) > 0){
            $name = $this->parseVarFunction($name, $varArray);
        }
        $parseStr = '<?php switch(' . $name . '): ?>' . $content . '<?php endswitch;?>';
        return $parseStr;
    }

    /**
     * case标签解析 需要配合switch才有效
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _case($attrs, $content) {
        $value = $attrs['value'];
        if ('$' == substr($value, 0, 1)) {
            $varArray = explode('|', $value);
            $value = array_shift($varArray);
            $value = $this->autoBuildVar(substr($value, 1));
            if (count($varArray) > 0)
                $value = $this->parseVarFunction($value, $varArray);
            $value = 'case ' . $value . ': ';
        } elseif (strpos($value, '|')) {
            $values = explode('|', $value);
            $value = '';
            foreach ($values as $val) {
                $value .= 'case "' . addslashes($val) . '": ';
            }
        } else {
            $value = 'case "' . $value . '": ';
        }
        $parseStr = '<?php ' . $value . ' ?>' . $content;
        $isBreak = isset($attrs['break']) ? $attrs['break'] : '';
        if ('' == $isBreak || $isBreak) {
            $parseStr .= '<?php break;?>';
        }
        return $parseStr;
    }

    /**
     * default标签解析 需要配合switch才有效
     * 使用： <default />ddfdf
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _default($attrs) {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }

    /**
     * compare标签解析
     * 用于值的比较 支持 eq neq gt lt egt elt heq nheq 默认是eq
     * 格式： <compare name="" type="eq" value="" >content</compare>
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _compare($attrs, $content, $type = 'eq') {
        $name = $attrs['name'];
        $value = $attrs['value'];
        $type = isset($attrs['type']) ? $attrs['type'] : $type;
        $type = $this->parseCondition(' ' . $type . ' ');
        $varArray = explode('|', $name);
        $name = array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if (count($varArray) > 0)
            $name = $this->parseVarFunction($name, $varArray);
        if ('$' == substr($value, 0, 1)) {
            $value = $this->autoBuildVar(substr($value, 1));
        } else {
            $value = '"' . $value . '"';
        }
        $parseStr = '<?php if((' . $name . ') ' . $type . ' ' . $value . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    public function _eq($attrs, $content) {
        return $this->_compare($attrs, $content, 'eq');
    }

    public function _equal($attrs, $content) {
        return $this->_compare($attrs, $content, 'eq');
    }

    public function _neq($attrs, $content) {
        return $this->_compare($attrs, $content, 'neq');
    }

    public function _notequal($attrs, $content) {
        return $this->_compare($attrs, $content, 'neq');
    }

    public function _gt($attrs, $content) {
        return $this->_compare($attrs, $content, 'gt');
    }

    public function _lt($attrs, $content) {
        return $this->_compare($attrs, $content, 'lt');
    }

    public function _egt($attrs, $content) {
        return $this->_compare($attrs, $content, 'egt');
    }

    public function _elt($attrs, $content) {
        return $this->_compare($attrs, $content, 'elt');
    }

    public function _heq($attrs, $content) {
        return $this->_compare($attrs, $content, 'heq');
    }

    public function _nheq($attrs, $content) {
        return $this->_compare($attrs, $content, 'nheq');
    }

    /**
     * range标签解析
     * 如果某个变量存在于某个范围 则输出内容 type= in 表示在范围内 否则表示在范围外
     * 格式： <range name="var|function"  value="val" type='in|notin' >content</range>
     * example: <range name="a"  value="1,2,3" type='in' >content</range>
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @param string $type 比较类型
     * @return string
     */
    public function _range($attrs, $content, $type = 'in') {
        $name = $attrs['name'];
        $value = $attrs['value'];
        $varArray = explode('|', $name);
        $name = array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if (count($varArray) > 0)
            $name = $this->parseVarFunction($name, $varArray);

        $type = isset($attrs['type']) ? $attrs['type'] : $type;

        if ('$' == substr($value, 0, 1)) {
            $value = $this->autoBuildVar(substr($value, 1));
            $str = 'is_array(' . $value . ')?' . $value . ':explode(\',\',' . $value . ')';
        } else {
            $value = '"' . $value . '"';
            $str = 'explode(\',\',' . $value . ')';
        }
        if ($type == 'between') {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '>= $_RANGE_VAR_[0] && ' . $name . '<= $_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } elseif ($type == 'notbetween') {
            $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '<$_RANGE_VAR_[0] || ' . $name . '>$_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
        } else {
            $fun = ($type == 'in') ? 'in_array' : '!in_array';
            $parseStr = '<?php if(' . $fun . '((' . $name . '), ' . $str . ')): ?>' . $content . '<?php endif; ?>';
        }
        return $parseStr;
    }

    // range标签的别名 用于in判断
    public function _in($attrs, $content) {
        return $this->_range($attrs, $content, 'in');
    }

    // range标签的别名 用于notin判断
    public function _notin($attrs, $content) {
        return $this->_range($attrs, $content, 'notin');
    }

    public function _between($attrs, $content) {
        return $this->_range($attrs, $content, 'between');
    }

    public function _notbetween($attrs, $content) {
        return $this->_range($attrs, $content, 'notbetween');
    }

    /**
     * present标签解析
     * 如果某个变量已经设置 则输出内容
     * 格式： <present name="" >content</present>
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _present($attrs, $content) {
        $name = $attrs['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notpresent标签解析
     * 如果某个变量没有设置，则输出内容
     * 格式： <notpresent name="" >content</notpresent>
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _notpresent($attrs, $content) {
        $name = $attrs['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(!isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * empty标签解析
     * 如果某个变量为empty 则输出内容
     * 格式： <empty name="" >content</empty>
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _empty($attrs, $content) {
        $name = $attrs['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    public function _notempty($attrs, $content) {
        $name = $attrs['name'];
        $name = $this->autoBuildVar($name);
        $parseStr = '<?php if(!empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * 判断是否已经定义了该常量
     * <defined name='TXT'>已定义</defined>
     * @param <type> $attr
     * @param <type> $content
     * @return string
     */
    public function _defined($attrs, $content) {
        $name = $attrs['name'];
        $parseStr = '<?php if(defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    public function _notdefined($attrs, $content) {
        $name = $attrs['name'];
        $parseStr = '<?php if(!defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }


    /**
     * assign标签解析
     * 在模板中给某个变量赋值 支持变量赋值
     * 格式： <assign name="" value="" />
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _assign($attrs, $content) {
        $name = $this->autoBuildVar($attrs['name']);
        if ('$' == substr($attrs['value'], 0, 1)) {
            $value = $this->autoBuildVar(substr($attrs['value'], 1));
        } else {
            $value = '\'' . $attrs['value'] . '\'';
        }
        $parseStr = '<?php ' . $name . ' = ' . $value . '; ?>';
        return $parseStr;
    }

    /**
     * define标签解析
     * 在模板中定义常量 支持变量赋值
     * 格式： <define name="" value="" />
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _define($attrs, $content) {
        $name = '\'' . $attrs['name'] . '\'';
        if ('$' == substr($attrs['value'], 0, 1)) {
            $value = $this->autoBuildVar(substr($attrs['value'], 1));
        } else {
            $value = '\'' . $attrs['value'] . '\'';
        }
        $parseStr = '<?php define(' . $name . ', ' . $value . '); ?>';
        return $parseStr;
    }

    /**
     * for标签解析
     * 格式： <for start="" end="" comparison="" step="" name="" />
     * @access public
     * @param array $attrs 标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function _for($attrs, $content) {
        //设置默认值
        $start = 0;
        $end = 0;
        $step = 1;
        $comparison = 'lt';
        $name = 'i';
        $rand = rand(); //添加随机数，防止嵌套变量冲突
        //获取属性
        foreach ($attrs as $key => $value) {
            $value = trim($value);
            if (':' == substr($value, 0, 1))
                $value = substr($value, 1);
            elseif ('$' == substr($value, 0, 1))
                $value = $this->autoBuildVar(substr($value, 1));
            switch ($key) {
                case 'start':
                    $start = $value;
                    break;
                case 'end' :
                    $end = $value;
                    break;
                case 'step':
                    $step = $value;
                    break;
                case 'comparison':
                    $comparison = $value;
                    break;
                case 'name':
                    $name = $value;
                    break;
            }
        }

        $parseStr = '<?php $__FOR_START_' . $rand . '__=' . $start . ';$__FOR_END_' . $rand . '__=' . $end . ';';
        $parseStr .= 'for($' . $name . '=$__FOR_START_' . $rand . '__;' . $this->parseCondition('$' . $name . ' ' . $comparison . ' $__FOR_END_' . $rand . '__') . ';$' . $name . '+=' . $step . '){ ?>';
        $parseStr .= $content;
        $parseStr .= '<?php } ?>';
        return $parseStr;
    }


}