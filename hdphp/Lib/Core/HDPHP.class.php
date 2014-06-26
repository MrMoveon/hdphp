<?php
// .-----------------------------------------------------------------------------------
// |  Software: [HDPHP framework]
// |   Version: 2013.01
// |      Site: http://www.hdphp.com
// |-----------------------------------------------------------------------------------
// |    Author: 向军 <houdunwangxj@gmail.com>
// | Copyright (c) 2012-2013, http://houdunwang.com. All Rights Reserved.
// |-----------------------------------------------------------------------------------
// |   License: http://www.apache.org/licenses/LICENSE-2.0
//'-----------------------------------------------------------------------------------
final class HDPHP
{
    /**
     * 初始化应用
     */
    static public function init()
    {
        //加载应用配置
        is_file(APP_CONFIG_PATH . 'config.php')                 and C(require(APP_CONFIG_PATH . 'config.php'));
        is_file(APP_CONFIG_PATH . 'event.php')                  and C('APP_EVENT', require APP_CONFIG_PATH . 'event.php');
        is_file(APP_CONFIG_PATH . 'alias.php')                  and alias_import(APP_CONFIG_PATH . 'alias.php');
        is_file(APP_LANGUAGE_PATH . C('LANGUAGE') . '.php')     and L(require APP_LANGUAGE_PATH . C('LANGUAGE') . '.php');
        //解析路由
        Route::parseUrl();
        defined('MODULE_PATH')                                  or define('MODULE_PATH', APP_PATH.MODULE.'/');
        defined('MODULE_CONTROLLER_PATH')                       or define('MODULE_CONTROLLER_PATH', MODULE_PATH . 'Controller/');
        defined('MODULE_MODEL_PATH')                            or define('MODULE_MODEL_PATH', MODULE_PATH . 'Model/');
        defined('MODULE_CONFIG_PATH')                           or define('MODULE_CONFIG_PATH', MODULE_PATH . 'Config/');
        defined('MODULE_EVENT_PATH')                            or define('MODULE_EVENT_PATH', MODULE_PATH . 'Event/');
        defined('MODULE_LANGUAGE_PATH')                         or define('MODULE_LANGUAGE_PATH', MODULE_PATH . 'Language/');
        defined('MODULE_TAG_PATH')                              or define('MODULE_TAG_PATH', MODULE_PATH . 'Tag/');
        defined('MODULE_LIB_PATH')                              or define('MODULE_LIB_PATH', MODULE_PATH . 'Lib/');
        //应用配置
        is_file(MODULE_CONFIG_PATH . 'config.php')              and C(require(MODULE_CONFIG_PATH . 'config.php'));
        is_file(MODULE_CONFIG_PATH . 'event.php')               and C('APP_EVENT', require MODULE_CONFIG_PATH . 'event.php');
        is_file(MODULE_CONFIG_PATH . 'alias.php')               and alias_import(MODULE_CONFIG_PATH . 'alias.php');
        is_file(MODULE_LANGUAGE_PATH . C('LANGUAGE') . '.php')  and L(require MODULE_LANGUAGE_PATH . C('LANGUAGE') . '.php');
        //模板目录常量
        defined('MODULE_TPL_PATH')                              or define('MODULE_TPL_PATH',C('TPL_PATH')?C('TPL_PATH').C('TPL_STYLE'):MODULE_PATH.'Tpl/'.C('TPL_STYLE'));
        defined('MODULE_PUBLIC_PATH')                           or define('MODULE_PUBLIC_PATH', MODULE_TPL_PATH .'Public/');
        defined('CONTROLLER_TPL_PATH')                          or define('CONTROLLER_TPL_PATH',MODULE_TPL_PATH.CONTROLLER.'/');
        //网站根-Static目录
        defined("__STATIC__")                                   or define("__STATIC__", __ROOT__ . '/Static/');
        defined("__TPL__")                                      or define("__TPL__", __ROOT__  . '/'.MODULE_TPL_PATH);
        defined("__PUBLIC__")                                   or define("__PUBLIC__", __TPL__ . 'Public/');
        defined("__CONTROLLER_TPL__")                           or define("__CONTROLLER_TPL__", __TPL__  . CONTROLLER.'/');
        //来源URL
        define("__HISTORY__",                                   isset($_SERVER["HTTP_REFERER"])?$_SERVER["HTTP_REFERER"]:null);
        //=========================环境配置
        date_default_timezone_set(C('DEFAULT_TIME_ZONE'));
        @ini_set('memory_limit',                                '128M');
        @ini_set('register_globals',                            'off');
        @ini_set('magic_quotes_runtime',                        0);
        define('NOW',                                           $_SERVER['REQUEST_TIME']);
        define('NOW_MICROTIME',                                 microtime(true));
        define('REQUEST_METHOD',                                $_SERVER['REQUEST_METHOD']);
        define('IS_GET',                                        REQUEST_METHOD == 'GET' ? true : false);
        define('IS_POST',                                       REQUEST_METHOD == 'POST' ? true : false);
        define('IS_PUT',                                        REQUEST_METHOD == 'PUT' ? true : false);
        define('IS_AJAX',                                       ajax_request());
        define('IS_DELETE',                                     REQUEST_METHOD == 'DELETE' ? true : false);
        define('HTTP_REFERER',                                  isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:null);
        //注册自动载入函数
        spl_autoload_register(array(__CLASS__,                  'autoload'));
        set_error_handler(array(__CLASS__,                      'error'), E_ALL);
        set_exception_handler(array(__CLASS__,                  'exception'));
        register_shutdown_function(array(__CLASS__,             'fatalError'));
        HDPHP::_appAutoLoad();
        //COOKIE安全处理
        if(!empty($_COOKIE)){
            foreach($_COOKIE as $name=>$v){
                $_name = preg_replace('@[^0-9a-z]@i', '', $name);
                unset($_COOKIE[$name]);
                $_COOKIE[$_name]=$v;
            }
        }
    }
    /**
     * 自动加载Lib文件
     */
    static private function _appAutoLoad()
    {
        //自动加载文件列表
        $files = C('AUTO_LOAD_FILE');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                require_array(array(
                    APP_LIB_PATH . $file,
                    MODULE_LIB_PATH . $file
                )) || require_cache($file);
            }
        }
    }

    /**
     * 自动载入函数
     * @param string $className 类名
     * @access private
     * @return void
     */
    static public function autoload($className)
    {
        $class = ucfirst($className) . '.class.php'; //类文件
        if (substr($className, -5) == 'Model') {
            if (require_array(array(
                HDPHP_DRIVER_PATH . 'Model/' . $class,
                MODULE_MODEL_PATH . $class,
                APP_MODEL_PATH . $class
            ))
            ) return;
        } elseif (substr($className, -7) == 'Control') {
            if (require_array(array(
                HDPHP_CORE_PATH . $class,
                MODULE_CONTROLLER_PATH . $class,
                APP_CONTROLLER_PATH . $class
            ))
            ) return;
        } elseif (substr($className, 0, 2) == 'Db') {
            if (require_array(array(
                HDPHP_DRIVER_PATH . 'Db/' . $class
            ))
            ) return;
        } elseif (substr($className, 0, 5) == 'Cache') {
            if (require_array(array(
                HDPHP_DRIVER_PATH . 'Cache/' . $class,
            ))
            ) return;
        } elseif (substr($className, 0, 4) == 'View') {
            if (require_array(array(
                HDPHP_DRIVER_PATH . 'View/' . $class,
            ))
            ) return;
        } elseif (substr($className, -5) == 'Event') {
            if (require_array(array(
                MODULE_EVENT_PATH . $class,
                APP_EVENT_PATH . $class
            ))
            ) return;
        } elseif (substr($className, -3) == 'Tag') {
            if (require_array(array(
                APP_TAG_PATH . $class,
                MODULE_TAG_PATH . $class
            ))
            ) return;
        } elseif (substr($className, -7) == 'Storage') {
            if (require_array(array(
                HDPHP_DRIVER_PATH . 'Storage/' . $class
            ))
            ) return;
        } elseif (alias_import($className)) {
            return;
        } elseif (require_array(array(
            MODULE_LIB_PATH . $class,
            APP_LIB_PATH . $class,
            HDPHP_CORE_PATH . $class,
            HDPHP_EXTEND_PATH . $class,
            HDPHP_EXTEND_PATH . '/Tool/' . $class
        ))
        ) {
            return;
        }
        $msg = "Class {$className} not found";
        Log::write($msg);
        halt($msg);
    }

    /**
     * 自定义异常理
     * @param $e
     */
    static public function exception($e)
    {
        halt($e->__toString());
    }

    //错误处理
    static public function error($errno, $error, $file, $line)
    {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                $msg = $error. $file . " 第 $line 行.";
                if(C('LOG_RECORD')) Log::write("[$errno] " . $msg, Log::ERROR);
                function_exists('halt') ? halt($msg) : exit('ERROR:' . $msg);
                break;
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "[$errno] $error " . $file . " 第 $line 行.";
                trace($errorStr, 'NOTICE');
                //SHUT_NOTICE关闭提示信息
                if (DEBUG && C('SHOW_NOTICE'))
                    require HDPHP_TPL_PATH . 'notice.html';
                break;
        }
    }

    //致命错误处理
    static public function fatalError()
    {
        if ($e = error_get_last()) {
            self::error($e['type'], $e['message'], $e['file'], $e['line']);
        }
    }
}
?>