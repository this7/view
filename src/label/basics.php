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

//标签基类
abstract class basics {
    protected $content;
    protected $view;
    protected $path;
    protected $left;
    protected $right;
    protected $info;
    protected $name;
    protected $exp = [
        '/\s+eq\s+/'  => '==',
        '/\s+neq\s+/' => '!=',
        '/\s+gt\s+/'  => '>',
        '/\s+lt\s+/'  => '<',
        '/\s+lte\s+/' => '<=',
        '/\s+gte\s+/' => '>=',
    ];

    /**
     * 解析标签
     *
     * @param  string   $content        模板内容
     * @param  object   &$view          视图对象
     * @param  object   $info           模版信息
     * @param  string   $path           所在目录
     * @return string                   解析后内容
     */
    public function parse($content, &$view, $info = []) {
        $this->content = $content;
        $this->view    = $view;
        $this->info    = $info;
        $this->left    = "<";
        $this->right   = ">";

        #解析标签
        foreach ($this->tags as $tag => $param) {
            if ($param['block']) {
                #解析块标签
                $this->block($tag, $param);
            } else {
                #解析行标签
                $this->line($tag, $param);
            }
        }

        return $this->content;
    }

    /**
     * 解析块标签
     *
     * @param $tag
     * @param $param
     */
    private function block($tag, $param) {
        for ($i = 1; $i <= $param['level']; $i++) {
            $preg = '#' . $this->left . '(?:' . $tag . '|' . $tag . '\s+(.*?))' . $this->right . '(.*?)' . $this->left . '/' . $tag . $this->right . '#is';
            if (preg_match_all($preg, $this->content, $matchs, PREG_SET_ORDER)) {
                foreach ($matchs as $m) {
                    //获取属性
                    if (!empty($m[1])) {
                        $attr = $this->getAttr($m[1]);
                    } else {
                        $attr = [];
                    }
                    //执行标签方法
                    $method  = '_' . $tag;
                    $replace = $this->$method($attr, $m[2], $this->view);
                    //替换模板内容
                    $this->content = str_replace($m[0], $replace, $this->content);
                }
            } else {
                return;
            }
        }
    }

    /**
     * 解析行标签
     *
     * @param $tag
     */
    private function line($tag) {
        $preg = '#' . $this->left . '(?:' . $tag . '|' . $tag . '\s+(.*?))\s*/?' . $this->right . '#is';
        if (preg_match_all($preg, $this->content, $matchs, PREG_SET_ORDER)) {
            foreach ($matchs as $m) {
                //获取属性
                if (!empty($m[1])) {
                    $attr = $this->getAttr($m[1]);
                } else {
                    $attr = [];
                }
                //执行标签方法
                $method = '_' . $tag;

                $replace = $this->$method($attr, '', $this->view);
                //替换模板内容
                $this->content = str_replace($m[0], $replace, $this->content);
            }
        }
    }

    /**
     * HTML标签复原
     * @param  string $tag  标签名称
     * @param  array  $attr 属性数据
     * @param  string $type 标签类型 line | block
     * @return [type]       [description]
     */
    protected function recover($tag = '', $attr = [], $type = '') {
        $html = '<' . trim($tag);
        #循环遍历属性
        foreach ($attr as $key => $value) {
            if ($key === $value) {
                $html .= ' ' . $key;
            } else {
                $html .= ' ' . $key . '="' . $value . '"';
            }
        }
        switch ($type) {
        case 'line':
            $html .= '/>';
            break;
        case 'block':
        default:
            # code...
            $html .= '>';
            break;
        }
        return $html;
    }

    /**
     * 获取属性
     *
     * @param $con
     *
     * @return array
     */
    private function getAttr($con) {
        $attr = [];
        $preg = '#([\:\w\-]+)\s*=\s*([\\\'\"])(.*?)\2#i';
        if (preg_match_all($preg, $con, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $attr[$name] = preg_replace(array_keys($this->exp), array_values($this->exp), $matches[3][$i]);
            }
        }
        $preg = '#(data\-[\w\-]*)\s*#i';
        if (preg_match_all($preg, $con, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $attr[$name] = $name;
            }
        }
        return $attr;
    }

    /**
     * 替换常量
     *
     * @param $content 内容
     *
     * @return mixed
     */
    protected function replaceConst($content) {
        $const = get_defined_constants(TRUE);

        foreach ($const['user'] as $k => $v) {
            $content = str_replace($k, $v, $content);
        }
        return $content;
    }

}
