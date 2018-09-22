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
     * 是否设置作用域
     * @var boolean
     */
    private $scoped = false;

    /**
     * block 块标签
     * level 嵌套层次
     */
    public $tags
    = [
        'style'    => ['block' => TRUE, 'level' => 1],
        'template' => ['block' => TRUE, 'level' => 1],
        'script'   => ['block' => TRUE, 'level' => 1],
        'json'     => ['block' => TRUE, 'level' => 1],
        'header'   => ['block' => TRUE, 'level' => 1],
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
        #如果内容为空 直接返回
        if (!$content) {
            return;
        }
        #设置作用域
        if ($this->scoped) {
            $content = $this->html_scoped($content);
        }
        #设置唯一码
        $unique = $this->info['unique'];
        #解析组件标签
        $content = $this->tags($content);
        #存储组件
        $this->view->html['compontent'][$unique]['name']     = $this->info['name'];
        $this->view->html['compontent'][$unique]['template'] = str_replace('"', '\"', compress_html($content));
    }

    /**
     * 获取JS脚本
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     * $c="/\{([^{}]+|(?R))*\}/"; 原始语句
     */
    public function _script($attr, $content, &$ubdata) {
        if (isset($attr['type']) && $attr['type'] == 'text/json') {
            $this->_json($attr, $content, $ubdata);
            return;
        }
        #如果内容为空 直接返回
        if (!$content) {
            return;
        }
        #匹配export default模式
        $preg = "#export default#is";
        if (preg_match($preg, $content, $matches)) {
            $data    = 'exports.default =';
            $content = preg_replace($preg, $data, $content);
        }
        #匹配AJAX模式--防止AJAX中的DATA被匹配掉
        $preg = "#ajax\((.+?)(data\:)(.+?)\)#is";
        if (preg_match($preg, $content, $matches)) {
            $data    = 'ajax(\1This7DataAjax:\3)';
            $content = preg_replace($preg, $data, $content);
        }
        #设置唯一码
        $unique = $this->info['unique'];
        #设置替换DATA模式
        $preg = "#data\s*\:\s*\{(([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+)*\}))*\}))*\}))*\}))*\}))*\}))*\}))*\}))*\}))*)\}#";
        if (preg_match($preg, $content, $matches)) {
            $data    = 'data() {return {\1}}';
            $content = preg_replace($preg, $data, $content);
        }
        #还原AJAXDATA数据问题 This7DataAjax
        $preg = "#ajax\((.+?)(This7DataAjax\:)(.+?)\)#is";
        if (preg_match($preg, $content, $matches)) {
            $data    = 'ajax(\1data:\3)';
            $content = preg_replace($preg, $data, $content);
        }

        #数据字段存储
        $this->view->html['compontent'][$unique]['page']   = str_replace(ROOT_DIR, "", $this->info['page']);
        $this->view->html['compontent'][$unique]['line']   = $this->info['line'];
        $this->view->html['compontent'][$unique]['script'] = $content;
    }

    public function getRowsread() {

        $array = explode(PHP_EOL, $this->content);

        foreach ($array as $key => $value) {
            $preg = "#\<script(.+?)\>#is";
            if (preg_match($preg, $value, $matches)) {
                $s = trim($matches[1]);
                if (md5($s) !== md5('type="text/json"') && $s !== md5("type='text/json'")) {
                    $line = $key;
                }
            }
        }
        $this->info['line'] = $line;
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
        $this->getRowsread();
        $this->scoped = false;
        #如果内容为空 直接返回
        if (!$content) {
            return;
        }
        #判断作用域
        if (isset($attr['scoped']) && $attr['scoped']) {
            $this->scoped = substr(md5($content), 0, 6);
        }
        #组件模式
        if ($this->info && in_array($this->info['name'], ['app', 'router-view']) && !empty($this->info['path'])) {
            $file = $this->info['path'] . DS . "base.css";
            if (is_file($file)) {
                $base    = file_get_contents($file);
                $content = $base . $content;
            }
        }
        #CSS编译
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
        if ($this->scoped) {
            $content = $this->css_scoped($content);
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
        $content = trim($content);
        if (!$content || empty($content)) {
            return;
        }
        $array = check_json($content);
        $this->view->module($this->info['page'], $array);
    }

    /**
     * 获取头部
     * @Author   Sean       Yan
     * @DateTime 2018-09-13
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _header($attr, $content, &$ubdata) {
        if ($content) {
            $this->view->html['header'][] = trim($content);
        }
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

    /**
     * CSS作用域
     * @Author   Sean       Yan
     * @DateTime 2018-09-05
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function css_scoped($content = '') {
        $data    = "[data-t-" . $this->scoped . "]";
        $preg    = "#(\w*)\s*\{#is";
        $content = preg_replace($preg, '\1' . $data . '{', $content);
        return $content;
    }

    /**
     * HTML作用域
     * @Author   Sean       Yan
     * @DateTime 2018-09-05
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    public function html_scoped($content = '') {
        $preg = array(
            "#\<(a)(.+?)\>#is",
            "#\<(abbr)(.+?)\>#is",
            "#\<(acronym)(.+?)\>#is",
            "#\<(address)(.+?)\>#is",
            "#\<(applet)(.+?)\>#is",
            "#\<(area)(.+?)\>#is",
            "#\<(article)(.+?)\>#is",
            "#\<(aside)(.+?)\>#is",
            "#\<(audio)(.+?)\>#is",
            "#\<(b)(.+?)\>#is",
            "#\<(base)(.+?)\>#is",
            "#\<(basefont)(.+?)\>#is",
            "#\<(bdi)(.+?)\>#is",
            "#\<(bdo)(.+?)\>#is",
            "#\<(big)(.+?)\>#is",
            "#\<(blockquote)(.+?)\>#is",
            "#\<(br)(.+?)\>#is",
            "#\<(button)(.+?)\>#is",
            "#\<(canvas)(.+?)\>#is",
            "#\<(caption)(.+?)\>#is",
            "#\<(center)(.+?)\>#is",
            "#\<(cite)(.+?)\>#is",
            "#\<(code)(.+?)\>#is",
            "#\<(col)(.+?)\>#is",
            "#\<(colgroup)(.+?)\>#is",
            "#\<(command)(.+?)\>#is",
            "#\<(datalist)(.+?)\>#is",
            "#\<(dd)(.+?)\>#is",
            "#\<(del)(.+?)\>#is",
            "#\<(details)(.+?)\>#is",
            "#\<(dfn)(.+?)\>#is",
            "#\<(dir)(.+?)\>#is",
            "#\<(div)(.+?)\>#is",
            "#\<(dl)(.+?)\>#is",
            "#\<(dt)(.+?)\>#is",
            "#\<(em)(.+?)\>#is",
            "#\<(embed)(.+?)\>#is",
            "#\<(fieldset)(.+?)\>#is",
            "#\<(figcaption)(.+?)\>#is",
            "#\<(figure)(.+?)\>#is",
            "#\<(font)(.+?)\>#is",
            "#\<(footer)(.+?)\>#is",
            "#\<(form)(.+?)\>#is",
            "#\<(frame)(.+?)\>#is",
            "#\<(frameset)(.+?)\>#is",
            "#\<(h1)(.+?)\>#is",
            "#\<(h2)(.+?)\>#is",
            "#\<(h3)(.+?)\>#is",
            "#\<(h4)(.+?)\>#is",
            "#\<(h5)(.+?)\>#is",
            "#\<(h6)(.+?)\>#is",
            "#\<(head)(.+?)\>#is",
            "#\<(header)(.+?)\>#is",
            "#\<(hgroup)(.+?)\>#is",
            "#\<(hr)(.+?)\>#is",
            "#\<(i)(.+?)\>#is",
            "#\<(iframe)(.+?)\>#is",
            "#\<(img)(.+?)\>#is",
            "#\<(input)(.+?)\>#is",
            "#\<(ins)(.+?)\>#is",
            "#\<(kbd)(.+?)\>#is",
            "#\<(label)(.+?)\>#is",
            "#\<(legend)(.+?)\>#is",
            "#\<(li)(.+?)\>#is",
            "#\<(link)(.+?)\>#is",
            "#\<(map)(.+?)\>#is",
            "#\<(mark)(.+?)\>#is",
            "#\<(menu)(.+?)\>#is",
            "#\<(meta)(.+?)\>#is",
            "#\<(nav)(.+?)\>#is",
            "#\<(noframes)(.+?)\>#is",
            "#\<(noscript)(.+?)\>#is",
            "#\<(object)(.+?)\>#is",
            "#\<(ol)(.+?)\>#is",
            "#\<(optgroup)(.+?)\>#is",
            "#\<(option)(.+?)\>#is",
            "#\<(output)(.+?)\>#is",
            "#\<(p)(.+?)\>#is",
            "#\<(param)(.+?)\>#is",
            "#\<(pre)(.+?)\>#is",
            "#\<(progress)(.+?)\>#is",
            "#\<(q)(.+?)\>#is",
            "#\<(rp)(.+?)\>#is",
            "#\<(rt)(.+?)\>#is",
            "#\<(ruby)(.+?)\>#is",
            "#\<(s)(.+?)\>#is",
            "#\<(samp)(.+?)\>#is",
            "#\<(section)(.+?)\>#is",
            "#\<(select)(.+?)\>#is",
            "#\<(small)(.+?)\>#is",
            "#\<(source)(.+?)\>#is",
            "#\<(span)(.+?)\>#is",
            "#\<(strike)(.+?)\>#is",
            "#\<(strong)(.+?)\>#is",
            "#\<(sub)(.+?)\>#is",
            "#\<(summary)(.+?)\>#is",
            "#\<(sup)(.+?)\>#is",
            "#\<(table)(.+?)\>#is",
            "#\<(tbody)(.+?)\>#is",
            "#\<(td)(.+?)\>#is",
            "#\<(textarea)(.+?)\>#is",
            "#\<(tfoot)(.+?)\>#is",
            "#\<(th)(.+?)\>#is",
            "#\<(thead)(.+?)\>#is",
            "#\<(time)(.+?)\>#is",
            "#\<(title)(.+?)\>#is",
            "#\<(tr)(.+?)\>#is",
            "#\<(track)(.+?)\>#is",
            "#\<(tt)(.+?)\>#is",
            "#\<(u)(.+?)\>#is",
            "#\<(ul)(.+?)\>#is",
            "#\<(var)(.+?)\>#is",
            "#\<(video)(.+?)\>#is",
        );
        $data    = "data-t-" . $this->scoped;
        $content = preg_replace($preg, '<\1 ' . $data . '\2>', $content);
        return $content;
    }
}