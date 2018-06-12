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

class compile {
    /**
     * 视图对象
     * @var [type]
     */
    private $view;

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
    public function run($template) {
        #模板内容
        $this->content = $template;

        #解析标签
        $this->tags();

        #解析全局变量与常量
        $this->globalParse();

        #保存编译文件
        return $this->content;
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
        $obj           = new labels();
        $this->content = $obj->parse($this->content, $this->view);
    }
}