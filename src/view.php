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

class view {

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
        $template = $style = $script = $json = null;
        if (preg_match('#<template(.*?)>(.*?)<\/template>#is', $body, $matchs)) {
            $template = $matchs[2];
        }
        if (preg_match('#<style(.*?)>(.*?)<\/style>#is', $body, $matchs)) {
            $style = $matchs[2];
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
        $title = '这是This7框架APP应用';
        #获取解析结果
        ob_start();
        echo $this->setHtmlCode($title, $template, $script, $style);
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
            $style = $matchs[2];
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
        $js = array(
            ROOT . '/client/config.js',
            'https://cdn.jsdelivr.net/npm/vue/dist/vue.js',
            ROOT . '/client/app.js',
        );
        $css = array(
            ROOT . '/client/app.css',
        );
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
}