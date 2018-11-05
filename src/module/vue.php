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
use Exception;
use this7\framework\ErrorCode;
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
     * 一维码
     * @var [type]
     */
    public $unique;

    /**
     * 页面
     * @var [type]
     */
    public $page;

    /**
     * 初始化接口
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     */
    public function bootstrap($page = '') {
        #获取数据页面
        $this->config = get_json(ROOT_DIR . DS . 'client/app.json');

        #当前页面
        $this->page = empty($page) ? $_GET['model'] . "/" . $_GET['action'] : $page;

        $this->appTpl['pageTpl'] = $this->getTemplateFile();

        $this->unique = md5($this->page);

        #启动独立路由模式 且排除非路由项
        if (isset($this->config['route']) && $this->config['route'] && !in_array($this->page, $this->config['excludeRoute'])) {
            $this->config['components']['app']         = ROOT_DIR . DS . "client/app.html";
            $this->config['components']['router-view'] = $this->appTpl['pageTpl'];
        }
        #启动独立模式
        else {
            $this->config['components']['app'] = $this->appTpl['pageTpl'];
        }
        #设置预编译和编译路径
        $this->appTpl['precompileTpl'] = $this->getFileNmae($this->appTpl['pageTpl'], ".php");
        $this->appTpl['compileTpl']    = $this->getTemplate($this->appTpl['pageTpl'], ".php");
        foreach ($this->appTpl as $key => $value) {
            $this->assign($key, $value);
        }

        #设置页面代码
        $this->assign("page", $this->page);
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
        #启动引导接口
        $this->bootstrap();

        #判断模版文件-在为空的情况下生产环境返回False
        if (!$this->appTpl['pageTpl']) {
            return false;
        }
        #选择编译模式
        if (C("view", "prestrain") || !is_file($this->appTpl['compileTpl'])) {
            $this->compileFile();
            $this->compile = $this->appTpl['precompileTpl'];
        } else {
            $this->compile = $this->appTpl['compileTpl'];
        }

        #释放变量到全局
        if (!empty(self::$vars)) {
            extract(self::$vars);
        }
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
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
            $content = $compile->bootstrap($this->appTpl['pageTpl'], $this->config);
        }
    }

    /**
     * 页面存储
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function pagestorage() {
        $data = base64_decode($_POST['code']);
        $data = to_array($data);
        extract($data);
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        #获取解析结果
        ob_start();
        require_once dirname(dirname(__FILE__)) . "/bin/template.php";
        $content = ob_get_clean();
        #创建编译文件
        to_mkdir($compileTpl, $content, true, true);
        redirect($page);
        exit;
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
        try {
            if (empty($array)) {
                $array = $_GET;
            }
            if (is_file($file)) {
                return $file;
            } elseif (!is_array($array)) {
                $file = ROOT_DIR . DS . "client" . DS . $file . C("view", "postfix");
            } else {
                if ($array['app'] == 'client') {
                    $file = ROOT_DIR . DS . "client/pages/" . $array['model'] . "/" . $array['action'] . C("view", "postfix");
                } else {
                    $file = ROOT_DIR . DS . $array['app'] . "/" . $array['model'] . "/" . $array['action'] . C("view", "postfix");
                }
            }
            if (!is_file($file)) {
                throw new Exception("模板文件不存在", ErrorCode::$FileDoesNotExist);
            }
            return $file;
        } catch (Exception $e) {
            ERRORCODE($e);
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
        return ROOT_DIR . DS . $compilePath . "/" . md5($this->addressToUrl($file)) . '_' . basename($file, C("view", "postfix")) . $suffix;
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
        return ROOT_DIR . DS . $compilePath . "/" . md5($this->addressToUrl($file)) . $suffix;
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
     * 地址转URL
     * @param  string $url [description]
     * @return [type]      [description]
     */
    public function addressToUrl($url = '') {
        $path   = ROOT_DIR . DS . "client/pages";
        $length = strlen($path);
        return trim(str_replace(C("view", "postfix"), '', substr($url, $length)), "/");
    }

}
