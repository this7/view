/*
 * @Author: qinuoyun
 * @Date:   2018-02-27 15:06:41
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-03-06 16:28:49
 */
var Directive = (function() {
    let _ = new Util();
    /**
     * 指令构造函数
     * @param name {string} 例如:text, 代表是文本节点
     * @param el {Element} 对应的文本节点
     * @param vm {This7} This7实例
     * @param descriptor {Object} 指令描述符, 描述一个指令, 形如: {expression: "user.name"}
     * @constructor
     */
    function directive(name, el, vm, descriptor) {
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
    directive.prototype._bind = function() {
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
    directive.prototype._initDef = function() {
        let directives = {
            text: {
                bind: function() {},
                /**
                 * 这个就是textNode对应的更新函数啦
                 */
                update: function(value) {
                    console.log("指令",this)
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
        };
        let def = directives[this.name];
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
    directive.prototype._update = function(value, oldValue) {
        this.update(value, oldValue);
    };
    return directive;
})();