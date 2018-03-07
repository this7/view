<?php
namespace this7\view;

use Exception;
use this7\view\bin\compile;

class view extends compile {
    /**
     * 模板变量集合
     * @var array
     */
    protected static $vars = array();

    /**
     * 模版文件
     * @var [type]
     */
    public $tpl;

    /**
     * 数据文件
     * @var [type]
     */
    public $file;

    /**
     * 编译文件
     * @var [type]
     */
    public $compile;

    /**
     * 配置信息
     * @var [type]
     */
    public static $config;

    /**
     * 配置目录信息
     * @return [type] [description]
     */
    public function configs() {
        if (empty(self::$config)) {
            self::$config['cache_path']      = ROOT_DIR . "/temp/cache";
            self::$config['compile_path']    = ROOT_DIR . "/temp/compile";
            self::$config['template_path']   = ROOT_DIR;
            self::$config['template_suffix'] = array('html' => '.html', "js" => '.js', "css" => '.css');
        }
    }

    /**
     * 设置配置文件
     * @param string $key   键名
     * @param string $value 键值
     */
    public function setConfig($key = '', $value = "") {
        self::$config[$key] = $value;
    }

    /**
     * 页面模版展示
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function exhibition($expire = 7200, $show = TRUE) {
        #执行配置文件
        $this->configs();
        #设置URL路径
        if ($_GET['app'] == 'client') {
            $url = $_GET['model'] . "/" . $_GET['action'];
        } else {
            $url = $_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'];
        }
        #设置框架路径
        $this7_frame = '<link rel="stylesheet" type="text/css" href="' . ROOT . '/vendor/this7/tags-' . C('view', 'library') . '/' . C('view', 'library') . '.css?' . time() . '">';
        $this7_frame .= '<script src="' . ROOT . '/vendor/this7/tags-' . C('view', 'library') . "/" . C('view', 'library') . ".js?" . time() . '"></script>';
        #设置文件路径
        $this->file['file_html'] = self::$config['template_path'] . "/" . $_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'] . self::$config['template_suffix']['html'];
        $this->file['file_js']   = self::$config['template_path'] . "/" . $_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'] . self::$config['template_suffix']['js'];
        $this->file['file_css']  = self::$config['template_path'] . "/" . $_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'] . self::$config['template_suffix']['css'];
        #判断是否存在
        if (!is_file($this->file['file_html'])) {
            $name = md5($this->file['file_html']);
            if (isset($_GET['key']) && $_GET['key'] == $name) {
                $tpl_html = '<!--' . $_GET['app'] . "/" . $_GET['model'] . '.html-->' .
                    '<div>' . $_GET['app'] . "/" . $_GET['model'] . '.html</div>';
                $tpl_css = '/* ' . $_GET['app'] . "/" . $_GET['model'] . '.css */';
                $tpl_js  = '//' . $_GET['app'] . "/" . $_GET['model'] . '.js' . '
Page({
     /**
      * 页面的初始数据
      */
     data: {

     },

     /**
      * 生命周期函数--监听页面显示
      */
     onShow: function() {
        console.log("生命周期函数--监听页面显示");
     }
});
';
                to_mkdir($this->file['file_html'], $tpl_html, true, true);
                to_mkdir($this->file['file_js'], $tpl_js, true, true);
                to_mkdir($this->file['file_css'], $tpl_css, true, true);
                #创建完成执行跳转

