/* Shared, bounded work scheduling for both 3D Map entry points. */
(function (root) {
    'use strict';
    class WorkQueue {
        constructor(limit) { this.limit = limit; this.active = 0; this.waiting = []; }
        run(work) {
            return new Promise((resolve, reject) => {
                this.waiting.push({ work, resolve, reject });
                this.pump();
            });
        }
        pump() {
            while (this.active < this.limit && this.waiting.length) {
                const task = this.waiting.shift();
                this.active++;
                Promise.resolve().then(task.work).then(task.resolve, task.reject).finally(() => { this.active--; this.pump(); });
            }
        }
    }
    class MaterialBatcher {
        constructor(send, cache, limit = 2, batchSize = 256) {
            this.send = send; this.cache = cache; this.batchSize = batchSize;
            this.queue = new WorkQueue(limit); this.pending = new Map(); this.waiting = [];
            this.scheduled = false;
        }
        load(entries) {
            const promises = [...new Set(entries.map(x => String(x || '').trim()).filter(Boolean))].map(entry => {
                if (this.cache.has(entry)) return Promise.resolve(this.cache.get(entry));
                if (this.pending.has(entry)) return this.pending.get(entry);
                const promise = new Promise((resolve, reject) => this.waiting.push({ entry, resolve, reject }));
                this.pending.set(entry, promise);
                return promise;
            });
            if (!this.scheduled && this.waiting.length) {
                this.scheduled = true;
                queueMicrotask(() => this.flush());
            }
            return Promise.all(promises);
        }
        flush() {
            this.scheduled = false;
            while (this.waiting.length) {
                const batch = this.waiting.splice(0, this.batchSize);
                this.queue.run(async () => {
                    try {
                        const results = await this.send(batch.map(x => x.entry));
                        if (!Array.isArray(results) || results.length !== batch.length) throw new Error('Incomplete material batch.');
                        batch.forEach((item, index) => {
                            const value = results[index]?.ok === true ? results[index] : null;
                            this.cache.set(item.entry, value);
                            this.pending.delete(item.entry);
                            item.resolve(value);
                        });
                    } catch (error) {
                        batch.forEach(item => { this.pending.delete(item.entry); item.reject(error); });
                    }
                });
            }
        }
    }
    const api = { WorkQueue, MaterialBatcher };
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.DG3DPipeline = api;
})(typeof window === 'undefined' ? {} : window);
