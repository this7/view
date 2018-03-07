/*
* @Author: qinuoyun
* @Date:   2018-02-27 15:06:59
* @Last Modified by:   qinuoyun
* @Last Modified time: 2018-02-27 15:08:10
*/
var Binding = (function() {
    /**
     * binding构造函数
     * @constructor
     */
    function binding() {
        // 用来存放各种watcher实例
        this._subs = [];
    }
    /**
     * 给你一个键值, 如果原来就有这个键值对应的binding, 那么乖乖返回就好了
     * 否则就新建一个再返回
     * @param key {string} 形如: "name"
     * @returns {binding}
     * @private
     */
    binding.prototype._addChild = function(key) {
        return this[key] || new binding();
    };
    /**
     * 这个就是把watcher塞到_subs数组里面啦
     * 之后触发update的话,是会遍历这个数组滴
     * @param sub {Watcher} 观察容器
     * @private
     */
    binding.prototype._addSub = function(sub) {
        this._subs.push(sub);
    };
	return binding;
})();