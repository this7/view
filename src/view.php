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
        $app    = get_json(ROOT_DIR . DS . 'client/app.json');
        $config = get_json(ROOT_DIR . DS . $this->url . '.json');
        #设置title(标题)
        $title = isset($app['window']['navigationBarTitleText']) ? $app['window']['navigationBarTitleText'] : '这是This7框架APP应用';
        $title = isset($config['navigationBarTitleText']) ? $config['navigationBarTitleText'] : $title;
        #获取HTML内容
        $body = file_get_contents(ROOT_DIR . DS . $this->url . '.html');
        #获取script内容
        $script = file_get_contents(ROOT_DIR . DS . $this->url . '.js');
        #获取解析结果
        ob_start();
        echo $this->setHtmlCode($title, $body, $script);
        $content = ob_get_clean();
        echo $content;
        exit;
    }

    /**
     * 设置HTML代码
     * @param string $value [description]
     */
    public function setHtmlCode($title = 'THIS7', $body = '', $script = '') {
        $js = array(
            ROOT . '/client/config.js',
            get_relative_path(__DIR__) . '/build/Configs.js',
            get_relative_path(__DIR__) . '/build/Functions.js',
            get_relative_path(__DIR__) . '/build/Util.js',
            get_relative_path(__DIR__) . '/build/Batcher.js',
            get_relative_path(__DIR__) . '/build/Observer.js',
            get_relative_path(__DIR__) . '/build/Watcher.js',
            get_relative_path(__DIR__) . '/build/Directive.js',
            get_relative_path(__DIR__) . '/build/Binding.js',
            get_relative_path(__DIR__) . '/build/View.js',
            get_relative_path(__DIR__) . '/build/Component.js',
            get_relative_path(__DIR__) . '/build/Page.js',

            ROOT . '/client/app.js',
        );
        $css = array(
            ROOT . '/client/app.css',
            ROOT . '/' . $this->url . '.css',
        );
        $html = '<!doctype html><html lang="zh"><head><meta charset="UTF-8"><title>';
        $html .= $title;
        $html .= '</title>';
        foreach ($css as $key => $value) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . $value . '?' . time() . '">';
        }
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
     * 页面直接跳转
     * @param  string  $url  跳转地址
     * @param  integer $time 停留时间
     * @param  string  $msg  提示信息
     * @return [type]        [description]
     */
    public function go($url, $time = 0, $msg = '') {
        if (is_array($url)) {
            switch (count($url)) {
            case 2:
                $url = $this->getUrl($url[0], $url[1]);
                break;
            default:
                $url = $this->getUrl($url[0]);
                break;
            }
        } else {
            $url = $this->getUrl($url);
        }
        if (!headers_sent()) {
            $time == 0 ? header("Location:" . $url) : header("refresh:{$time};url={$url}");
            exit($msg);
        } else {
            echo "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            if ($msg) {
                echo ($msg);
            }
            exit;
        }
    }

    /**
     * URL地址获取
     * @param  sting $address   需要解析的地址用/分割
     * @param  sting $parameter 需要解析的参数
     * @return url              返回路径
     */
    public function getUrl($address = NULL, $parameter = NULL) {
        if (strstr($address, "http://") || strstr($address, "https://") || strstr($address, "//")) {
            return $address;
        }
        $array = explode("/", $address);
        $count = count($array);
        $par   = array();
        $url   = null;
        switch ($count) {
        case '3':
            $root     = rtrim(ROOT, "/") . '/' . $array[0];
            $par['c'] = $array[1];
            $par['a'] = $array[2];
            break;
        case '2':
            $root     = rtrim(ROOT, "/");
            $par['c'] = $array[0];
            $par['a'] = $array[1];
            break;
        default:
        case '1':
            $root     = rtrim(ROOT, "/");
            $par['c'] = $_GET['model'];
            $par['a'] = $array[0];
            break;
        }
        #转换参数信息
        if (!empty($parameter)) {
            if (strstr($parameter, "=")) {
                $array = explode(';', $parameter);
                foreach ($array as $key => $value) {
                    $value          = explode('=', $value);
                    $par[$value[0]] = $value[1];
                }
            } elseif (strstr($parameter, "/")) {
                $array = explode('/', $parameter);
                for ($i = 0; $i < count($array); $i += 2) {
                    $par[$array[$i]] = $array[$i + 1];
                }
            } elseif (is_array($parameter)) {
                $par = $parameter;
            }
        }
        #进行参数拼接
        foreach ($par as $key => $value) {
            if ($key == 'c' || $key == 'a' || $key == 'w') {
                $url .= "/{$value}";
            } else {
                $url .= "/{$key}/{$value}";
            }
        }
        return $root . $url;
    }

}