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
     * 一维码
     * @var [type]
     */
    public $unique;

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

        $this->unique = md5($page);

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
            if (!file_exists($routeTpl) && is_file($compileTpl)) {
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
        debug::display(["model" => "page"]);
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
        #设置模板信息
        $tpl     = $this->getTemplateFile('', $url);
        $compile = $this->getFileNmae($tpl, ".php");
        $file    = $this->getTemplate($tpl, ".php");
        $url     = site_url($url);
        #压缩JS
        require_once dirname(dirname(__FILE__)) . "/bin/jsmin.php";

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
        #判断是否编译生成
        $status = DEBUG || !file_exists($compile)
            || (filemtime($tpl) > filemtime($compile));
        if ($status) {
            #调试模式关闭时
            if (!DEBUG) {
                $html = preg_replace('#console\.log\(.*?\)\;#i', '', $html);
            }
            #查找替换域名
            $html = preg_replace('#' . ROOT . '#i', '', $html);
            #创建编译文件
            to_mkdir($file, $html, true, true);
        }
        redirect($url);
        exit("这是系统");
    }

    public function addressToUrl($url = '') {
        $path   = ROOT_DIR . DS . "client/pages";
        $length = strlen($path);
        return trim(str_replace(C("view", "postfix"), '', substr($url, $length)), "/");
    }

    /**
     * 一键读取生成
     * @Author   Sean       Yan
     * @DateTime 2018-08-29
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function onekey() {
        $path   = ROOT_DIR . DS . "client/pages";
        $files  = get_dir(ROOT_DIR . DS . "client/pages");
        $pages  = array();
        $length = strlen($path);
        foreach ($files as $key => $value) {
            $page        = trim(str_replace(C("view", "postfix"), '', substr($value, $length)), "/");
            $pages[$key] = site_url($page);

        }
        $total = count($pages);
        $pages = to_json($pages);
        $html  = <<<HT
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>一键编译</title>
    <style>
    html,
    body,
    #box {
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    body {

        background: #222 url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAMAAAC67D+PAAAAFVBMVEUqKiopKSkoKCgjIyMuLi4kJCQtLS0dJckpAAAAO0lEQVR42iXLAQoAUQhCQSvr/kfe910jHIikElsl5qVFa1iE5f0Pom/CNZdbNM6756lQ41NInMfuFPgAHVEAlGk4lvIAAAAASUVORK5CYII=");

        font: 13px 'trebuchet MS', Arial, Helvetica;
    }



    h2,
    p {

        text-align: center;

        color: #fafafa;

        text-shadow: 0 1px 0 #111;
    }



    a {

        color: #777;
    }

    .button {
        display: inline-block;
        outline: none;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        font: 16px/100% 'Microsoft yahei', Arial, Helvetica, sans-serif;
        padding: .5em 2em .55em;
        text-shadow: 0 1px 1px rgba(0, 0, 0, .3);
        -webkit-border-radius: .5em;
        -moz-border-radius: .5em;
        border-radius: .5em;
        -webkit-box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
        -moz-box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
    }

    .button:hover {
        text-decoration: none;
    }

    .button:active {
        position: relative;
        top: 1px;
    }

    .bigrounded {
        -webkit-border-radius: 2em;
        -moz-border-radius: 2em;
        border-radius: 2em;
    }

    .medium {
        font-size: 12px;
        padding: .4em 1.5em .42em;
    }

    .small {
        font-size: 11px;
        padding: .2em 1em .275em;
    }

    .orange {
        color: #fef4e9;
        border: solid 1px #da7c0c;
        background: #f78d1d;
        background: -webkit-gradient(linear, left top, left bottom, from(#faa51a), to(#f47a20));
        background: -moz-linear-gradient(top, #faa51a, #f47a20);
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#faa51a', endColorstr='#f47a20');
    }

    .orange:hover {
        background: #f47c20;
        background: -webkit-gradient(linear, left top, left bottom, from(#f88e11), to(#f06015));
        background: -moz-linear-gradient(top, #f88e11, #f06015);
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#f88e11', endColorstr='#f06015');
    }

    .orange:active {
        color: #fcd3a5;
        background: -webkit-gradient(linear, left top, left bottom, from(#f47a20), to(#faa51a));
        background: -moz-linear-gradient(top, #f47a20, #faa51a);
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#f47a20', endColorstr='#faa51a');
    }
    /*---------------------------*/

    .progress-bar {

        background-color: #1a1a1a;

        height: 25px;

        padding: 5px;

        width: 100%;

        margin: 70px 0 20px 0;

        -moz-border-radius: 5px;

        -webkit-border-radius: 5px;

        border-radius: 5px;

        -moz-box-shadow: 0 1px 5px #000 inset, 0 1px 0 #444;

        -webkit-box-shadow: 0 1px 5px #000 inset, 0 1px 0 #444;

        box-shadow: 0 1px 5px #000 inset, 0 1px 0 #444;
    }



    .progress-bar span {

        display: inline-block;

        height: 100%;

        background-color: #777;

        -moz-border-radius: 3px;

        -webkit-border-radius: 3px;

        border-radius: 3px;

        -moz-box-shadow: 0 1px 0 rgba(255, 255, 255, .5) inset;

        -webkit-box-shadow: 0 1px 0 rgba(255, 255, 255, .5) inset;

        box-shadow: 0 1px 0 rgba(255, 255, 255, .5) inset;

        -webkit-transition: width .4s ease-in-out;

        -moz-transition: width .4s ease-in-out;

        -ms-transition: width .4s ease-in-out;

        -o-transition: width .4s ease-in-out;

        transition: width .4s ease-in-out;
    }
    /*---------------------------*/

    .blue span {

        background-color: #34c2e3;
    }



    .orange span {

        background-color: #fecf23;

        background-image: -webkit-gradient(linear, left top, left bottom, from(#fecf23), to(#fd9215));

        background-image: -webkit-linear-gradient(top, #fecf23, #fd9215);

        background-image: -moz-linear-gradient(top, #fecf23, #fd9215);

        background-image: -ms-linear-gradient(top, #fecf23, #fd9215);

        background-image: -o-linear-gradient(top, #fecf23, #fd9215);

        background-image: linear-gradient(top, #fecf23, #fd9215);
    }



    .green span {

        background-color: #a5df41;

        background-image: -webkit-gradient(linear, left top, left bottom, from(#a5df41), to(#4ca916));

        background-image: -webkit-linear-gradient(top, #a5df41, #4ca916);

        background-image: -moz-linear-gradient(top, #a5df41, #4ca916);

        background-image: -ms-linear-gradient(top, #a5df41, #4ca916);

        background-image: -o-linear-gradient(top, #a5df41, #4ca916);

        background-image: linear-gradient(top, #a5df41, #4ca916);
    }

    .shine span {

        position: relative;
    }



    .shine span::after {

        content: '';

        opacity: 0;

        position: absolute;

        top: 0;

        right: 0;

        bottom: 0;

        left: 0;

        background: #fff;

        -moz-border-radius: 3px;

        -webkit-border-radius: 3px;

        border-radius: 3px;



        -webkit-animation: animate-shine 2s ease-out infinite;

        -moz-animation: animate-shine 2s ease-out infinite;
    }



    @-webkit-keyframes animate-shine {

        0% {
            opacity: 0;
            width: 0;
        }

        50% {
            opacity: .5;
        }

        100% {
            opacity: 0;
            width: 95%;
        }
    }

    @-moz-keyframes animate-shine {

        0% {
            opacity: 0;
            width: 0;
        }

        50% {
            opacity: .5;
        }

        100% {
            opacity: 0;
            width: 95%;
        }
    }

    * {
        margin: 0;
        padding: 0;
    }

    #box {
        margin: 20px 10px;
        position: absolute;
        z-index: 2;
        width: 100%;
        height: 100%;
    }

    iframe {
        position: absolute;
        width: 100%;
        height: 100%;
        z-index: 13;
        left: -800%;
        top: -800%;
    }

    .compile {
        text-align: center;
        margin: 20px 10px;
    }
    </style>
</head>

<body>
    <iframe src=""></iframe>
    <div id="box">
        <h2>一键编译文件</h2>
        <div style="width:40%; margin:0 auto">
            <div class="progress-bar blue shine">
                <span id="schedule" style="width: 0%"></span>
            </div>
            <div class="compile">
                <button id="compile" class="button orange" onclick="clickHandler()">一键编译</button>
            </div>
            <p>正在编译文件:<span id="on_num">0</span>/<span id="con_num">0</span></p>
            <p id="info"></p>
        </div>
    </div>
    <script>
    var i = 0;
    var t = $total;
    var b = 100 / t;
    var c = 0;
    var pages = $pages;
    var iframe = document.createElement("iframe");
    var styleElement = document.getElementById('schedule');

    document.getElementById("con_num").innerHTML = t;
    document.body.appendChild(iframe);

    function isload(pages) {
        var url = pages[i];

        if (!url) {
            document.getElementById("compile").disabled = false;
            document.getElementById("compile").innerHTML = "编译完成";
            styleElement.setAttribute('style', 'width: 100%');
            return;
        }
        console.log(url);
        i++;
        c = c + b;
        document.getElementById("on_num").innerHTML = i;
        styleElement.setAttribute('style', 'width: ' + c + '%');
        iframe.src = url;
        if (iframe.attachEvent) {
            iframe.attachEvent("onload", function() {
                console.log(1);
                isload(pages);
            });
        } else {
            iframe.onload = function() {
                console.log(2);
                isload(pages);
            };
        }
    }

    function clickHandler() {
        document.getElementById("compile").disabled = true;
        document.getElementById("compile").innerHTML = "编译中……";
        isload(pages);
    }
    </script>
</body>

</html>
HT;
        echo $html;
        exit("");
    }

}