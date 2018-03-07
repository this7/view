/*
 * @Author: qinuoyun
 * @Date:   2018-03-01 13:38:42
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-03-06 09:40:06
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
            // View构造函数上定义了一些指令相关的方法,需要将它们引用过来, 以供后面的调用
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
            this.$el.__View__ = this;
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
         * 插入View实例
         * @param target {Element}
         */
        $before: function(target) {
            _.before(this.$el, target);
        },
        /**
         * 移除View实例
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
         * @returns {View} 实例本身
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
         * @returns {View} 实例
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
         * @returns {View} 实例
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
    view.options = {
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
                    _.after(this.ref, el);
                    _.remove(el);
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
                    this.childBM = new _.view({
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
                        _.warn(`指令v-on:${this.expression}不是一个函数`);
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
                    _.replace(this.el, this.ref);
                },
                update: function(data) {
                    if (data && !Array.isArray(data)) {
                        _.warn(`Invalid value for b-repeat:${data}\nExpects Array`);
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
                    let vm = new _.view({
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
                        _.replace(this.el, this.anchor);
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
    //installGlobalAPI(view);
    window.view = _.view = view;
    return view;
})();