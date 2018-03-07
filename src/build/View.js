/*
 * @Author: qinuoyun
 * @Date:   2018-03-01 13:38:42
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-03-06 19:23:41
 */
var View = (function() {
    /**
     * 变量
     * @type {Util}
     */
    let _ = new Util(),
        uid = 0;
    /**
     * 常量
     * @type {Number}
     */
    const priorityDirs = ['if', 'repeat'];
    /**
     * view构造函数
     * @constructor
     */
    function view(element, options) {
        for (v in options) {
            this[v] = options[v]
        }
        this._init(element, options);
    }
    /**
     * 静态共有属性方法
     * @type {Object}
     */
    view.prototype = {
        constructor: view,
        /**
         * 实例初始化入口
         * @param options {Object} View实例选项
         * @private
         */
        _init: function(element, options) {
            console.log("初始化", [element, options]);
            this.$children = [];
            this.$data = options.data;
            // 初始化data, 主要是做Observer,数据监听这一块
            this._initData(this.$data);
            // binding、watcher、directive是实现动态数据绑定的三大核心对象
            this._initBindings();
            // 指令数组,用于存放解析DOM模板的时候生成的指令
            this._directives = [];
            // 解析DOM模板, 渲染真实的DOM
            this._compile(element);
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
         * 就是在这里定于数据对象的变化的
         * @private
         */
        _initBindings: function() {
            this._rootBinding = new Binding();

            this.observer.on('set', this._updateBindingAt.bind(this))
                .on('get', this._collectDep.bind(this));
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
         * 整体思路: 利用递归的思想
         */

        _compile: function(el) {
            this.$el = transclude(el);
            this._compileNode(this.$el);
        },
        /**
         * 编译节点
         * @param  {[type]} node [description]
         * @return {[type]}      [description]
         */
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
         * 渲染节点
         * @param node {Element}
         * @private
         */
        _compileElement: function(node) {
            // // 判断节点是否是组件指令
            // if (this._checkComponentDirs(node)) {
            //     return;
            // }

            // let hasAttributes = node.hasAttributes();

            // // 解析高优指令
            // if (hasAttributes && this._checkPriorityDirs(node)) {
            //     return;
            // }

            // // 解析属性
            // if (hasAttributes) {
            //     this._compileAttrs(node);
            // }

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

    };
    window.view = _.view = view;
    return view;
})();