                $_GET = array();
                $this->go($url);
            }
            $url = $this->getUrl($url, "key/" . $name);
            echo "您访问的页面不存在<a href='" . $url . "'>点击此处立即创建</a>";
            exit();
        }
        #编译HTML内容
        $body_html = $this->display($this->file['file_html'], 0, false);
        #获取文件内容
        $this->assign("this7_frame", $this7_frame);
        $this->assign("this7_time", time());
        $this->assign("this7_path", ROOT . "/client");
        $this->assign("this7_html", $body_html);
        $this->assign("this7_sky", session_id());
        $this->assign("this7_js", file_get_contents($this->file['file_js']));
        $this->assign("this7_css", file_get_contents($this->file['file_css']));
        #设置编译信息
        $this->tpl     = self::$config['template_path'] . "/client/index.html";
        $this->compile = self::$config['compile_path'] . "/" . md5("index.html") . "_index.html";
        #缓存标识
        $cacheName = md5($_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action']);
        $cachePath = self::$config['cache_path'];
        #缓存有效
        if ($expire > 0 && $content = cache::dir($cachePath)->get($cacheName)) {
            if ($show) {
                die($content);
            } else {
                return $content;
            }
        }
        #编译文件
        $status = DEBUG || !file_exists($this->compile) || !is_file($this->compile) || (filemtime($this->tpl) > filemtime($this->compile));
        if ($status) {
            #执行文件编译
            $compile = new Compile($this);
            $content = $compile->run();
            #创建编译文件
            to_mkdir($this->compile, $content, true, true);
        }
        #释放变量到全局
        if (!empty(self::$vars)) {
            extract(self::$vars);
        }
        #获取解析结果
        ob_start();
        require $this->compile;
        $content = ob_get_clean();
        if ($expire > 0) {
            #缓存
            if (!cache::dir($cachePath)->set($cacheName, $content, $expire)) {
                throw new Exception("创建缓存失效");
            }
        }
        if ($show) {
            echo $content;
            exit;
        } else {
            return $content;
        }
    }

    /**
     * 解析模板
     *
     * @param string $tpl 模板
     * @param int $expire 过期时间
     * @param bool|true $show 显示或返回
     *
     * @return bool|string
     * @throws Exception
     */
    public function display($tpl = '', $expire = 0, $show = TRUE) {
        #执行配置文件
        $this->configs();
        #模板文件
        $this->tpl = $tpl;
        #缓存标识
        $cacheName = md5($this->tpl);
        $cachePath = self::$config['cache_path'];

        #缓存有效
        if ($expire > 0 && $content = cache::dir($cachePath)->get($cacheName)) {
            if ($show) {
                die($content);
            } else {
                return $content;
            }
        }
        #编译文件
        $compilePath   = self::$config['compile_path'];
        $this->compile = rtrim($compilePath, "/") . "/" . md5($this->tpl) . '_' . basename($this->tpl) . '.php';

        #编译文件
        $this->compileFile();
        #释放变量到全局
        if (!empty(self::$vars)) {
            extract(self::$vars);
        }
        #获取解析结果
        ob_start();
        require $this->compile;
        $content = ob_get_clean();
        if ($expire > 0) {
            #缓存
            if (!cache::dir($cachePath)->set($cacheName, $content, $expire)) {
                throw new Exception("创建缓存失效");
            }
        }
        if ($show) {
            echo $content;
            exit;
        } else {
            return $content;
        }
    }

    #获取显示内容
    public function fetch($tpl = '') {
        return $this->display($tpl, 0, FALSE);
    }

    /**
     * 页面直接跳转
     * @param  string  $url  跳转地址
     * @param  integer $time 停留时间
     * @param  string  $msg  提示信息
     * @return [type]        [description]
     */
    public function go($url, $time = 0, $msg = '') {
        if (is_array($url)) {
            switch (count($url)) {
            case 2:
                $url = $this->getUrl($url[0], $url[1]);
                break;
            default:
                $url = $this->getUrl($url[0]);
                break;
            }
        } else {
            $url = $this->getUrl($url);
        }
        if (!headers_sent()) {
            $time == 0 ? header("Location:" . $url) : header("refresh:{$time};url={$url}");
            exit($msg);
        } else {
            echo "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            if ($msg) {
                echo ($msg);
            }
            exit;
        }
    }

    /**
     * URL地址获取
     * @param  sting $address   需要解析的地址用/分割
     * @param  sting $parameter 需要解析的参数
     * @return url              返回路径
     */
    public function getUrl($address = NULL, $parameter = NULL) {
        if (strstr($address, "http://") || strstr($address, "https://") || strstr($address, "//")) {
            return $address;
        }
        $array = explode("/", $address);
        $count = count($array);
        $par   = array();
        $url   = null;
        switch ($count) {
        case '3':
            $root     = rtrim(ROOT, "/") . '/' . $array[0];
            $par['c'] = $array[1];
            $par['a'] = $array[2];
            break;
        case '2':
            $root     = rtrim(ROOT, "/");
            $par['c'] = $array[0];
            $par['a'] = $array[1];
            break;
        default:
        case '1':
            $root     = rtrim(ROOT, "/");
            $par['c'] = $_GET['model'];
            $par['a'] = $array[0];
            break;
        }
        #转换参数信息
        if (!empty($parameter)) {
            if (strstr($parameter, "=")) {
                $array = explode(';', $parameter);
                foreach ($array as $key => $value) {
                    $value          = explode('=', $value);
                    $par[$value[0]] = $value[1];
                }
            } elseif (strstr($parameter, "/")) {
                $array = explode('/', $parameter);
                for ($i = 0; $i < count($array); $i += 2) {
                    $par[$array[$i]] = $array[$i + 1];
                }
            } elseif (is_array($parameter)) {
                $par = $parameter;
            }
        }
        #进行参数拼接
        foreach ($par as $key => $value) {
            if ($key == 'c' || $key == 'a' || $key == 'w') {
                $url .= "/{$value}";
            } else {
                $url .= "/{$key}/{$value}";
            }
        }
        return $root . $url;
    }

    /**
     * 获取模板文件
     *
     * @param string $file 模板文件
     * @return bool|string
     * @throws Exception
     */
    public function getTemplateFile($file = NULL) {
        $template_path   = self::$config['template_path'];
        $template_suffix = self::$config['template_suffix'];
        if (is_file($file)) {
            return $file;
        }
        if (DEBUG) {
            throw new Exception("模板不存在:" . $file);
        } else {
            return FALSE;
        }
    }

    /**
     * 验证缓存文件
     *
     * @param string $tpl
     *
     * @return mixed
     * @throws \Exception
     */
    public function isCache($tpl = '') {
        #缓存标识
        $cacheName = md5($_SERVER['REQUEST_URI'] . $tpl);

        return cache::dir('Library/Caches/cache')->get($cacheName);
    }

    /**
     * 编译文件
     * @return [type] [description]
     */
    private function compileFile() {
        $status = DEBUG || !file_exists($this->compile)
        || !is_file($this->compile)
            || (filemtime($this->tpl) > filemtime($this->compile));
        if ($status) {
            #执行文件编译
            $compile = new Compile($this);
            $content = $compile->run();
            #创建编译文件
            to_mkdir($this->compile, $content, true, true);
        }
    }

    /**
     * 分配变量
     *
     * @param $name 变量名
     * @param string $value 值
     *
     * @return $this
     */
    public function assign($name, $value = '') {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                self::$vars[$k] = $v;
            }
        } else {
            self::$vars[$name] = $value;
        }
        return $this;
    }

}