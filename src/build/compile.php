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
use \Exception;
use \this7\view\label\template;

class compile {
    /**
     * 视图对象
     * @var [type]
     */
    private $view;

    /**
     * 编译内容
     * @var [type]
     */
    public $body;

    /**
     * 页面内容
     * @var [type]
     */
    public $html = array(
        "title"      => "这是This7框架APP应用",
        "is_title"   => 1,
        "css"        => [],
        "js"         => [],
        "script"     => "",
        "style"      => [],
        "body"       => "",
        "compontent" => [],
        "routeView"  => []
    );

    /**
     * 模板编译内容
     * @var [type]
     */
    private $content;

    /**
     * 构造函数
     * @param [type] &$view [description]
     */
    public function __construct(&$view) {
        $this->view = $view;
    }

    /**
     * 运行编译
     * @return string
     */
    public function run() {
        extract($this->view->appTpl);
        if (!is_file($routeTpl)) {
            $name = md5($routeTpl);
            if (isset($_GET['key']) && $_GET['key'] == $name) {
                $tpl = <<<TPL
<template>
    <div class="this7">
        欢迎使用This7框架
    </div>
</template>
<style type="text/css">

</style>
<script type="text/javascript">
export default {
    data: {

    },
    method: {

    },
    mounted: function() {

    }
}
</script>
<script type="text/json">
</script>
TPL;
                to_mkdir($routeTpl, $tpl, true, true);
                redirect($_GET['model'] . '/' . $_GET['action']);
            }
            $url = site_url($_GET['model'] . "/" . $_GET['action'], "key/" . $name);
            echo "您访问的页面不存在，<a href='" . $url . "'>点击此处立即创建</a>";
            exit();
        }
        #模板内容
        $this->content = file_get_contents($appTpl);

        #解析标签
        $this->tags();

        #解析模块
        $this->module();

        #解析全局变量与常量
        $this->globalParse();

        #保存编译文件
        return $this->content;
    }

    /**
     * 解析四大模块
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     */
    public function module() {
        extract($this->view->appTpl);
        #页面唯一编号
        $unique = md5($routeTpl) . "_";
        #执行模块编译
        $obj = new template();
        #组件存储
        $arr = [];
        #解析组件
        $config = $this->view->config;
        $this->json($config, $obj);
        #标题设置
        $this->html['title']    = isset($config['title']) ? $config['title'] : '这是This7框架APP应用';
        $this->html['is_title'] = isset($config['title']) ? $config['title'] : '这是This7框架APP应用';
        #编译模块
        $obj->parse($this->content, $this);
        $html = [];
        $i    = 0;
        #执行HTML合并
        $html['html']  = '<!doctype html><html lang="zh"><head><meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes"/> <meta charset="UTF-8"><meta http-equiv="Access-Control-Allow-Origin" content="*"><title>';
        $html['title'] = $this->html['title'] . '</title>';
        #前置代码
        $html['precode'] = '<?php echo $precode;?>';
        #输出CSS代码
        foreach ($this->html['css'] as $key => $value) {
            $html['css' . $key] = '<link rel="stylesheet" type="text/css" href="' . replace_url($value, 'file') . '?' . time() . '">';
        }
        $html['this7'] = '<script src="' . ROOT . "/vendor/this7/view/src/bin/this7.js" . '?' . time() . '"></script>';
        #系统JS-编译模式
        if (!C("view", "prestrain")) {
            $html['babel'] = '<script src="' . ROOT . "/vendor/this7/view/src/bin/babel.js" . '?' . time() . '"></script>';
        }
        #系统JS-预编译模式
        else {
            $html['babel'] = '<script src="' . ROOT . "/vendor/this7/view/src/bin/babel.mini.js" . '?' . time() . '"></script>';
        }
        #加载JS文件
        foreach ($this->html['js'] as $key => $value) {
            $html['js' . $key] = '<script src="' . replace_url($value, 'file') . '?' . time() . '"></script>';
        }
        #加载CSS文件
        foreach ($this->html['style'] as $key => $value) {
            $html['style' . $key] = '<style type="text/css">' . $value . '</style>';
        }
        #后置代码
        $html['rearcode'] = '<?php echo $rearcode;?>';
        #设置初始化
        $html['script' . $i++] = '<script type="text/javascript">var exports={}; var routerView=[];</script>';
        #设置内容
        $html['script' . $i++] = '</head><body>';
        if (isset($config['route']) && $config['route']) {
            $html['script' . $i++] = $this->html['body'];
        } else {
            $html['script' . $i++] = '<div id="app">';
            $html['script' . $i++] = $this->html['body'];
            $html['script' . $i++] = '</div>';
        }
        $html_i = $html;
        $html_i = array_remove($html_i, 'jquery');
        $html_i = array_remove($html_i, 'babel');
        #编译模式下存储
        if (!C("view", "prestrain")) {
            cache::set($unique . 'html', implode(" ", $html_i), 80);
        }
        #输出组件内容
        foreach ($this->html['compontent'] as $key => $value) {
            $html['script' . $i++] = '<script type="text/babel" id="' . $unique . $key . '">';
            $html['script' . $i++] = $value['script'];
            $html['script' . $i++] = "exports.default.template=" . '"' . $value['template'] . '";';
            $html['script' . $i++] = "Vue.component('" . $key . "',exports.default);</script>";
            $arr['compontent'][]   = $unique . $key;
        }
        #输出视图内容
        foreach ($this->html['routeView'] as $key => $value) {
            $html['script' . $i++] = '<script type="text/babel" id="' . $unique . $key . '">';
            $html['script' . $i++] = $value['script'];
            $html['script' . $i++] = "routerView['{$key}'].template=" . '"' . $value['template'] . '";';
            $html['script' . $i++] = "</script>";
            $arr['routeView'][]    = $unique . $key;
        }
        #设置存储名
        $arr['body'] = $unique . 'body';
        $arr['html'] = $unique . 'html';

        #设置GET唯一键
        $_GET['babel' . md5('babel_this7')] = to_json($arr);

        #编译JS代码
        $html['script' . $i++] = '<script type="text/babel" id="' . $unique . 'body">';

        #单例模式
        if (isset($config['single']) && $config['single'] && is_array($config['route']) && !empty($config['route'])) {
            $html['script' . $i++] = 'var getrouter = $_GET["app"] + "_" + $_GET["model"];';
            $html['script' . $i++] = 'getrouter = (getrouter=="_undefined")?"home_home":getrouter;';
            $html['script' . $i++] = 'console.log(getrouter);';
            $html['script' . $i++] = 'Vue.component("router-view", routerView[getrouter]);';
        }
        #组合JS文件
        $html['script' . $i++] = $this->html['script'];
        $html['script' . $i++] = ';exports.default.el = "#app";var app = new Vue(exports.default);</script>';
        $html['script' . $i++] = '<script type="text/javascript">';

        #编译模式下存储
        if (!C("view", "prestrain")) {
            cache::set($unique, to_json($_GET), 80);
        }

        if (!C("view", "prestrain")) {
            #设置跳转链接
            $url = site_url('system/view/showES5', array("web" => $unique));
            #编译模式
            $html['script' . $i++] = 'window.location.href= "' . $url . '";';
        }
        $html['script' . $i++] = '</script>';
        $html['script' . $i++] = '</body></html>';
        $this->content         = implode(" ", $html);
    }

