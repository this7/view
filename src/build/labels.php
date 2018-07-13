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
        'img' => ['block' => FALSE],
        'a'   => ['block' => TRUE, 'level' => 5],
    ];

    //img标签地址转换
    public function _img($attr, $content, &$ubdata) {
        if (isset($attr['src'])) {
            $attr['src'] = replace_url($attr['src'], 'file');
        }
        return $this->recover('img', $attr, 'block');
    }

    //a标签地址转换
    public function _a($attr, $content, &$ubdata) {
        if (isset($attr['helf'])) {
            $attr['helf'] = replace_url($attr['helf'], 'file');
        }
        return $this->recover('a', $attr, 'block');
    }
}