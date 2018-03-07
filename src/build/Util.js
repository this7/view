/*
 * @Author: qinuoyun
 * @Date:   2018-02-27 14:25:05
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-02-28 13:58:47
 */
var Util = (function() {
    //构造函数
    function util() {}
    /**
     * Demo测试代码
     * @returns {console}
     */
    util.prototype.demo = function() {
        console.log("测试代码");
        this.test("你好");
    };
    /**
     * test测试代码
     * @returns {console}
     */
    util.prototype.test = function(a) {
        console.log(a);
    };
    /**
     * 定义对象属性
     * @param obj {Object} 对象
     * @param key {String} 键值
     * @param val {*} 属性值
     * @param enumerable {Boolean} 是否可枚举
     */
    util.prototype.define = function(obj, key, val, enumerable) {
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
    util.prototype.extend = function(to, from) {
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
    util.prototype.proxy = function(to, from, key) {
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
    util.prototype.before = function(el, target) {
        target.parentNode.insertBefore(el, target);
    };
    /**
     * 因为没有原声的insertAfter方法, 所以需要迂回处理一下
     * @param el
     * @param target
     */
    util.prototype.after = function(el, target) {
        if (target.nextSibling) {
            this.before(el, target.nextSibling);
        } else {
            target.parentNode.appendChild(el);
        }
    };
    /**
     * removeSelf
     * @param el {Element}
     */
    util.prototype.remove = function(el) {
        el.parentNode.removeChild(el);
    };
    /**
     * 用新的节点代替旧的节点
     * @param target {Element} 旧节点
     * @param el {Element} 新节点
     */
    util.prototype.replace = function(target, el) {
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
    util.prototype.attr = function(node, attr) {
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
    util.prototype.on = function(el, event, cb) {
        el.addEventListener(event, cb);
    };
    /**
     * 获取动态数据绑定属性值,
     * 比如 b-bind:name="user.name" 和 :name="user.name"
     * @param node {Element}
     * @param name {String} 属性名称 比如"name"
     * @returns {string} 属性值
     */
    util.prototype.getBindAttr = function(node, name) {
        return this.getAttr(node, `:${name}`) || this.getAttr(node, `${config.prefix}bind:${name}`);
    };
    /**
     * 获取节点属性值,并且移除该属性
     * @param node {Element}
     * @param attr {String}
     * @returns {string}
     */
    util.prototype.getAttr = function(node, attr) {
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
    util.prototype.warn = function() {
        console.warn.apply(console, arguments);
    };
    /**
     * 合并对象
     * @param  {Object} parent 原始对象
     * @param  {Object} child  需要合并项
     * @return {Object}        返回对象合集
     */
    util.prototype.mergeOptions = function(parent, child) {
        return Object.assign({}, parent, child);
    };
    /**
     * 返回对应键名
     * @param  {String} val 键值
     * @return {Array}     返回数组
     */
    util.prototype.indexOf = function(val) {
        for (var i = 0; i < this.length; i++) {
            if (this[i] == val) return i;
        }
        return -1;
    };
    return util;
})();