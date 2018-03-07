/*
 * @Author: qinuoyun
 * @Date:   2018-02-28 20:22:53
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-03-06 17:32:18
 */

let _v = {};

function o(options) {
    for (v in options) {
        this[v] = options[v]
    }
    var v = new View("#app", this);
}

function Page(options) {
    o.prototype = {
        setData: function() {
            console.log("测试setData", this);
        }
    };
    var View = new o(options);
    console.log(View);
    return View;
}

