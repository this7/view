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
namespace this7\view\module;
use \this7\view\build\compile;

class vue {
    /**
     * 模板变量集合
     * @var array
     */
    protected static $vars = array();

    /**
     * 模版文件
     * @var [type]
     */
    public $appTpl = [];

    /**
     * 配置信息
     * @var [type]
     */
    public $config = [];

    /**
     * 编译文件
     * @var [type]
     */
    public $compile;

    /**
     * 初始化接口
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     */
    public function __construct() {
        #获取数据页面
        $this->config = get_json(ROOT_DIR . DS . 'client/app.json');

        #获取路由页面
        $this->appTpl['routeTpl'] = $this->getTemplateFile();

        #当前页面
        $page = $_GET['model'] . "/" . $_GET['action'];

        #启动独立路由模式 且排除非路由项
        if (isset($this->config['route']) && $this->config['route'] && !$this->config['single'] && !in_array($page, $this->config['excludeRoute'])) {
            $this->appTpl['appTpl']                    = ROOT_DIR . DS . "client/app.html";
            $this->config['components']['router-view'] = $this->appTpl['routeTpl'];
        }
        #启动单例模式 且路由列表不为空
        elseif (isset($this->config['single']) && $this->config['single'] && is_array($this->config['route']) && !empty($this->config['route'])) {
            $this->appTpl['appTpl']   = ROOT_DIR . DS . "client/app.html";
            $this->appTpl['routeTpl'] = $this->appTpl['appTpl'];
        }
        #启动独立模式
        else {
            $this->appTpl['appTpl'] = $this->appTpl['routeTpl'];
        }

        #设置预编译和编译路径
        $this->appTpl['precompileTpl'] = $this->getFileNmae($this->appTpl['routeTpl'], ".php");
        $this->appTpl['compileTpl']    = $this->getTemplate($this->appTpl['routeTpl'], ".php");

        #设置前置后置代码
        $this->assign("precode", $this->preCode());
        $this->assign("rearcode", $this->rearCode());
    }

    /**
     * Vue视图显示
     * @Author   Sean       Yan
     * @DateTime 2018-07-30
     * @return   [type]     [description]
     */
    public function display() {
        extract($this->appTpl);

        #判断模版文件-在为空的情况下生产环境返回False
        if (!$appTpl) {
            return false;
        }
        $this->compile = $precompileTpl;

        #显示模式 预编译+编译模式
        if (C("view", "prestrain")) {
            $this->compileFile();
        } else {
            #开发页面删除并且生成页面存在时，直接调用生产页面
            if (!file_exists($routeTpl) && file_exists($compileTpl)) {
                $this->compile = $compileTpl;
            } else {
                if ($this->is_update($compileTpl)) {
                    $this->compileFile();
                } else {
                    $this->compile = $compileTpl;
                }
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
        echo $content;
        exit;
    }

    /**
     * 判断是否更新
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     * @param    string     $file  检查文件
     * @return   boolean           返回检查值
     */
    public function is_update($file = null) {
        extract($this->appTpl);

        $status = DEBUG
        || !file_exists($precompileTpl)
            || (@filemtime($appTpl) > @filemtime($precompileTpl))
            || (@filemtime($routeTpl) > @filemtime($precompileTpl));

        if (!empty($file)) {
            $status = !file_exists($file)
                || (@filemtime($appTpl) > @filemtime($file))
                || (@filemtime($routeTpl) > @filemtime($file));
        }

        return $status;
    }

    /**
     * 编译文件
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @return   [type]     [description]
     */
    private function compileFile() {
        #执行编译
        if ($this->is_update()) {
            $compile = new compile($this);
            $content = $compile->run();
        }
        #创建编译文件
        to_mkdir($this->compile, $content, true, true);
    }

    /**
     * 获取模板文件
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     * @param    string            $file  如果为空或不是文件，将自动GET获取
     * @param    array|bool        $array 为空则自动GET获取，为布尔值，将根据第一个字段返回完整路径
     * @return   string            获取文件路径
     */
    public function getTemplateFile($file = NULL, $array = []) {
        if (empty($array)) {
            $array = $_GET;
        }
        if (is_file($file)) {
            return $file;
        } else if (!is_file($file)) {
            if (!is_array($array)) {
                return ROOT_DIR . DS . "client" . DS . $file . C("view", "postfix");
            }
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
     * 获取文件名
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function getFileNmae($file = '', $suffix = '') {
        $compilePath = C("view", "cache");
        return ROOT_DIR . DS . $compilePath . "/" . md5($file) . '_' . basename($file, C("view", "postfix")) . $suffix;
    }

    /**
     * 获取模板文件
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function getTemplate($file = '', $suffix = '') {
        $compilePath = C("view", "template");
        return ROOT_DIR . DS . $compilePath . "/" . md5($file) . $suffix;
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

    /**
     * 前置代码
     * @Author   Sean       Yan
     * @DateTime 2018-08-09
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function preCode() {
        $codeList = C("view", 'precode');
        $html     = '';
        foreach ($codeList as $key => $value) {
            $html .= $value();
        }
        return $html;
    }

    /**
     * 后置代码
     * @Author   Sean       Yan
     * @DateTime 2018-08-09
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function rearCode($value = '') {
        $codeList = C("view", 'rearcode');
        $html     = '';
        foreach ($codeList as $key => $value) {
            $html .= $value();
        }
        return $html;
    }

    /**
     * 编译存储
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     */
    public function saveES5() {
        cache::set($_POST['keyword'], $_POST['body'], 60);
    }

    /**
     * 编译解析
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     */
    public function showES5() {
        $data = to_array(cache::get($_GET['web']));
        #获取对应的Key值
        $key = 'babel' . md5('babel_this7');
        #获取列表
        $babel = to_array($data[$key]);
        #获取URL地址
        $url = array_remove($data, $key);
        #获取HTML信息
        $html = cache::get($babel['html']);
        #压缩JS
        require_once dirname(dirname(__FILE__)) . "/bin/jsmin.php";
        //$jsmin      = new \JSMin($this->html['script']);
        //         $script_min = $jsmin->min();
        // P($script_min);
        // \JSMin::minify($script);

        $html .= '<script type="text/javascript">';
        foreach ($babel['compontent'] as $key => $value) {
            $script = cache::get($value);
            $html .= \JSMin::minify($script);
        }
        foreach ($babel['routeView'] as $key => $value) {
            $script = cache::get($value);
            $html .= \JSMin::minify($script);
        }
        $html .= \JSMin::minify(cache::get($babel['body']));
        $html .= '</script></body></html>';
        #设置模板信息
        $tpl     = $this->getTemplateFile('', $url);
        $compile = $this->getFileNmae($tpl, ".php");
        $file    = $this->getTemplate($tpl, ".php");
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

}