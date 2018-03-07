/*
 * @Author: qinuoyun
 * @Date:   2018-02-27 14:25:05
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-02-27 15:03:58
 */
var Batcher = (function() {
    /**
     * 批处理构造函数
     * @constructor
     */
    function batcher() {
        this.reset();
    }
    /**
     * 批处理充值
     */
    batcher.prototype.reset = function() {
        this.has = {};
        this.queue = [];
        this.waiting = false;
    };
    /**
     * 将事件添加到队列中
     * @param job {Watcher} watcher事件
     */
    batcher.prototype.push = function(job) {
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
    batcher.prototype.flush = function() {
        this.queue.forEach((job) => {
            // job.cb.call(job.ctx);
            job.run();
        });
        this.reset();
    };
    return batcher;
})();