<?php

/**
 * This7 Frame
 * @Author: else
 * @Date:   2018-06-28 14:07:29
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-07-31 17:21:16
 */
namespace this7\view;
use this7\view\build\analysis;
use this7\view\build\singleton;

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

    //更改缓存驱动
    protected function driver() {
        $method = C("view", "method");
        switch ($method) {
        case 'vue':
            $this->link = new analysis($this->app);
            break;
        case 'html':
            $this->link = new singleton($this->app);
            break;
        }

        return $this;
    }

    public function __call($method, $params = []) {
        if (is_null($this->link)) {
            $this->driver();
        }

        return call_user_func_array([$this->link, $method], $params);
    }

    //生成单例对象
    public static function single() {
        static $link;
        if (is_null($link)) {
            $link = new static();
        }

        return $link;
    }

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([static::single(), $name], $arguments);
    }
}