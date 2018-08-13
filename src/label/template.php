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
class template extends basics {
    /**
     * blade模板(父级)
     * @var array
     */
    private $blade = [];

    /**
     * less对象
     * @var [type]
     */
    private $less;

    /**
     * scss对象
     * @var [type]
     */
    private $scss;

    /**
     * blockshow模板(父级)
     * @var array
     */
    private static $widget = [];

    /**
     * block 块标签
     * level 嵌套层次
     */
    public $tags
    = [
        'template' => ['block' => TRUE, 'level' => 5],
        'script'   => ['block' => TRUE, 'level' => 5],
        'style'    => ['block' => TRUE, 'level' => 5],
        'json'     => ['block' => TRUE, 'level' => 5],
    ];

    /**
     * 获取模板信息
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _template($attr, $content, &$ubdata) {
        if (!$content) {
            return;
        }
        if ($this->compontent) {
            #设置KEY
            $key = $this->compontent;
            #解析组件标签
            $content = $this->tags($content);
            #存储组件
            $this->view->html['compontent'][$key]['template'] = str_replace('"', '\"', compress_html($content));
        } else {
            $this->view->html['body'] = compress_html($content);
        }

    }

    /**
     * 获取JS脚本
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _script($attr, $content, &$ubdata) {
        if (isset($attr['type']) && $attr['type'] == 'text/json') {
            $this->_json($attr, $content, $ubdata);
            return;
        }
        if (!$content) {
            return;
        }
        #设置KEY
        $key = $this->compontent;
        if ($key) {
            $this->view->html['compontent'][$key]['script'] = $content;
        } else {
            $this->view->html['script'] = $content;
        }
    }

    /**
     * 获取样式信息
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _style($attr, $content, &$ubdata) {
        if (!$content) {
            return;
        }
        if ($this->compontent && !empty($this->path)) {
            $file = $this->path . DS . "base.css";
            if (file_exists($file)) {
                $base    = file_get_contents($file);
                $content = $base . $content;
            }
        }
        if (isset($attr['lang'])) {
            switch (strtolower($attr['lang'])) {
            case 'less':
                require_once dirname(dirname(__FILE__)) . "/bin/lessc.inc.php";
                if (!$this->less) {
                    $this->less = new \lessc();
                }
                $content = $this->less->compile($content);
                break;
            case 'scss':
                require_once dirname(dirname(__FILE__)) . "/bin/scss.inc.php";
                if (!$this->scss) {
                    $this->scss = new \scssc();
                }
                $content = $this->scss->compile($content);
                break;
            }
        }
        $this->view->html['style'][] = compress_css($content);
    }

    /**
     * 获取JSON数据
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _json($attr, $content, &$ubdata) {
        if (!$content) {
            return;
        }
        $array = to_array($content);
        $this->view->json($array, $this);
    }

    /**
     * 解析标签
     */
    public function tags($content) {
        #标签库
        $tags = array('\this7\view\label\labels');
        // #解析标签
        foreach ($tags as $class) {
            $obj     = new $class();
            $content = $obj->parse($content, $this->view);
            return $content;
        }
    }
}