    /**
     * 获取主配置JSON
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     * @param    array      $array 配置数据
     * @param    object     $obj   父级对象
     * @param    string     $type  [description]
     * @return   [type]            [description]
     */
    public function json($array, $obj, $type = 'mian') {
        #判断是否设置标题
        $this->html['title'] = isset($array['title']) ? $array['title'] : $this->html['is_title'];
        #获取link标签CSS列表
        $this->html['css'] = isset($array['style']) ? array_merge($array['style'], $this->html['css']) : $this->html['css'];
        #获取script标签JS列表
        $this->html['js'] = isset($array['script']) ? array_merge($array['script'], $this->html['js']) : $this->html['js'];
        #判断是否为单例模式
        if (isset($array['single']) && is_array($array['route'])) {
            foreach ($array['route'] as $key => $value) {
                $file = $this->view->getTemplateFile($value['component'], true);
                if (!file_exists($file)) {
                    throw new Exception("组件文件不存在:" . $file);
                }
                $content = file_get_contents($file);
                $key     = str_replace("/", "_", $value['path']);
                $type    = array("name" => "routeView", "value" => $key);
                $obj->parse($content, $this, $type);
            }
        }
        #判断是否有组件
        if (isset($array['components'])) {
            foreach ($array['components'] as $key => $value) {
                $file = $this->view->getTemplateFile($value, true);
                $path = dirname($file);
                if (!file_exists($file)) {
                    throw new Exception("组件文件不存在:" . $file);
                }
                $content = file_get_contents($file);
                $type    = array("name" => "compontent", "value" => $key);
                $obj->parse($content, $this, $type, $path);
            }
        }
    }

    /**
     * 解析全局变量与常量
     */
    private function globalParse() {
        #处理{ub:}
        $this->content = preg_replace('/(?<!@)\{ub\:(.*?)\}/i', '<?php echo \1;?>', $this->content);
        #处理@{ub:}
        $this->content = preg_replace('/@\{ub\:(.*?)\}/i', '\1;', $this->content);
    }

    /**
     * 解析标签
     */
    public function tags() {
        #标签库
        $tags = array('\this7\view\label\labels');
        // #解析标签
        foreach ($tags as $class) {
            $obj           = new $class();
            $this->content = $obj->parse($this->content, $this->view);
        }
    }

}