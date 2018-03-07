/*
 * @Author: qinuoyun
 * @Date:   2018-02-27 14:56:24
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-03-06 19:25:36
 */
function P(argument) {
    console.info("打印输出:", argument);
}
/**
 * 将template模板转化成DOM结构,
 * 举例: '<p>{{user.name}}</p>'  -> 对应的DOM结构
 * @param el {Element} 原有的DOM结构
 * @param options {Object}
 * @returns {DOM}
 */
function transclude(element) {
    if ((element instanceof HTMLElement)) {
        return element;
    }
    try {
        let ret = document.querySelector(element);
        return ret;
    } catch (err) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(element, 'text/html');
        // 此处生成的doc是一个包含html和body标签的HTMLDocument
        // 想要的DOM结构被包在body标签里面
        // 所以需要进去body标签找出来
        return doc.querySelector('body').firstChild;
    }
}

function transclude2(el, options) {
    let tpl = options.template;
    if (!tpl) {
        return el;
    }
    //此处缺少判断 options.template 是字符串 还是ID或者class
    // 处理tpl是一个selector的情况, 比如tpl='#child-template'
    let ret = document.querySelector(options.template);
    console.log(ret);
    if (ret) {
        return ret.content.children[0];
    }
    var parser = new DOMParser();
    var doc = parser.parseFromString(tpl, 'text/html');
    // 此处生成的doc是一个包含html和body标签的HTMLDocument
    // 想要的DOM结构被包在body标签里面
    // 所以需要进去body标签找出来
    return doc.querySelector('body').firstChild;
}
/**
 * 遍历实例的所有事件
 * @param vm {This7} This7实例
 * @param action {String} 动作类型,此处为'$on',代表绑定事件
 * @param events {Object} 事件对象,可能包含多个事件, 所以需要遍历
 */
function registerCallbacks(vm, action, events) {
    if (!events) return;
    for (let key in events) {
        let event = events[key];
        register(vm, action, key, event);
    }
}
/**
 * 注册单个事件
 * @param vm {This7} This7实例
 * @param action {String} 动作类型,此处为'$on',代表绑定事件
 * @param key {String} 事件名称, 比如: 'parent-name',代表从父组件那里传递了名称过来
 * @param event {Function} 触发key事件的时候, 对应的回调函数
 */
function register(vm, action, key, event) {
    if (typeof event !== 'function') return;
    vm[action](key, event);
}

/**
 * 这个函数比较厉害, 解决了很多地方的问题。
 * 比如说, 给你一个path="user.name",你如何去获取它对应的值呢?
 * 我之前的做法是循环$data对象,然后一层一层往下找
 * 但是这样的做法是低效的。
 * 作者的写法更高明。他创建根据path创建了一个通用的getter函数,
 * 只需要将这个拼接成的getter函数赋值给对象的get属性
 * 那么自然就能读取到该属性值了
 * @param path {String} 路径,如"user.name"
 * @returns {Function} Getter函数
 */
function expParser(path) {
    path = path.split('.');
    let boby = 'if (o !=null';
    let pathString = 'o';
    let key;
    for (let i = 0; i < path.length - 1; i++) {
        key = path[i];
        pathString += `.${key}`;
        boby += ` && ${pathString} != null`;
    }
    key = path[path.length - 1];
    pathString += `.${key}`;
    boby += `) return ${pathString}`;
    return new Function('o', boby); // eslint-disable-line
};
/**
 * 将文本节点如"{{user.name}}1111",解析成["user.name","1111"]两个节点
 * @param text {String} 例如 "{{user.name}}1111"
 */
function textParser(text) {
    const tagRE = /\{?\{\{(.+?)\}\}\}?/g;
    if (text.trim() === '' || !tagRE.test(text)) return null;
    let tokens = [],
        match, index, value, lastIndex = 0;
    tagRE.lastIndex = 0;
    while (match = tagRE.exec(text)) {
        index = match.index;
        if (index > lastIndex) {
            tokens.push({
                value: text.slice(lastIndex, index)
            });
        }
        index = match.index;
        value = match[1];
        tokens.push({
            tag: true,
            value: value.trim()
        });
        lastIndex = index + match[0].length;
    }
    if (lastIndex < text.length - 1) {
        tokens.push({
            value: text.slice(lastIndex)
        });
    }
    return tokens;
};
/**
 * 格式转换(有待扩充)此处原作者代码非常复杂, 有待研究
 * @param s {string} 例如: user.name或者data-id:user.id
 * @returns {Array} [{expression: "user.name"}]
 */
function dirParser(s) {
    // 此处缺缓存系统
    let dirs = [];
    if (s.indexOf(':') !== -1) {
        // 属性指令 data-id:user.id
        let ss = s.split(':');
        dirs.push({
            raw: s,
            arg: ss[0],
            expression: ss[1]
        });
    } else {
        // 文本指令  user.name
        dirs.push({
            raw: s,
            expression: s
        });
    }
    return dirs;
};


/**
 * 全局API接口
 * @param  {[type]} argument [description]
 * @return {[type]}          [description]
 */
function installGlobalAPI(View) {
    /**
     * 组件构造器
     * 返回组件构造函数
     * @param extendOptions {Object} 组件参数
     * @returns {ViewComponent}
     */
    View.extend = function(extendOptions) {
        let Super = this;
        extendOptions = extendOptions || {};
        let Sub = createClass();
        Sub.prototype = Object.create(Super.prototype);
        Sub.prototype.constructor = Sub;
        Sub.options = _.mergeOptions(Super.options, extendOptions);
        return Sub;
    };
    /**
     * 构造组件构造函数本身
     * 为什么不能直接定义ViewComponent,而要每声明一个组件,都new一个构造函数呢?
     * 因为在extend函数中,我们把options当做ViewComponent的自定义属性,
     * 那么就意味着如果我们一直使用同一个构造函数的话, 那么所有组件最终的options都会是一样的
     * 这显然不妥
     * @returns {Function}
     */
    function createClass() {
        return new Function('return function ViewComponent(options){ this._init(options)}')(); // eslint-disable-line
    }
    /**
     * 注册组件
     * vue的组件使用方式与React不同。React构建出来的组件名可以直接在jsx中使用
     * 当时vue不是。vue的组件在构建之后还需要注册与之相对应的DOM标签
     * @param id {String}, 比如 'my-component'
     * @param definition {ViewComponent} 比如 MyComponent
     * @returns {*}
     */
    View.component = function(id, definition) {
        this.options.components[id] = definition;
        return definition;
    };
}