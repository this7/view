/*
 * @Author: qinuoyun
 * @Date:   2018-02-27 15:06:32
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-03-06 19:19:57
 */
var Watcher = (function() {
    let batcher = new Batcher(),
        uid = 0;
       /**
     * watcher构造函数
     * 有什么用呢这个东西?两个用途
     * 1. 当指令对应的数据发生改变的时候, 执行更新DOM的update函数
     * 2. 当$watch API对应的数据发生改变的时候, 执行你自己定义的回调函数
     * @param vm {This7} This7实例
     * @param expression {String} 表达式, 例如: "user.name"
     * @param cb {Function} 当对应的数据更新的时候执行的回调函数
     * @param ctx {Object} 回调函数执行上下文
     * @constructor
     */
    function watcher(vm, expression, cb, ctx) {
        console.log("watcher",[vm, expression, cb, ctx])
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
    watcher.prototype.initDeps = function(path) {
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
    watcher.prototype.addDep = function(path) {
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
    watcher.prototype.update = function() {
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
    watcher.prototype.beforeGet = function() {
        Observer.emitGet = true;
        this.vm._activewatcher = this;
    };
    /**
     * getter.call是完成计算属性的核心,
     * 因为正是这里的getter.call, 执行了该计算属性的getter方法,
     * 从而执行该计算属性所依赖的原始原型的get方法
     * 从而发出get事件,冒泡到底层, 触发collectDep事件
     */
    watcher.prototype.get = function() {
        this.beforeGet();
        let value = this.getter.call(this.vm, this.vm);
        this.afterGet();
        return value;
    };
    /**
     * 在调用属性的getter之后调用
     * 作用是关闭某些开关
     */
    watcher.prototype.afterGet = function() {
        Observer.emitGet = false;
        this.vm._activewatcher = null;
    };
    /**
     * 为watcher添加一个run方法, 此方法调用回调函数
     * 之前是直接在bathcer的flush函数里面调用cb
     * 但是这样传递参数的问题不好处理
     * 所以为了将属性变化前后的值传递给cb
     * 弄一个run函数更好一些
     */
    watcher.prototype.run = function() {
        let value = this.get();
        let oldValue = this.value;
        this.value = value;
        this.cb.call(this.ctx, value, oldValue);
    };
    return watcher;
})();