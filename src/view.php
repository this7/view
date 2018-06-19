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
namespace this7\view;
use this7\view\build\compile;

class view extends compile {

    /**
     * 编译文件
     * @var [type]
     */
    public $url;

    /**
     * 组件数组
     * @var [type]
     */
    public $component = array();

    /**
     * [$style description]
     * @var null
     */
    public $style = null;

    /**
     * 扩展信息
     * @var [type]
     */
    protected $exp = [
        '/\s+eq\s+/'  => '==',
        '/\s+neq\s+/' => '!=',
        '/\s+gt\s+/'  => '>',
        '/\s+lt\s+/'  => '<',
        '/\s+lte\s+/' => '<=',
        '/\s+gte\s+/' => '>=',
    ];

    /**
     * 页面模版展示
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function exhibition($expire = 7200) {
        #设置URL路径
        if ($_GET['app'] == 'client') {
            $this->url = "client/pages/" . $_GET['model'] . "/" . $_GET['action'];
        } else {
            $this->url = $_GET['app'] . "/" . $_GET['model'] . "/" . $_GET['action'];
        }
        #获取配置信息
        $app = get_json(ROOT_DIR . DS . 'client/app.json');
        #获取HTML内容
        $body     = file_get_contents(ROOT_DIR . DS . $this->url . '.html');
        $template = $style = $script = $json = $config = null;
        if (preg_match('#<template(.*?)>(.*?)<\/template>#is', $body, $matchs)) {
            $attr = $this->getAttr($matchs[1]);
            if (isset($attr['file'])) {
                $template = $this->setTemplate($attr['file'], $matchs[2]);
            } else {
                $template = $matchs[2];
            }

        }
        if (preg_match('#<json(.*?)>(.*?)<\/json>#is', $body, $matchs)) {
            $json       = $matchs[2];
            $config     = to_array($json);
            $compontent = null;
            if (isset($config['components'])) {
                $this->style = '';
                foreach ($config['components'] as $key => $value) {
                    $compontent .= '"' . $key . '":' . $key . ",";
                    $this->components($key, $value);
                }
            }
        }
        if (preg_match('#<style(.*?)>(.*?)<\/style>#is', $body, $matchs)) {
            $this->style .= $matchs[2];
        }
        if (preg_match('#<script(.*?)>(.*?)<\/script>#is', $body, $matchs)) {
            $script = $matchs[2];
            $string = "var app=new Vue({el: '#app',";
            if (!empty($compontent)) {
                $string .= 'components:{' . trim($compontent, ",") . '},';
            }
            $script = preg_replace("/\{/", $string, $script, 1);
            $script = $script . ");";
        }
        #设置组件
        $component = '';
        foreach ($this->component as $key => $value) {
            $component .= $value . ";";
        }
        $script = $component . $script;
        #设置title(标题)
        $title = isset($config['title']) ? $config['title'] : '这是This7框架APP应用';
        #解析HTML代码
        #执行文件编译
        $compile  = new Compile($this);
        $template = $compile->run($template);
        #获取解析结果
        ob_start();
        echo $this->setHtmlCode($title, $template, $script, $this->style);
        $content = ob_get_clean();
        echo $content;
        exit;
    }

    /**
     * 获取页面组件包
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function components($key, $value = '') {
        $body     = file_get_contents(ROOT_DIR . DS . 'client' . DS . $value . '.html');
        $template = $style = $script = $json = null;
        if (preg_match('#<template(.*?)>(.*?)<\/template>#is', $body, $matchs)) {
            $template = $matchs[2];
        }
        if (preg_match('#<style(.*?)>(.*?)<\/style>#is', $body, $matchs)) {
            $this->style .= $matchs[2];
        }
        if (preg_match('#<json(.*?)>(.*?)<\/json>#is', $body, $matchs)) {
            $json       = $matchs[2];
            $config     = to_array($json);
            $compontent = null;
            if (isset($config['components'])) {
                foreach ($config['components'] as $key => $value) {
                    $compontent .= '"' . $key . '":' . $key . ",";
                    $this->components($key, $value);
                }
            }

        }
        if (preg_match('#<script(.*?)>(.*?)<\/script>#is', $body, $matchs)) {
            $script = $matchs[2];
            $string = "{";
            if (!empty($compontent)) {
                $string .= 'components:{' . trim($compontent, ",") . '},';
            }
            $script = preg_replace("/\{/", $string, $script, 1);
        }
        $t = str_replace(array("\r\n", "\r", "\n"), " ", $template);

        $data              = 'var ' . $key . '=' . substr(trim($script), 0, -1) . ",template:'" . str_replace("'", "\'", $t) . "'}";
        $this->component[] = $data;
    }

    /**
     * 设置HTML代码
     * @param string $value [description]
     */
    public function setHtmlCode($title = 'THIS7', $body = '', $script = '', $style = '') {
        $json = to_array(file_get_contents(ROOT_DIR . DS . 'client' . DS . 'app.json'));
        $js   = $css   = array();
        #判断是否有加载其他外部JS
        if (isset($json['script'])) {
            foreach ($json['script'] as $key => $value) {
                $js[$key] = replace_url($value, 'file');
            }
        }
        #判断是否有加载其他外部CSS
        if (isset($json['style'])) {
            foreach ($json['style'] as $key => $value) {
                $css[$key] = replace_url($value, 'file');
            }
        }
        $html = '<!doctype html><html lang="zh"><head><meta charset="UTF-8"><title>';
        $html .= $title;
        $html .= '</title>';
        foreach ($css as $key => $value) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . $value . '?' . time() . '">';
        }
        $html .= '<style type="text/css">' . $style . '</style>';
        $html .= '</head><body><div id="app">';
        $html .= $body;
        $html .= '</div>';
        foreach ($js as $key => $value) {
            $html .= '<script src="' . $value . '?' . time() . '"></script>';
        }
        $html .= '<script type="text/javascript">';
        $html .= $script;
        $html .= '</script></body></html>';
        return $html;
    }

    /**
     * 设置模板
     * @param string $file     模板文件
     * @param string $template 继承内容
     */
    public function setTemplate($file = '', $template = '') {
        $tpl_file = ROOT_DIR . DS . 'client/template/' . $file . '.html';
        if (is_file($tpl_file)) {
            $tpl_body = file_get_contents($tpl_file);
            return preg_replace('#\<view\>\<\/view\>#is', $template, $tpl_body);
        } else {
            throw new Exception('Error return:模板文件不存在', -2);
        }
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
        $preg = '#([\w\-\:]+)\s*=\s*([\'"])(.*?)\2#i';
        if (preg_match_all($preg, $con, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $attr[$name] = preg_replace(array_keys($this->exp), array_values($this->exp), $matches[3][$i]);
            }
        }
        return $attr;
    }
}