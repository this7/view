<?php
/**
 * this7 PHP Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2016-2018 Yan TianZeng<qinuoyun@qq.com>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://www.ub-7.com
 */
namespace this7\view\build;

use Exception;

class analysis {
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
     * 编译文件
     * @var [type]
     */
    public $compile;

    /**
     * 当前驱动
     * @var [type]
     */
    public $drive;

    /**
     * 配置信息
     * @var [type]
     */
    public static $config;

    /**
     * 解析模板
     *
     * @param string $tpl 模板
     * @param int $expire 过期时间
     * @param bool|true $show 显示或返回
     * @return bool|string
     * @throws Exception
     */
    public function display($tpl = '', $expire = 0, $show = TRUE) {
        #模板文件
        if (!$this->tpl = $this->getTemplateFile($tpl)) {
            return false;
        }
        #缓存标识
        $cacheName = md5($_SERVER['REQUEST_URI'] . $this->tpl);
        $cachePath = ROOT_DIR . DS . C("view", "template");
        #缓存有效
        if ($expire > 0 && $content = cache::get($cacheName)) {
            if ($show) {
                die($content);
            } else {
                return $content;
            }
        }

        #编译文件
        $compilePath   = C("view", "template");
        $this->compile = $this->getFileNmae($this->tpl, ".php");

        #选择显示编译
        if (C("view", "prestrain")) {
            #编译文件
            $this->compileFile();
        } else {
            $file = $this->getFileNmae($this->tpl);
            if (!file_exists($file)
                || (filemtime($file) < filemtime($this->compile))
                || (filemtime($this->tpl) > filemtime($this->compile))
            ) {
                $this->compileFile();
            } else {
                $this->compile = $file;
            }
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
            if (!cache::set($cacheName, $content, $expire)) {
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
     * 获取模板文件
     *
     * @param string $file 模板文件
     * @return bool|string
     * @throws Exception
     */
    public function getTemplateFile($file = NULL, $array = []) {
        if (empty($array)) {
            $array = $_GET;
        }
        if (is_file($file)) {
            return $file;
        } else if (!is_file($file)) {
            if ($array['app'] == 'client') {
                return ROOT_DIR . DS . "client/pages/" . $array['model'] . "/" . $array['action'] . C("view", "postfix");
            } else {
                return ROOT_DIR . DS . $array['app'] . "/" . $array['model'] . "/" . $array['action'] . C("view", "postfix");
            }
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
        //缓存标识
        $cacheName = md5($_SERVER['REQUEST_URI'] . $this->getTemplateFile($tpl));

        return cache::get($cacheName);
    }

    /**
     * 编译文件
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @return   [type]     [description]
     */
    private function compileFile() {
        $status = DEBUG || !file_exists($this->compile)
        || !is_file($this->compile)
            || (filemtime($this->tpl) > filemtime($this->compile));
        if ($status) {
            #执行文件编译
            if (C("view", "prestrain")) {
                $compile = new prestrain($this);
                $content = $compile->run();
            } else {
                $compile = new compile($this);
                $content = $compile->run();
            }
            #创建编译文件
            to_mkdir($this->compile, $content, true, true);
        }
    }

    /**
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function saveES5($value = '') {
        cache::set($_POST['keyword'], $_POST['body'], 60);
    }

    /**
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function showES5($value = '') {
        $data = to_array(decrypt($_GET['web']));
        #获取对应的Key值
        $key = 'babel' . md5('babel_this7');
        #获取列表
        $babel = to_array($data[$key]);
        #获取URL地址
        $url = array_remove($data, $key);
        #获取HTML信息
        $html = cache::get($babel['html']);
        $html .= '<script type="text/javascript">';
        foreach ($babel['compontent'] as $key => $value) {
            $script = cache::get($value);
            $html .= $script;
        }
        $html .= cache::get($babel['body']);
        $html .= '</script></body></html>';
        #设置模板信息
        $tpl     = $this->getTemplateFile('', $url);
        $compile = $this->getFileNmae($tpl, ".php");
        $file    = $this->getFileNmae($tpl);
        $url     = site_url($url);
        $status  = DEBUG || !file_exists($compile)
            || (filemtime($tpl) > filemtime($compile));
        if ($status) {
            #创建编译文件
            to_mkdir($file, $html, true, true);
        }
        redirect($url);
        exit("这是系统");
    }

    /**
     * 获取文件名
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function getFileNmae($file = '', $suffix = '') {
        $compilePath = C("view", "template");
        return $compilePath . "/" . md5($file) . '_' . basename($file) . $suffix;
    }

    /**
     * 获取行号
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     */
    public function getLine($value = '') {
        $line  = 0;
        $comma = explode(PHP_EOL, $this->content);
        preg_match('#<script(.+?)>#i', $this->content, $matches);
        foreach ($comma as $key => $value) {
            if (strval($value) == strval($matches[0])) {
                $line = $key;
            }
        }
        return $line + 2;
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