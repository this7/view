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

class prestrain {
    /**
     * 视图对象
     * @var [type]
     */
    private $view;

    /**
     * 页面内容
     * @var [type]
     */
    public $html = array(
        "title"      => "这是This7框架APP应用",
        "css"        => [],
        "js"         => [],
        "script"     => "",
        "style"      => [],
        "body"       => "",
        "compontent" => []
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
        if (!is_file($this->view->tpl)) {
            $name = md5($this->view->tpl);
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
<json>
</json>
TPL;
                to_mkdir($this->view->tpl, $tpl, true, true);
                redirect($_GET['model'] . '/' . $_GET['action']);
            }
            $url = site_url($_GET['model'] . "/" . $_GET['action'], "key/" . $name);
            echo "您访问的页面不存在，<a href='" . $url . "'>点击此处立即创建</a>";
            exit();
        }
        #模板内容
        $this->content = file_get_contents($this->view->tpl);

        #解析模块
        $this->module();

        #解析标签
        $this->tags();

        #解析全局变量与常量
        $this->globalParse();

        #保存编译文件
        return $this->content;
    }

    /**
     * 获取行号
     * @Author   Sean       Yan
     * @DateTime 2018-08-03
     * @param    string     $value [description]
     */
    public function getLine() {
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
     * 解析四大模块
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     */
    public function module() {
        #执行模块编译
        $obj = new template();
        #获取主配置
        $array = get_json(ROOT_DIR . DS . 'client/app.json');
        $this->json($array, $obj);
        #编译模块
        $obj->parse($this->content, $this);
        #执行HTML合并
        $html = '<!doctype html><html lang="zh"><head><meta charset="UTF-8"><meta http-equiv="Access-Control-Allow-Origin" content="*"><title>';
        $html .= $this->html['title'];
        $html .= '</title>';
        foreach ($this->html['css'] as $key => $value) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . replace_url($value, 'file') . '?' . time() . '">';
        }
        foreach ($this->html['js'] as $key => $value) {
            $html .= '<script src="' . replace_url($value, 'file') . '?' . time() . '"></script>';
        }

        foreach ($this->html['style'] as $key => $value) {
            $html .= '<style type="text/css">' . $value . '</style>';
        }
        #设置初始化
        $html .= '<script type="text/javascript">var exports={};</script>';
        #设置内容
        $html .= '</head><body><div id="app">';
        $html .= $this->html['body'];
        $html .= '</div>';
        #输出组件内容
        foreach ($this->html['compontent'] as $key => $value) {
            $html .= '<script type="text/babel">';
            $html .= $value['script'];
            $html .= "exports.default.template=" . '"' . $value['template'] . '";';
            $html .= "Vue.component('" . $key . "',exports.default)</script>";
        }
        $html .= '<script type="text/babel">' . $this->html['script'];
        $html .= ';exports.default.el = "#app";var app = new Vue(exports.default);</script>';
        $html .= '</body></html>';
        $this->content = $html;
    }

    public function module_v1($value = '') {
        #执行模块编译
        $obj = new template();
        #获取主配置
        $array = get_json(ROOT_DIR . DS . 'client/app.json');
        $this->json($array, $obj);
        #编译模块
        $obj->parse($this->content, $this);
        #执行HTML合并
        $html = '<!doctype html><html lang="zh"><head><meta charset="UTF-8"><meta http-equiv="Access-Control-Allow-Origin" content="*"><title>';
        $html .= $this->html['title'];
        $html .= '</title>';
        foreach ($this->html['css'] as $key => $value) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . replace_url($value, 'file') . '?' . time() . '">';
        }
        foreach ($this->html['style'] as $key => $value) {
            $html .= '<style type="text/css">' . $value . '</style>';
        }
        $html .= '</head><body><div id="app">';
        $html .= $this->html['body'];
        $html .= '</div>';
        foreach ($this->html['js'] as $key => $value) {
            $html .= '<script src="' . replace_url($value, 'file') . '?' . time() . '"></script>';
        }
        $html .= '<script type="text/javascript">';
        foreach ($this->html['compontent'] as $key => $value) {
            $html .= $value['script'];
            $html .= "client.extend(" . $key . md5($key) . ', {"template":"' . $value['template'] . '"});';
            //$html .= "console.log(" . $key . md5($key) . ")";
            $html .= "Vue.component('" . $key . "'," . $key . md5($key) . ");";
        }
        $html .= $this->html['script'];
        $html .= 'var defaulted = { el: "#app" };client.extend(app, defaulted);app = new Vue(app);</script></body></html>';
    }

    /**
     * 获取主配置JSON
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function json($array, $obj) {

        #判断是否设置标题
        $this->html['title'] = isset($array['title']) ? $array['title'] : '这是This7框架APP应用';
        #获取link标签CSS列表
        $this->html['css'] = isset($array['style']) ? array_merge($array['style'], $this->html['css']) : $this->html['css'];
        #获取script标签JS列表
        $this->html['js'] = isset($array['script']) ? array_merge($array['script'], $this->html['js']) : $this->html['js'];
        #判断是否有组件
        if (isset($array['components'])) {
            foreach ($array['components'] as $key => $value) {
                $file    = ROOT_DIR . DS . "client/" . trim($value, "/") . C("view", "postfix");
                $content = file_get_contents($file);
                $obj->parse($content, $this, $key);
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
        $tags = array('\this7\view\build\labels');
        // #解析标签
        foreach ($tags as $class) {
            $obj           = new $class();
            $this->content = $obj->parse($this->content, $this->view);
        }
    }
}