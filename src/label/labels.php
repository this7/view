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
namespace this7\view\label;

#模板解析
class labels extends basics {
    //blade模板(父级)
    private $blade = [];
    //blockshow模板(父级)
    private static $widget = [];
    /**
     * block 块标签
     * level 嵌套层次
     */
    public $tags
    = [
        'loop'   => ['block' => TRUE, 'level' => 5],
        'if'     => ['block' => TRUE, 'level' => 5],
        'elseif' => ['block' => FALSE],
        'else'   => ['block' => FALSE],
        'img'    => ['block' => FALSE],
        'a'      => ['block' => FALSE],
    ];

    //img标签地址转换
    public function _img($attr, $content, &$ubdata) {
        if (isset($attr['src'])) {
            $attr['src'] = rtrim($attr['src'], "/");
            $attr['src'] = str_replace(['./', '../'], "", $attr['src']);
            $attr['src'] = replace_url($attr['src'], 'file');
        }
        return $this->recover('img', $attr, 'block');
    }

    //a标签地址转换
    public function _a($attr, $content, &$ubdata) {
        if (isset($attr['href']) && !empty($attr['href'])) {
            $attr['href'] = replace_url($attr['href'], 'link');
        }
        return $this->recover('a', $attr, 'block');
    }

    //if标签
    public function _if($attr, $content, &$ubdata) {
        $php = "<?php if({$attr['value']}){?>$content<?php }?>";
        return $php;
    }

    //elseif标签
    public function _elseif($attr, $content, &$view) {
        return "<?php }else if({$attr['value']}){?>";
    }

    //else标签
    public function _else($attr, $content, &$view) {
        return "<?php }else{?>";
    }

    //标签处理
    public function _loop($attr, $content) {
        $empty = isset($attr['empty']) ? $attr['empty'] : "' '";
        $php   = "<?php if(empty({$attr['name']})){";
        $php .= "echo {$empty};";
        $php .= "}else{?>";
        if (isset($attr['key'])) {
            $php .= "<?php foreach ((array){$attr['name']} as {$attr['key']}=>{$attr['id']}){?>";
        } else {
            $php .= "<?php foreach ((array){$attr['name']} as {$attr['id']}){?>";
        }
        $php .= $content;
        $php .= '<?php }}?>';
        return $php;
    }
}