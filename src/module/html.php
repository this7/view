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
namespace this7\view\module;

class html {
    /**
     * 单例显示
     * @Author   Sean       Yan
     * @DateTime 2018-07-30
     * @return   [type]     [description]
     */
    public function display() {
        $app = ROOT_DIR . DS . "client/app" . C("view", "postfix");
        #获取解析结果
        ob_start();
        require $app;
        $content = ob_get_clean();
        echo $content;
        exit;
    }
}