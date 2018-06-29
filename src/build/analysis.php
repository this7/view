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
        $this->compile = $compilePath . "/" . md5($this->tpl) . '_' . basename($this->tpl) . '.php';
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
    public function getTemplateFile($file = NULL) {
        if (is_file($file)) {
            return $file;
        } else if (!is_file($file)) {
            if ($_GET['app'] == 'client') {
                return ROOT_DIR . DS . "client/pages/" . $_GET['model'] . "/" . $_GET['action'] . C("view", "postfix");
            } else {
                return ROOT_DIR . DS . $_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'] . C("view", "postfix");
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

    //编译文件
    private function compileFile() {
        $status = DEBUG || !file_exists($this->compile)
        || !is_file($this->compile)
            || (filemtime($this->tpl) > filemtime($this->compile));
        if ($status) {
            #执行文件编译
            $compile = new compile($this);
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