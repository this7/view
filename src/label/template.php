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
        'style'    => ['block' => TRUE, 'level' => 5],
        'template' => ['block' => TRUE, 'level' => 5],
        'script'   => ['block' => TRUE, 'level' => 5],
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
        if ($this->scoped) {
            $content = $this->html_scoped($content);
        }

        #组件模式
        if ($this->type && $this->type['name'] == 'compontent') {
            #设置KEY
            $key = $this->type['value'];
            #解析组件标签
            $content = $this->tags($content);
            #存储组件
            $this->view->html['compontent'][$key]['template'] = str_replace('"', '\"', compress_html($content));
        }
        #视图模式
        elseif ($this->type && $this->type['name'] == 'routeView') {
            #设置KEY
            $key = $this->type['value'];
            #解析组件标签
            $content = $this->tags($content);
            #存储组件
            $this->view->html['routeView'][$key]['template'] = str_replace('"', '\"', compress_html($content));

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
     * $c="/\{([^{}]+|(?R))*\}/"; 原始语句
     */
    public function _script($attr, $content, &$ubdata) {
        if (isset($attr['type']) && $attr['type'] == 'text/json') {
            $this->_json($attr, $content, $ubdata);
            return;
        }
        if (!$content) {
            return;
        }
        #组件模式
        if ($this->type && $this->type['name'] == 'compontent') {
            #设置KEY
            $key = $this->type['value'];
            if ($key == "router-view") {
                $preg = "#data\s*\:\s*\{(([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+)*\}))*\}))*\}))*\}))*\}))*\}))*\}))*\}))*\}))*)\}#";
                if (preg_match($preg, $content, $matches)) {
                    $data    = 'data() {return {' . $matches[1] . '}}';
                    $content = preg_replace($preg, $data, $content);
                }
            }
            $this->view->html['compontent'][$key]['script'] = $content;
        }
        #视图模式
        elseif ($this->type && $this->type['name'] == 'routeView') {
            #设置KEY
            $key  = $this->type['value'];
            $preg = '#export default#is';
            if (preg_match($preg, $content, $matches)) {
                $data    = "routerView['{$key}'] = ";
                $content = preg_replace($preg, $data, $content);
            }
            $preg = "#data\s*\:\s*\{(([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+|(\{([^{}]+)*\}))*\}))*\}))*\}))*\}))*\}))*\}))*\}))*\}))*)\}#";
            if (preg_match($preg, $content, $matches)) {
                $data    = 'data() {return {' . $matches[1] . '}}';
                $content = preg_replace($preg, $data, $content);
            }
            #返回数据格式
            $this->view->html['routeView'][$key]['script'] = $content;
        }
        #页面模式
        else {
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
        $this->scoped = false;
        if (!$content) {
            return;
        }
        #判断作用域
        if (isset($attr['scoped']) && $attr['scoped']) {
            $this->scoped = substr(md5($content), 0, 6);
        }
        #组件模式
        if ($this->type && $this->type['name'] == 'compontent' && !empty($this->path)) {
            $file = $this->path . DS . "base.css";
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
        if (!$content) {
            return;
        }
        $array = check_json($content);
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