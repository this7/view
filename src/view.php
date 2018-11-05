<?php

/**
 * This7 Frame
 * @Author: else
 * @Date:   2018-06-28 14:07:29
 * @Last Modified by:   seanyan
 * @Last Modified time: 2018-11-05 16:28:58
 */
namespace this7\view;

class view {

    /**
     * 初始APP核心
     * @var [type]
     */
    protected $app;

    /**
     * 链接驱动
     * @var [type]
     */
    protected $link;

    public function __construct($app) {
        $this->app = $app;
    }

    /**
     * 视图URL解析
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function viewURL($url = '') {
        $string = trim($_SERVER['REQUEST_URI'], "/");
        if (substr($string, 0, strlen("view")) === "view") {
            $_GET['type'] = "view";
            #清除之前的缓存
            if (ob_get_level()) {
                ob_end_clean();
            }
            if (empty($_POST['data'])) {
                $returned = request::get($_POST['url']);
            } else {
                $returned = request::post($_POST['url'], $_POST['data']);
            }
            if ($returned['status'] == 200) {
                $returned = $returned['body'];
            }
            echo to_json($returned);
            exit();
        }
        return $url;
    }

    /**
     * 设置执行驱动
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     * @return   [type]     [description]
     */
    protected function driver() {
        $method = C("view", "method");
        switch ($method) {
        case 'vue':
            $this->link = new module\vue($this->app);
            break;
        case 'html':
            $this->link = new module\html($this->app);
            break;
        }
        return $this;
    }

    /**
     * 挂在链接
     * @Author   Sean       Yan
     * @DateTime 2018-08-13
     * @return   [type]     [description]
     */
    public static function single() {
        static $link;
        if (is_null($link)) {
            $link = new static();
        }

        return $link;
    }

    public function __call($method, $params = []) {
        if (is_null($this->link)) {
            $this->driver();
        }
        return call_user_func_array([$this->link, $method], $params);
    }

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([static::single(), $name], $arguments);
    }
}