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

class compile {
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
            throw new Exception('模版文件(' . $this->view->tpl . ')不存在', -2);
            die();
        }
        #模板内容
        $this->content = file_get_contents($this->view->tpl);

        #解析模块
        $this->module();

        #解析标签
        //$this->tags();

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
        $this->content = $html;
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