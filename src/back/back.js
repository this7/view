var this7 = (function() {
    //======================================================================================
    //初始化变量类-开始
    //======================================================================================
    /**
     * 函数变量
     * @type {Object}
     */
    var util = objectAugmentations = {};
    /**
     * 类变量
     * @type {Number}
     */
    let uid = 0,
        config = {
            prefix: 'b-'
        };
    /**
     * 类常理
     * @type {Number}
     */
    const ARRAY = 0,
        OBJECT = 1,
        aryMethods = ['push', 'pop', 'shift', 'unshift', 'splice', 'sort', 'reverse'],
        arrayAugmentations = [],
        priorityDirs = ['if', 'repeat'];
    //======================================================================================
    //函数类库-开始
    //======================================================================================
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
    function transclude(el, options) {
        let tpl = options.template;
        console.log(options);
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
     * 全局API接口
     * @param  {[type]} argument [description]
     * @return {[type]}          [description]
     */
    function installGlobalAPI(This7) {
        /**
         * 组件构造器
         * 返回组件构造函数
         * @param extendOptions {Object} 组件参数
         * @returns {This7Component}
         */
        This7.extend = function(extendOptions) {
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
         * 为什么不能直接定义This7Component,而要每声明一个组件,都new一个构造函数呢?
         * 因为在extend函数中,我们把options当做This7Component的自定义属性,
         * 那么就意味着如果我们一直使用同一个构造函数的话, 那么所有组件最终的options都会是一样的
         * 这显然不妥
         * @returns {Function}
         */
        function createClass() {
            return new Function('return function This7Component(options){ this._init(options)}')(); // eslint-disable-line
        }
        /**
         * 注册组件
         * vue的组件使用方式与React不同。React构建出来的组件名可以直接在jsx中使用
         * 当时vue不是。vue的组件在构建之后还需要注册与之相对应的DOM标签
         * @param id {String}, 比如 'my-component'
         * @param definition {This7Component} 比如 MyComponent
         * @returns {*}
         */
        This7.component = function(id, definition) {
            this.options.components[id] = definition;
            return definition;
        };
    }
    //======================================================================================
    //工具方法util-开始
    //======================================================================================
    /**
     * 定义对象属性
     * @param obj {Object} 对象
     * @param key {String} 键值
     * @param val {*} 属性值
     * @param enumerable {Boolean} 是否可枚举
     */
    util.define = function(obj, key, val, enumerable) {
        Object.defineProperty(obj, key, {
            value: val,
            enumerable: !!enumerable,
            writable: true,
            configurable: true
        });
    };
    /**
     * 这不需要多加解释吧
     * @param to
     * @param from
     */
    util.extend = function(to, from) {
        for (let key in from) {
            to[key] = from[key];
        }
    };
    /**
     * 代理属性
     * @param to {Object} 目标对象
     * @param from {Object} 当前对象
     * @param key {String} 键值
     */
    util.proxy = function(to, from, key) {
        if (to.hasOwnProperty(key)) return;
        Object.defineProperty(to, key, {
            enumerable: true,
            configurable: true,
            get: function() {
                return from[key];
            },
            set: function(val) {
                from[key] = val;
            }
        });
    };
    /**
     * insertBefore
     * @param el {Element}
     * @param target {Element}
     */
    util.before = function(el, target) {
        target.parentNode.insertBefore(el, target);
    };
    /**
     * 因为没有原声的insertAfter方法, 所以需要迂回处理一下
     * @param el
     * @param target
     */
    util.after = function(el, target) {
        if (target.nextSibling) {
            util.before(el, target.nextSibling);
        } else {
            target.parentNode.appendChild(el);
        }
    };
    /**
     * removeSelf
     * @param el {Element}
     */
    util.remove = function(el) {
        el.parentNode.removeChild(el);
    };
    /**
     * 用新的节点代替旧的节点
     * @param target {Element} 旧节点
     * @param el {Element} 新节点
     */
    util.replace = function(target, el) {
        let parent = target.parentNode;
        parent.insertBefore(el, target);
        parent.removeChild(target);
    };
    /**
     * 把node节点的attr取出来(并且移除该attr)
     * 注意! 这里会把该attr移除! 专门用来处理v-if这样的属性
     * @param node {Element}
     * @param attr {String}
     * @returns {string}
     */
    util.attr = function(node, attr) {
        attr = config.prefix + attr;
        let val = node.getAttribute(attr);
        if (val) {
            node.removeAttribute(attr);
        }
        return val;
    };
    /**
     * 事件绑定
     * @param el {Element}
     * @param event {String} 比如:'click'
     * @param cb {Function} 事件函数
     */
    util.on = function(el, event, cb) {
        el.addEventListener(event, cb);
    };
    /**
     * 获取动态数据绑定属性值,
     * 比如 b-bind:name="user.name" 和 :name="user.name"
     * @param node {Element}
     * @param name {String} 属性名称 比如"name"
     * @returns {string} 属性值
     */
    util.getBindAttr = function(node, name) {
        return util.getAttr(node, `:${name}`) || util.getAttr(node, `${config.prefix}bind:${name}`);
    };
    /**
     * 获取节点属性值,并且移除该属性
     * @param node {Element}
     * @param attr {String}
     * @returns {string}
     */
    util.getAttr = function(node, attr) {
        let val = node.getAttribute(attr);
        if (val) {
            node.removeAttribute(attr);
        }
        return val;
    };
    /**
     * Debug输出函数
     * @returns {console}
     */
    util.warn = function() {
        console.warn.apply(console, arguments);
    };
    /**
     * 合并对象
     * @param  {Object} parent 原始对象
     * @param  {Object} child  需要合并项
     * @return {Object}        返回对象合集
     */
    util.mergeOptions = function(parent, child) {
        return Object.assign({}, parent, child);
    };
    /**
     * DEMO测试项
     * @return {[type]} [description]
     */
    util.demo = function(value) {
        console.log("Demo测试项:", value);
    }
    var _ = util;
    //======================================================================================
    //Batcher批处理构造函数-开始
    //======================================================================================
    /**
     * 批处理构造函数
     * @constructor
     */
    function Batcher() {
        this.reset();
    }
    /**
     * 批处理充值
     */
    Batcher.prototype.reset = function() {
        this.has = {};
        this.queue = [];
        this.waiting = false;
    };
    /**
     * 将事件添加到队列中
     * @param job {Watcher} watcher事件
     */
    Batcher.prototype.push = function(job) {
        if (!this.has[job.id]) {
            this.queue.push(job);
            this.has[job.id] = job;
            if (!this.waiting) {
                this.waiting = true;
                setTimeout(() => {
                    // isFlushing, 此字段用来处理多重异步队列的问题
                    this.isFlushing = true;
                    this.flush();
                    this.isFlushing = false;
                });
            }
        }
    };
    /**
     * 执行并清空事件队列
     */
    Batcher.prototype.flush = function() {
        this.queue.forEach((job) => {
            // job.cb.call(job.ctx);
            job.run();
        });
        this.reset();
    };
    //======================================================================================
    //Observer类库(观察者设计模式)-开始
    //======================================================================================
    /**
     * 定义一个对象,它的属性中有push等经过改写的数组方法
     */
    aryMethods.forEach((method) => {
        let original = Array.prototype[method];
        arrayAugmentations[method] = function() {
            let result = original.apply(this, arguments);
            let ob = this.$observer;
            ob.notify('set', null, this.length);
            return result;
        };
    });
    /**
     * 给对象添加"添加属性"方法
     * 为什么要写这个方法呢?
     * 因为如果没有这个方法, 你直接data.info = {name:"liangshaofeng"},
     * 这样虽然可以修改数据对象,但是却没法监听到这一改变,defineProperty只能监听已经存在的属性
     * 所以需要在添加新的属性的时候调用特殊的方法,下面的delete方法作用与此相同
     * TODO: 添加和删除动作是否需要进行事件传播
     */
    _.define(objectAugmentations, '$add', function(key, val) {
        if (this.hasOwnProperty(key)) return;
        _.define(this, key, val, true);
        let ob = this.$observer;
        ob.observe(key, val);
        ob.convert(key, val);
    });
    _.define(objectAugmentations, '$delete', function(key) {
        if (!this.hasOwnProperty(key)) return;
        delete this[key];
    });
    /**
     * 观察者构造函数
     * @param value {Object} 数据对象
     * @param type {Int} 数据对象的类型(分为对象和数组)
     * @constructor
     */
    function Observer(value, type) {
        this.value = value;
        this.id = ++uid;
        // TODO 这里enumerable一定要为false,否则会触发死循环, 原因未明
        // 将当前对象存储到当前对象的$observer属性中
        Object.defineProperty(value, '$observer', {
            value: this,
            enumerable: false,
            writable: true,
            configurable: true
        });
        if (type === ARRAY) {
            value.__proto__ = arrayAugmentations; // eslint-disable-line
            this.link(value);
        } else if (type === OBJECT) {
            value.__proto__ = objectAugmentations; // eslint-disable-line
            this.walk(value);
        }
    }
    /**
     * 遍历数据对象
     * @param obj {Object} 待遍历的数据对象
     */
    Observer.prototype.walk = function(obj) {
        let val;
        for (let key in obj) {
            if (!obj.hasOwnProperty(key)) return;
            val = obj[key];
            // 递归
            this.observe(key, val);
            this.convert(key, val);
        }
    };
    /**
     * 定义对象属性
     * @param key {string} 属性键名
     * @param val {Any} 属性值
     */
    Observer.prototype.convert = function(key, val) {
        let ob = this;
        Object.defineProperty(this.value, key, {
            enumerable: true,
            configurable: true,
            get: function() {
                if (Observer.emitGet) {
                    ob.notify('get', key);
                }
                return val;
            },
            set: function(newVal) {
                if (newVal === val) return;
                val = newVal;
                ob.notify('set', key, newVal);
            }
        });
    };
    /**
     * 调用创建observer函数
     * 并且判断是否有父节点,如果有,则存储父节点到自身,
     * 目的是为了方便后面事件传播使用
     * @param key {string} 键值
     * @param val {Any} 属性值
     */
    Observer.prototype.observe = function(key, val) {
        let ob = Observer.create(val);
        if (!ob) return;
        ob.parent = {
            key,
            ob: this
        };
    };
    /**
     * 这个方法是用来处理如下情况: var ary = [1,{name:liangshaofeng}]
     * 也就是说,当数组的某些项是一个对象的时候,
     * 那么需要给这个对象创建observer监听它
     * @param items {Array} 待处理数组
     */
    Observer.prototype.link = function(items) {
        items.forEach((value, index) => {
            this.observe(index, value);
        });
    };
    /**
     * 订阅事件
     * @param event {string} 事件类型
     * @param fn {Function} 对调函数
     * @returns {Observer} 观察者对象
     */
    Observer.prototype.on = function(event, fn) {
        this._cbs = this._cbs || {};
        if (!this._cbs[event]) {
            this._cbs[event] = [];
        }
        this._cbs[event].push(fn);
        // 这里return this是为了实现.on(...).on(...)这样的级联调用
        return this;
    };
    /**
     * 取消订阅事件
     * @param event {string} 事件类型
     * @param fn {Function} 回调函数
     * @returns {Observer} 观察者对象
     */
    Observer.prototype.off = function(event, fn) {
        this._cbs = this._cbs || {};
        // 取消所有订阅事件
        if (!arguments.length) {
            this._cbs = {};
            return this;
        }
        let callbacks = this._cbs[event];
        if (!callbacks) return this;
        // 取消特定事件
        if (arguments.length === 1) {
            delete this._cbs[event];
            return this;
        }
        // 取消特定事件的特定回调函数
        for (let i = 0, cb; i < callbacks.length; i++) {
            cb = callbacks[i];
            if (cb === fn) {
                callbacks.splice(i, 1);
                break;
            }
        }
        return this;
    };
    /**
     * 触发消息, 并且将消息逐层往上传播
     *
     */
    Observer.prototype.notify = function(event, path, val) {
        this.emit(event, path, val);
        let parent = this.parent;
        if (!parent) return;
        let ob = parent.ob;
        let key = parent.key;
        let parentPath;
        // 此处为为了兼容数组的情况
        if (path) {
            parentPath = `${key}.${path}`;
        } else {
            parentPath = key;
        }
        ob.notify(event, parentPath, val);
    };
    /**
     * 触发执行回调函数
     * @param event {string} 事件类型
     * @param event {path} 事件触发路径
     *
     */
    Observer.prototype.emit = function(event, path, val) {
        this._cbs = this._cbs || {};
        let callbacks = this._cbs[event];
        if (!callbacks) return;
        callbacks = callbacks.slice(0);
        callbacks.forEach((cb, i) => {
            callbacks[i].apply(this, arguments);
        });
    };
    /**
     * 根据不同的数据类型,调用observer构造函数
     * @param value {Any} 数据
     * @returns {Observer}
     */
    Observer.create = function(value) {
        if (Array.isArray(value)) {
            return new Observer(value, ARRAY);
        } else if (typeof value === 'object') {
            return new Observer(value, OBJECT);
        }
    };
    //======================================================================================
    //Parse解析方法类库-开始
    //======================================================================================
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
    //======================================================================================
    //Watcher类库-开始
    //======================================================================================
    let batcher = new Batcher();
    /**
     * Watcher构造函数
     * 有什么用呢这个东西?两个用途
     * 1. 当指令对应的数据发生改变的时候, 执行更新DOM的update函数
     * 2. 当$watch API对应的数据发生改变的时候, 执行你自己定义的回调函数
     * @param vm {This7} This7实例
     * @param expression {String} 表达式, 例如: "user.name"
     * @param cb {Function} 当对应的数据更新的时候执行的回调函数
     * @param ctx {Object} 回调函数执行上下文
     * @constructor
     */
    function Watcher(vm, expression, cb, ctx) {
        this.id = ++uid;
        this.vm = vm;
        this.expression = expression;
        this.cb = cb;
        this.ctx = ctx || vm;
        this.deps = Object.create(null);
        this.getter = expParser(expression);
        this.initDeps(expression);
    }
    /**
     * @param path {String} 指令表达式对应的路径, 例如: "user.name"
     */
    Watcher.prototype.initDeps = function(path) {
        this.addDep(path);
        this.value = this.get();
    };
    /**
     * 这个函数不好理解。
     * 大概是: 根据给出的路径, 去获取Binding对象。
     * 如果该Binding对象不存在,则创建它。
     * 然后把当前的watcher对象添加到binding对象上
     * @param path {string} 指令表达式对应的路径, 例如"user.name"
     */
    Watcher.prototype.addDep = function(path) {
        let vm = this.vm;
        let deps = this.deps;
        if (deps[path]) return;
        deps[path] = true;
        let binding = vm._getBindingAt(path) || vm._createBindingAt(path);
        binding._addSub(this);
    };
    /**
     * 当数据发生更新的时候, 就是触发notify
     * 然后冒泡到顶层的时候, 就是触发updateBindingAt
     * 对应的binding包含的watcher的update方法就会被触发。
     * 就是执行watcher的cb回调。
     * 然后, watcher的cb回调是什么呢?
     * 两种情况, 如果是$watch调用的话,那么是你自己定义的回调函数。
     * 如果是directive调用的话,
     * 那么就是directive的_update方法。
     * 那么directive的_update方法是什么呢?
     * 其实就是各自对应的更新方法。比如对应文本节点来说, 就是更新nodeValue的值
     * 就是这么的。。复杂。。
     */
    Watcher.prototype.update = function() {
        // 这里需要加isFlushing的判断
        // 为什么要这样做呢?
        // 因为在实现组件化的时候发现, 会出现多重异步队列的问题
        // 也就是,执行的异步队列中某些任务会产生新的异步操作
        // 所以,现在的处理是, 如果在正在执行异步队列的过程中产生了新的异步任务
        // 那么将添加异步任务本身做成是异步操作
        if (!batcher.isFlushing) {
            batcher.push(this);
        } else {
            setTimeout(() => {
                batcher.push(this);
            });
        }
    };
    /**
     * 在调用属性的getter前调用
     * 作用是打开某些开关
     */
    Watcher.prototype.beforeGet = function() {
        Observer.emitGet = true;
        this.vm._activeWatcher = this;
    };
    /**
     * getter.call是完成计算属性的核心,
     * 因为正是这里的getter.call, 执行了该计算属性的getter方法,
     * 从而执行该计算属性所依赖的原始原型的get方法
     * 从而发出get事件,冒泡到底层, 触发collectDep事件
     */
    Watcher.prototype.get = function() {
        this.beforeGet();
        let value = this.getter.call(this.vm, this.vm);
        this.afterGet();
        return value;
    };
    /**
     * 在调用属性的getter之后调用
     * 作用是关闭某些开关
     */
    Watcher.prototype.afterGet = function() {
        Observer.emitGet = false;
        this.vm._activeWatcher = null;
    };
    /**
     * 为Watcher添加一个run方法, 此方法调用回调函数
     * 之前是直接在bathcer的flush函数里面调用cb
     * 但是这样传递参数的问题不好处理
     * 所以为了将属性变化前后的值传递给cb
     * 弄一个run函数更好一些
     */
    Watcher.prototype.run = function() {
        let value = this.get();
        let oldValue = this.value;
        this.value = value;
        this.cb.call(this.ctx, value, oldValue);
    };
    //======================================================================================
    //Directive类库(指令)-开始
    //======================================================================================
    /**
     * 指令构造函数
     * @param name {string} 例如:text, 代表是文本节点
     * @param el {Element} 对应的文本节点
     * @param vm {This7} This7实例
     * @param descriptor {Object} 指令描述符, 描述一个指令, 形如: {expression: "user.name"}
     * @constructor
     */
    function Directive(name, el, vm, descriptor) {
        this.name = name;
        this.el = el;
        this.vm = vm;
        this.expression = descriptor.expression;
        this.arg = descriptor.arg;
        this._initDef();
        this._bind();
    };
    /**
     * 根据指令表达式实例化watcher, 并且执行directive对应的update函数
     * 为毛要这样呢? 因为如果不这样, 那么初次解析渲染DOM的时候就没法显示真实的数据了呀!
     * 你想啊, 第一次压根没触发数据改变, 怎么会进行各种update呢?
     * 所以只能自己手动update了
     * @private
     */
    Directive.prototype._bind = function() {
        if (!this.expression) return;
        //console.log("绑定指令 指令名称", this.bind());
        this.bind && this.bind();
        if (this.name === 'component') {
            // 组件指令走这边
            this.update && this.update();
        } else {
            // 非组件指令走这边
            this._watcher = new Watcher(
                // 这里上下文非常关键
                // 如果是普通的非组件指令, 上下文是vm本身
                // 但是如果是prop指令, 那么上下文应该是该组件的父实例
                (this.name === 'prop' ? this.vm.$parent : this.vm), this.expression, this._update, // 回调函数,目前是唯一的,就是更新DOM
                this // 上下文
            );
            this.update(this._watcher.value);
        }
    };
    /**
     * 不同指令对应的更新update函数不同, 所以需要分类处理
     * 比如对于文本节点, this.name = 'text', 然后他的update函数就是更新nodeValue
     * 这里有点绕, 实际的函数请参考 /src/directives/text.js
     * @private
     */
    Directive.prototype._initDef = function() {
        let def = this.vm.$options.directives[this.name];
        _.extend(this, def);
    };
    /**
     * 这里就更绕了。意思是: 指令本身的更新函数, 其实是调用它自己的更新函数
     * 为什么要这样处理呢? 首先, 如果数据发生改变的话, 会调用指令的更新函数, 这没有问题
     * 但是,不同的指令类型, 所执行的更新函数是不一样的!这一点跟上面函数_initDef直接相关
     * @param value {*} 属性变化之后新的值
     * @param oldValue {*} 属性变化之前老的值
     * @private
     */
    Directive.prototype._update = function(value, oldValue) {
        this.update(value, oldValue);
    };
    //======================================================================================
    //Binding类库(绑定)-开始
    //======================================================================================
    /**
     * Binding构造函数
     * @constructor
     */
    function Binding() {
        // 用来存放各种watcher实例
        this._subs = [];
    }
    /**
     * 给你一个键值, 如果原来就有这个键值对应的binding, 那么乖乖返回就好了
     * 否则就新建一个再返回
     * @param key {string} 形如: "name"
     * @returns {Binding}
     * @private
     */
    Binding.prototype._addChild = function(key) {
        return this[key] || new Binding();
    };
    /**
     * 这个就是把watcher塞到_subs数组里面啦
     * 之后触发update的话,是会遍历这个数组滴
     * @param sub {Watcher} 观察容器
     * @private
     */
    Binding.prototype._addSub = function(sub) {
        this._subs.push(sub);
    };
    //======================================================================================
    //核心类方法-开始
    //======================================================================================
    //构造函数
    function This7(options) {
        this._init(options);
    }
    // //静态共有属性方法
    This7.prototype = {
        constructor: This7,
        /**
         * 实例初始化入口
         * @param options {Object} This7实例选项
         * @private
         */
        _init: function(options) {
            // 这个变量是用来存储遍历DOM过程中生成的当前的Watcher
            // 在实现computed功能的时候需要用到
            this._activeWatcher = null;
            this.$options = options;
            this.$parent = options.parent;
            this.$children = [];
            this._events = {};
            if (!this.$options.isComponent) {
                this.__proto__ = this.$parent; // eslint-disable-line
            }
            // This7构造函数上定义了一些指令相关的方法,需要将它们引用过来, 以供后面的调用
            _.extend(this.$options, this.constructor.options);
            if (this.$parent) {
                this.$parent.$children.push(this);
                // this.$data = options.parent.$data;
            }
            this.$data = options.data || {};
            // 初始化组件props
            this._initProps();
            // 初始化data, 主要是做Observer,数据监听这一块
            this._initData(this.$data);
            // 初始化计算属性
            this._initComputed();
            // 初始化数据代理
            this._initProxy();
            // 初始化事件
            this._initEvents();
            // 初始化方法
            this._initMethods();
            // binding、watcher、directive是实现动态数据绑定的三大核心对象
            this._initBindings();
            // 指令数组,用于存放解析DOM模板的时候生成的指令
            this._directives = [];
            // 解析DOM模板, 渲染真实的DOM
            if (options.el) {
                this.$mount(options.el);
            }
        },
        /**
         * 整体思路: 利用递归的思想
         */
        _compile: function() {
            this.$el = transclude(this.$el, this.$options);
            this._compileNode(this.$el);
        },
        /**
         * 渲染节点
         * @param node {Element}
         * @private
         */
        _compileElement: function(node) {
            // 判断节点是否是组件指令
            if (this._checkComponentDirs(node)) {
                return;
            }
            let hasAttributes = node.hasAttributes();
            // 解析高优指令
            if (hasAttributes && this._checkPriorityDirs(node)) {
                return;
            }
            // 解析属性
            if (hasAttributes) {
                this._compileAttrs(node);
            }
            if (node.hasChildNodes()) {
                Array.from(node.childNodes).forEach(this._compileNode, this);
            }
        },
        /**
         * 渲染文本节点
         * @param node {Element}
         * @private
         */
        _compileTextNode: function(node) {
            let tokens = textParser(node.nodeValue);
            if (!tokens) return;
            tokens.forEach((token) => {
                if (token.tag) {
                    // 指令节点
                    let value = token.value;
                    let el = document.createTextNode('');
                    _.before(el, node);
                    this._bindDirective('text', value, el);
                } else {
                    // 普通文本节点
                    let el = document.createTextNode(token.value);
                    _.before(el, node);
                }
            });
            _.remove(node);
        },
        _compileNode: function(node) {
            switch (node.nodeType) {
                // text
                case 1:
                    this._compileElement(node);
                    break;
                    // node
                case 3:
                    this._compileTextNode(node);
                    break;
                default:
                    return;
            }
        },
        /**
         * 生成指令
         * @param name {string} 'text' 代表是文本节点
         * @param value {string} 例如: user.name  是表示式
         * @param node {Element} 指令对应的el
         * @private
         */
        _bindDirective: function(name, value, node) {
            let descriptors = dirParser(value);
            let dirs = this._directives;
            descriptors.forEach((descriptor) => {
                dirs.push(new Directive(name, node, this, descriptor));
            });
        },
        /**
         * 检查node节点是否包含某些如 "v-if" 这样的高优先级指令
         * 如果包含,那么就不用走原先的DOM遍历了, 直接走指令绑定
         * @param node {Element}
         * @private
         */
        _checkPriorityDirs: function(node) {
            for (let i = 0, length = priorityDirs.length; i < length; i++) {
                let dir = priorityDirs[i];
                let value = _.attr(node, dir);
                if (value) {
                    this._bindDirective(dir, value, node);
                    return true;
                }
            }
        },
        /**
         * 判断节点是否是组件指令,如 <my-component></my-component>
         * 如果是,则构建组件指令
         * @param node {Element}
         * @returns {boolean}
         * @private
         */
        _checkComponentDirs: function(node) {
            let tagName = node.tagName.toLowerCase();
            if (this.$options.components[tagName]) {
                let dirs = this._directives;
                dirs.push(new Directive('component', node, this, {
                    expression: tagName
                }));
                return true;
            }
        },
        /**
         * 循环解析属性(包括特殊属性和普通属性)
         * @param node {Element}
         * @private
         */
        _compileAttrs: function(node) {
            let attrs = Array.from(node.attributes);
            let registry = this.$options.directives;
            attrs.forEach((attr) => {
                let attrName = attr.name;
                let attrValue = attr.value;
                if (attrName.indexOf(config.prefix) === 0) {
                    // 特殊属性 如: v-on:"submit"
                    let dirName = attrName.slice(config.prefix.length);
                    if (!registry[dirName]) return;
                    this._bindDirective(dirName, attrValue, node);
                } else {
                    // 普通属性 如: data-id="{{user.id}}"
                    this._bindAttr(node, attr);
                }
            });
        },
        /**
         *
         * @param node {Element}
         * @param attr {Object} 如 {name:"data-id", id:"app"}
         * @private
         */
        _bindAttr: function(node, attr) {
            let {
                name,
                value
            } = attr;
            let tokens = textParser(value);
            if (!tokens) return;
            this._bindDirective('attr', `${name}:${tokens[0].value}`, node);
        },
        /**
         * 初始化节点
         * @param el {string} selector
         * @private
         */
        _initElement: function(el) {
            if (typeof el === 'string') {
                let selector = el;
                this.$el = el = document.querySelector(el);
                if (!el) {
                    _.warn(`Cannot find element: ${selector}`);
                }
            } else {
                this.$el = el;
            }
            this.$el.__this7__ = this;
        },
        /**
         * 这个函数很重要。当数据方法改变时, 执行的就是它了。
         * 它分为两部分,
         * 先更新本实例所有相关的binding
         * 然后再更新本实例所有子实例的相关binding
         * 它会去把对应改变了的数据那里找出所有的watcher, 然后一一执行他们的cb
         * 一个都不放过
         * @private
         */
        _updateBindingAt: function() {
            this._updateSelfBindingAt(...arguments);
            this._updateChildrenBindingAt(...arguments);
        },
        /**
         * 执行本实例发生了数据变动的watcher
         * @param event {String} 事件类型
         * @param path {String} 事件路径
         * @private
         */
        _updateSelfBindingAt: function(event, path) {
            let pathAry = path.split('.');
            // TODO 此处代码有待优化,可以改成new Function
            let r = this._rootBinding;
            for (let i = 0, l = pathAry.length; i < l; i++) {
                let key = pathAry[i];
                r = r[key];
                if (!r) return;
            }
            let subs = r._subs;
            subs.forEach((watcher) => {
                watcher.cb();
            });
        },
        /**
         * 执行本实例所有子实例发生了数据变动的watcher
         * @private
         */
        _updateChildrenBindingAt: function() {
            if (!this.$children.length) return;
            this.$children.forEach((child) => {
                if (child.$options.isComponent) return;
                child._updateBindingAt(...arguments);
            });
        },
        /**
         * 就是在这里定于数据对象的变化的
         * @private
         */
        _initBindings: function() {
            this._rootBinding = new Binding();
            this.observer.on('set', this._updateBindingAt.bind(this)).on('get', this._collectDep.bind(this));
        },
        /**
         * 根据给出的路径获取binding
         * 如果有,则返回该binding;如果没有,则返回false
         * @param path {String} 例如: "user.name"
         * @returns {boolean|Binding}
         * @private
         */
        _getBindingAt: function(path) {
            let b = this._rootBinding;
            let pathAry = path.split('.');
            for (let i = 0; i < pathAry.length; i++) {
                let key = pathAry[i];
                b = b[key];
                if (!b) return false;
            }
            return b;
        },
        /**
         * 根据给出的路径创建binding
         * @param path {String} 例如: "user.name"
         * @returns {Binding}
         * @private
         */
        _createBindingAt: function(path) {
            let b = this._rootBinding;
            let pathAry = path.split('.');
            for (let i = 0; i < pathAry.length; i++) {
                let key = pathAry[i];
                b = b[key] = b._addChild(key);
            }
            return b;
        },
        /**
         * 收集依赖。
         * 为什么需要这个东西呢?
         * 因为在实现computed计算属性功能的过程中,
         * 发现程序需要知晓计算出来的属性到底依赖于哪些原先就有的属性
         * 这样才能做到在对应原有的属性的_subs数组中添加新属性指令的watcher事件
         * @param path {String} get事件传播到顶层时的路径,比如"user.name"
         * @private
         */
        _collectDep: function(event, path) {
            let watcher = this._activeWatcher;
            if (watcher) {
                watcher.addDep(path);
            }
        },
        /**
         * 初始化观察独享
         * @param data {Object} 就是那个大的对象啦
         * @private
         */
        _initData: function(data) {
            this.observer = Observer.create(data);
        },
        /**
         * 初始化组件的props,将props解析并且填充到$data中去
         * 在这个过程中,如果是动态属性, 那么会在父实例生成对应的directive和watcher
         * 用于prop的动态更新
         * @private
         */
        _initProps: function() {
            let {
                el,
                props,
                isComponent
            } = this.$options;
            if (!isComponent || !props) return;
            let compiledProps = this.compileProps(el, props);
            this.applyProps(compiledProps);
        },
        /**
         * 初始化所有计算属性
         * 主要完成一个功能:将计算属性定义的function当成是该属性的getter函数
         * @private
         */
        _initComputed: function() {
            let computed = this.$options.computed;
            if (!computed) return;
            for (let key in computed) {
                let def = computed[key];
                if (typeof def === 'function') {
                    def = {
                        get: def
                    };
                    def.enumerable = true;
                    def.configurable = true;
                    Object.defineProperty(this.$data, key, def);
                }
            }
        },
        /**
         * 初始化方法: 将method底下的方法proxy到vm实例上面去
         * @private
         */
        _initMethods: function() {
            let {
                methods
            } = this.$options;
            if (!methods) return;
            for (let key in methods) {
                this[key] = () => {
                    methods[key].apply(this, arguments);
                };
            }
        },
        /**
         * 初始化代理,将 $data里面的数据代理到vm实例上面去
         * @private
         */
        _initProxy: function() {
            for (let key in this.$data) {
                // this[key] = this.$data[key];
                _.proxy(this, this.$data, key);
            }
        },
        /**
         * 解析、渲染DOM
         * @param el {string} selector
         */
        $mount: function(el) {
            // 合法性判断等, 有待补充
            this._initElement(el);
            // 解析、渲染DOM
            this._compile();
        },
        /**
         * 这就是 vm.$watch(function(){.....})那里用到的
         * @param exp {String} 指令表达式
         * @param cb {Function} 当指令表达式对应的数据发生改变时执行的回调函数
         */
        $watch: function(exp, cb) {
            new Watcher(this, exp, cb, this);
        },
        /**
         * 设置数据值, 比如 this.vm.$set('user.name', "lianghshaofeng");
         * 等价于 app.$data.user.name = "liangshaofeng"
         * @param exp {String} 比如user.name
         * @param val {*} 数据的值
         */
        $set: function(exp, val) {
            let ee = exp.split('.');
            let length = ee.length;
            let data = this.$data;
            for (let i = 0; i < length - 1; i++) {
                let key = ee[i];
                data = data[key];
            }
            data[ee[length - 1]] = val;
        },
        /**
         * 插入This7实例
         * @param target {Element}
         */
        $before: function(target) {
            _.before(this.$el, target);
        },
        /**
         * 移除This7实例
         */
        $remove: function() {
            if (this.$el.parentNode) {
                _.remove(this.$el);
            }
        },
        /**
         * 解析props参数, 包括动态属性和静态属性
         * @param el {Element} 组件节点,比如: <my-component b-bind:name="user.name" message="hello"></my-component>
         * @param propOptions {Object} Vue.extend的时候传进来的prop对象参数, 形如 {name:{}, message:{}}
         * @returns {Array} 解析之后的props数组,
         * 形如: [
         *          {
         *              "name":"name",     // 组件属性名
         *              "options":{},      // 原先Vue.extend传过来的属性对应的参数, 暂时未空, 之后会放一些参数校验之类的
         *              "raw":"user.name", // 属性对应的值
         *              "dynamic":true,    // true代表是动态属性,也就是从父实例/组件那里获取值
         *              "parentPath":"user.name"   // 属性值在父实例/组件中的路径
         *          },
         *          {
         *              "name":"message",
         *              "options":{},
         *              "raw":"How are you?"
         *          }
         *     ]
         */
        compileProps: function(el, propOptions) {
            let names = Object.keys(propOptions);
            let props = [];
            names.forEach((name) => {
                let options = propOptions[name] || {};
                let prop = {
                    name,
                    options,
                    raw: null
                };
                let value;
                if ((value = util.getBindAttr(el, name))) {
                    // 动态props
                    prop.raw = value;
                    prop.dynamic = true;
                    prop.parentPath = value;
                } else if ((value = util.getAttr(el, name))) {
                    // 静态props
                    prop.raw = value;
                }
                props.push(prop);
            });
            return props;
        },
        /**
         * 应用属性到vm实例上
         * 如果是动态属性, 需要额外走Directive、Watcher那一套流程
         * 因为只有这样,当父实例/组件的属性发生变化时,才能将变化传导到子组件
         * @param props {Array} 解析之后的props数组
         */
        applyProps: function(props) {
            props.forEach((prop) => {
                if (prop.dynamic) {
                    // 动态props
                    let dirs = this.$parent._directives;
                    dirs.push(new Directive('prop', null, this, {
                        expression: prop.raw, // prop对应的父实例/组件的哪个数据, 如:user.name
                        arg: prop.name // prop在当前组件中的属性键值, 如:name
                    }));
                } else {
                    this.initProp(prop.name, prop.raw, prop.dynamic);
                }
            });
        },
        /**
         * 将prop设置到当前组件实例的$data中去, 这样一会儿initData的时候才能监听到这些数据
         * 如果是动态属性, 还需要跑到父实例/组件那里去取值
         * @param path {String} 组件prop键值，如"name"
         * @param val {String} 组件prop值，如果是静态prop，那么直接是"How are you"这种。
                                如果是动态prop，那么是"user.name"这种，需要从父实例那里去获取实际值
         * @param dynamic {Boolean} true代表是动态prop， false代表是静态prop
         */
        initProp: function(path, val, dynamic) {
            if (!dynamic) {
                // 静态prop
                this.$data[path] = val;
            } else {
                // 动态prop
                this.$data[path] = expParser(val)(this.$parent.$data);
            }
        },
        /**
         * 注册事件及其回调函数到实例上
         * @param event {String} 事件名称
         * @param fn {Function} 事件对应的回调函数
         * @returns {This7} 实例本身
         */
        $on: function(event, fn) {
            (this._events[event] || (this._events[event] = [])).push(fn);
            return this;
        },
        /**
         * 在当前实例中触发指定的事件, 执行对应的回调函数
         * @param event {String} 事件名称
         * @param val {*} 事件所携带的参数
         * @returns {boolean} true代表事件可以继续传播, false代表事件不可继续传播
         */
        $emit: function(event, val) {
            let cbs = this._events[event];
            let shouldPropagate = true;
            if (cbs) {
                shouldPropagate = false;
                // 遍历执行事件
                let args = new Array(Array.from(arguments)[1]);
                cbs.forEach((cb) => {
                    let res = cb.apply(this, args);
                    // 就是这里, 决定了"只有当events事件返回true的时候, 事件才能在触发之后依然继续传播"
                    if (res === true) {
                        shouldPropagate = true;
                    }
                });
            }
            return shouldPropagate;
        },
        /**
         * 向上冒泡事件, 沿父链传播
         * @param event {String} 事件的名称
         * @param val {*} 事件所携带的参数
         * @returns {This7} 实例
         */
        $dispatch: function(event, val) {
            // 在当前实例中触发该事件
            let shouldPropagate = this.$emit.apply(this, arguments);
            if (!shouldPropagate) return this;
            let parent = this.$parent;
            // 遍历父链
            while (parent) {
                shouldPropagate = parent.$emit.apply(parent, arguments);
                parent = shouldPropagate ? parent.$parent : null;
            }
            return this;
        },
        /**
         * 向下广播事件, 沿子链传播
         * @param event {String} 事件的名称
         * @param val {*} 事件所携带的参数
         * @returns {This7} 实例
         */
        $broadcast: function(event, val) {
            let children = this.$children;
            let shouldPropagate = true;
            children.forEach((child) => {
                shouldPropagate = child.$emit.apply(child, arguments);
                if (shouldPropagate) {
                    child.$broadcast.apply(child, arguments);
                }
            });
            return this;
        },
        /**
         * 初始化事件events
         * @private
         */
        _initEvents: function() {
            let options = this.$options;
            registerCallbacks(this, '$on', options.events);
        }
    };
    /**
     * 设置扩展
     * @type {Object}
     */
    This7.options = {
        directives: {
            text: {
                bind: function() {},
                /**
                 * 这个就是textNode对应的更新函数啦
                 */
                update: function(value) {
                    this.el['nodeValue'] = value;
                    console.log(`更新了DOM-${this.expression}`, value);
                },
            },
            if: {
                /**
                 * 此函数在初次解析v-if节点的时候执行
                 * 作用是用一个注释节点占据原先的v-if节点位置
                 * (其实就差不多相当于:对于文本节点,就用一个空的文本节点代替他一样。
                 */
                bind: function() {
                    let el = this.el;
                    this.ref = document.createComment(`${config.prefix}-if`);
                    util.after(this.ref, el);
                    util.remove(el);
                    this.inserted = false;
                },
                /**
                 * 当v-if指令依赖的数据发生变化时触发此更新函数
                 * @param value {Boolean} true/false 表示显示还是不显示该节点
                 */
                update: function(value) {
                    if (value) {
                        // 挂载子实例
                        if (!this.inserted) {
                            if (!this.childBM) {
                                this.build();
                            }
                            this.childBM.$before(this.ref);
                            this.inserted = true;
                        }
                    } else {
                        // 卸载子实例
                        if (this.inserted) {
                            this.childBM.$remove();
                            this.inserted = false;
                        }
                    }
                },
                /**
                 * 这个build比较吊
                 * 因为对于一个 "v-if" 结构来说, 远比一个普通的文本节点要复杂。
                 * 所以对弈v-if节点不能当成普通的节点来处理, 它更像是一个子的vue实例
                 * 所以我们将整个v-if节点当成是另外一个vue实例, 然后实例化它
                 */
                build: function() {
                    this.childBM = new util.This7({
                        el: this.el,
                        parent: this.vm
                    });
                }
            },
            attr: {
                update: function(value) {
                    let name = this.arg;
                    let el = this.el;
                    el.setAttribute(name, value);
                }
            },
            on: {
                /**
                 * 绑定事件
                 * @param handler
                 */
                update: function(handler) {
                    if (typeof handler !== 'function') {
                        util.warn(`指令v-on:${this.expression}不是一个函数`);
                        return;
                    }
                    this.reset();
                    this.handler = handler;
                    this.el.addEventListener(this.arg, this.handler);
                },
                /**
                 * 解绑事件
                 */
                reset: function() {
                    if (!this.handler) return;
                    this.el.removeEventListener(this.arg, this.handler);
                }
            },
            repeat: {
                bind: function() {
                    this.id = `__b_repeat_${++uid}`;
                    this.ref = document.createComment(`${config.prefix}repeat`);
                    util.replace(this.el, this.ref);
                },
                update: function(data) {
                    if (data && !Array.isArray(data)) {
                        util.warn(`Invalid value for b-repeat:${data}\nExpects Array`);
                        return;
                    }
                    this.vms = this.diff(data || [], this.vms);
                },
                /**
                 * 这个函数非常关键, 是保证性能的核心
                 * @param data {Array} 新的数组
                 * @param oldVms {Array} 旧的实例数组
                 * @returns {Array} 新的实例数组
                 */
                diff: function(data, oldVms) {
                    let vms = new Array(data.length);
                    let ref = this.ref;
                    // 第一步,遍历新数组
                    // 如果实例是可复用的,那么在旧的实例上打_reused的标签
                    // 如果实例不是可复用的,那么新建这个实例
                    data.forEach((obj, i) => {
                        let vm = this.getVm(obj);
                        if (vm) {
                            // 可复用的实例
                            vm._reused = true;
                        } else {
                            vm = this.build(obj, i);
                        }
                        vms[i] = vm;
                        // 初始化的时候,需要将各个vm插入到DOM中
                        if (!oldVms) {
                            vm.$before(ref);
                        }
                    });
                    // 如果第一次执行diff,也就是初始化, 那么程序到这儿就终止了。
                    if (!oldVms) return vms;
                    // 第二步,遍历旧的实例数组,删除那些没有被打上_reused标签的实例
                    oldVms.forEach((oldVm) => {
                        if (oldVm._reused) return;
                        oldVm.$remove();
                    });
                    // 第三步(最后一步),
                    // 移动/插入新的实例到正确的位置
                    for (let l = vms.length, i = l - 1; i >= 0; i--) {
                        let vm = vms[i];
                        let targetNext = vms[i + 1];
                        if (!targetNext) {
                            // 这是最后的一个实例
                            if (!vm._reused) vm.$before(ref);
                        } else {
                            if (vm._reused) {
                                // 可复用实例
                                // 如果当前的下一个兄弟节点不是目标顺序中的兄弟节点
                                // 那么重新移动排序
                                if (targetNext.$el !== vm.$el.nextSibling) {
                                    vm.$before(targetNext.$el);
                                }
                            } else {
                                vm.$before(targetNext.$el);
                            }
                        }
                    }
                    vms.forEach((vm) => {
                        vm._reused = false;
                    })
                    return vms;
                },
                /**
                 * 当之前没有这个实例的时候,就得新建这个实例
                 * @param data {Object}
                 * @returns {*|This7}
                 */
                build: function(data) {
                    // 处理别名
                    let alias = this.arg;
                    let d = {};
                    if (alias) {
                        d[alias] = data;
                    }
                    let vm = new util.This7({
                        el: this.el.cloneNode(true),
                        data: d,
                        parent: this.vm
                    });
                    this.cacheVm(data, vm);
                    return vm;
                },
                /**
                 * 根据data取原有的vm实例
                 * 其实就是把repeat实例的id存储到对应的数据的id字段上
                 * 这样就知道某个数据是否是对应之前的某个实例
                 * @param data {Object}
                 * @returns {*}
                 */
                getVm: function(data) {
                    return data[this.id];
                },
                /**
                 * 将vm缓存到数据里面去, 方面getVm获取
                 * @param data {Object}
                 * @param vm {This7}
                 */
                cacheVm: function(data, vm) {
                    data[this.id] = vm;
                }
            },
            model: {
                bind: function() {
                    let el = this.el;
                    let tag = el.tagName;
                    let handler;
                    if (tag === 'INPUT') {
                        handler = {
                            bind: function() {
                                let el = this.el;
                                this.handler = () => {
                                    this.vm.$set(this.expression, el.value);
                                };
                                _.on(el, 'input', this.handler);
                            },
                            update: function(value) {
                                this.el.value = value;
                            }
                        };
                    } else {
                        _.warn(`v-model doesn't support element type: ${tag}`);
                        return;
                    }
                    handler.bind.call(this);
                    this.update = handler.update;
                    this.unbind = handler.unbind;
                }
            },
            component: {
                bind: function() {
                    if (!this.el.__this7__) {
                        // 判断该组件是否已经被挂载
                        this.anchor = document.createComment(`${config.prefix}component`);
                        util.replace(this.el, this.anchor);
                        this.setComponent(this.expression);
                    }
                },
                update: function() {},
                /**
                 * @param value {String} 组件标签名, 如 "my-component"
                 */
                setComponent: function(value) {
                    if (value) {
                        this.Component = this.vm.$options.components[value];
                        this.ComponentName = value;
                        this.mountComponent();
                    }
                },
                /**
                 * 构建、挂载组件实例
                 */
                mountComponent: function() {
                    let newComponent = this.build();
                    newComponent.$before(this.anchor);
                },
                /**
                 * 构建组件实例
                 * @returns {This7Component}
                 */
                build: function() {
                    if (this.Component) {
                        let options = {
                            name: this.ComponentName,
                            el: this.el.cloneNode(),
                            parent: this.vm,
                            isComponent: true
                        };
                        let child = new this.Component(options);
                        return child;
                    }
                }
            },
            prop: {
                bind: function() {
                    // 初始化动态prop
                    // this.arg == "name"; this.expression == "user.name", true代表是动态prop
                    this.vm.initProp(this.arg, this.expression, true);
                },
                update: function(value) {
                    // 设置组件的$data, 此操作会引发数据的notify
                    this.vm.$set(this.arg, value);
                }
            }
        },
        components: {}
    };
    installGlobalAPI(This7);
    window.This7 = _.This7 = This7;
    return This7;
})();