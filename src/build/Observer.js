/*
 * @Author: qinuoyun
 * @Date:   2018-02-27 15:45:52
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-02-28 15:01:25
 */
var Observer = (function(a) {
    let objectAugmentations = {},
        _ = new Util();
    let uid = 0;
    /**
     * 类常理
     * @type {Number}
     */
    const ARRAY = 0,
        OBJECT = 1,
        aryMethods = ['push', 'pop', 'shift', 'unshift', 'splice', 'sort', 'reverse'],
        arrayAugmentations = []
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
    function observer(value, type) {
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
    observer.prototype.walk = function(obj) {
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
    observer.prototype.convert = function(key, val) {
        let ob = this;
        Object.defineProperty(this.value, key, {
            enumerable: true,
            configurable: true,
            get: function() {
                if (observer.emitGet) {
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
    observer.prototype.observe = function(key, val) {
        let ob = observer.create(val);
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
    observer.prototype.link = function(items) {
        items.forEach((value, index) => {
            this.observe(index, value);
        });
    };
    /**
     * 订阅事件
     * @param event {string} 事件类型
     * @param fn {Function} 对调函数
     * @returns {observer} 观察者对象
     */
    observer.prototype.on = function(event, fn) {
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
     * @returns {observer} 观察者对象
     */
    observer.prototype.off = function(event, fn) {
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
    observer.prototype.notify = function(event, path, val) {
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
    observer.prototype.emit = function(event, path, val) {
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
     * @returns {observer}
     */
    observer.create = function(value) {
        if (Array.isArray(value)) {
            return new observer(value, ARRAY);
        } else if (typeof value === 'object') {
            return new observer(value, OBJECT);
        }
    };
    observer.demo = function function_name(argument) {
        console.log(argument);
    }
    return observer;
})();