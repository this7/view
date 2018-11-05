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
use \this7\view\label\template;

class compile {
    /**
     * 视图对象
     * @var [type]
     */
    private $vue;

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
        "js"         => [
            ROOT . "/vendor/this7/view/src/bin/this7.js",
            ROOT . "/vendor/this7/view/src/bin/babel.js",
        ],
        "script"     => "",
        "baseCss"    => [],
        "style"      => [],
        "header"     => [],
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
    public function __construct(&$vue) {
        $this->vue = $vue;
    }

    /**
     * 运行编译
     * @return string
     */
    public function bootstrap($page, $config) {
        #标题设置
        $this->html['title']    = isset($config['title']) ? $config['title'] : '这是This7框架APP应用';
        $this->html['is_title'] = isset($config['title']) ? $config['title'] : '这是This7框架APP应用';
        #解析模块
        $this->module($page, $config);

        #循环分配元素
        foreach ($this->html as $key => $value) {
            $this->vue->assign($key, $value);
        }
        #选择编译模式
        if (C("view", "prestrain")) {
            #获取内容
            $this->vue->appTpl['precompileTpl'] = dirname(dirname(__FILE__)) . "/bin/precompile.php";
        } else {
            #获取内容
            $this->vue->appTpl['precompileTpl'] = dirname(dirname(__FILE__)) . "/bin/compile.php";
        }
    }

    /**
     * 解析四大模块
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     */
    public function module($page, $config) {
        #执行模块编译
        $obj = new template();
        #判断是否设置标题
        $this->html['title'] = isset($config['title']) ? $config['title'] : $this->html['is_title'];
        #获取link标签CSS列表
        $this->html['css'] = isset($config['style']) ? array_merge($this->html['css'], $config['style']) : $this->html['css'];
        #获取script标签JS列表
        $this->html['js'] = isset($config['script']) ? array_merge($this->html['js'], $config['script']) : $this->html['js'];

        #去重复化
        $this->html['css'] = array_unique($this->html['css']);
        $this->html['js']  = array_unique($this->html['js']);

        #解析组件模块
        foreach ($config['components'] as $key => $value) {
            #页面唯一编号
            $unique = md5($key);
            #获取页面文件
            $file = $this->vue->getTemplateFile($value, true);
            #获取所在目录
            $path = dirname($file);
            #获取文件内容
            $content = file_get_contents($file);
            #设置页面类型
            $info = array(
                "name"   => $key,
                "unique" => $unique,
                "page"   => $file,
                "path"   => $path,
                "type"   => "component",
            );
            #执行模块编译
            $obj->parse($content, $this, $info);
        }
        if (isset($config['extends']) == false) {
            return;
        }
        #解析扩展模块
        foreach ($config['extends'] as $key => $value) {
            #页面唯一编号
            $unique = md5($key);
            #获取页面文件
            $file = $this->vue->getTemplateFile($value, true);
            #获取所在目录
            $path = dirname($file);
            #获取文件内容
            $content = file_get_contents($file);
            #设置页面类型
            $info = array(
                "name"   => $key,
                "unique" => $unique,
                "page"   => $file,
                "path"   => $path,
                "type"   => "extend",
            );
            #执行模块编译
            $obj->parse($content, $this, $info);
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
        #解析标签
        foreach ($tags as $class) {
            $obj           = new $class();
            $this->content = $obj->parse($this->content, $this->vue);
        }
    }

}