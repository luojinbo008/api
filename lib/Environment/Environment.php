<?php
/**
 * Created by PhpStorm.
 * User: zhoutianliang
 * Date: 2016/2/19
 * Time: 16:54
 */
namespace Lib\Environment;

class Environment
{
    protected $env = 'production';
    //环境MAP
    protected $map = array();
    
    static $init = null;
    /* --------------------------------------------*/
    /**
        * @Synopsis __construct 
        * 构造函数，初化骀$map
        * @Param $map
        *
        * @Returns 
     */
    /* --------------------------------------------*/
    private function __construct(array $map = array())
    {
        $this->map = $map;
    }

    /* --------------------------------------------*/
    /**
        * @Synopsis Init 
        * 初化始
        * @Returns 
     */
    /* --------------------------------------------*/
    public static function Init($map = array())
    {
        if (self::$init === null) {
            self::$init = new self($map);
        }
        return self::$init;
    }
    /* --------------------------------------------*/
    /**
        * @Synopsis getCurrentEnvironment 
        * 获取得当前运行的环境
        * @Returns 
     */
    /* --------------------------------------------*/
    public function getCurrentRunEnvironment()
    {
        if (empty($this->map)) return $this->env;
        $hostname = gethostname();
        foreach ($this->map as $key => $value) {
            foreach ($value as $val) {
                if ($this->strIs($val, $hostname)) {
                    $this->env = $key;
                }
            }
        }
        return $this->env;
    }

    /* --------------------------------------------*/
    /**
        * @Synopsis setMap 
        * 设置环境MAP
        * @Param $map
        * @Example array('dev' => array('BF*'), 'test' => array('test*'))
        * @Returns 
     */
    /* --------------------------------------------*/
    public function setMap(array $map)
    {
        $this->map = $map;
    }
    
    /* --------------------------------------------*/
    /**
        * @Synopsis strIs 
        * 字符匹配
        * @Param $pattern
        * @Param $value
        *
        * @Returns 
     */
    /* --------------------------------------------*/
    private function strIs($pattern, $value)
    {
        if ($pattern == $value) return true;

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern).'\z';

        return (bool) preg_match('#^'.$pattern.'#', $value);
    }
}

