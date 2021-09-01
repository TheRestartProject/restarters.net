/** A simple queue that holds promises. */
export declare class PromiseBuffer<T> {
    protected _limit?: number | undefined;
    /** Internal set of queued Promises */
    private readonly _buffer;
    constructor(_limit?: number | undefined);
    /**
     * Says if the buffer is ready to take more requests
     */
    isReady(): boolean;
    /**
     * Add a promise (representing an in-flight action) to the queue, and set it to remove itself on fulfillment.
     *
     * @param taskProducer A function producing any PromiseLike<T>; In previous versions this used to be `task:
     *        PromiseLike<T>`, but under that model, Promises were instantly created on the call-site and their executor
     *        functions therefore ran immediately. Thus, even if the buffer was full, the action still happened. By
     *        requiring the promise to be wrapped in a function, we can defer promise creation until after the buffer
     *        limit check.
     * @returns The original promise.
     */
    add(taskProducer: () => PromiseLike<T>): PromiseLike<T>;
    /**
     * Remove a promise from the queue.
     *
     * @param task Can be any PromiseLike<T>
     * @returns Removed promise.
     */
    remove(task: PromiseLike<T>): PromiseLike<T>;
    /**
     * This function returns the number of unresolved promises in the queue.
     */
    length(): number;
    /**
     * Wait for all promises in the queue to resolve or for timeout to expire, whichever comes first.
     *
     * @param timeout The time, in ms, after which to resolve to `false` if the queue is still non-empty. Passing `0` (or
     * not passing anything) will make the promise wait as long as it takes for the queue to drain before resolving to
     * `true`.
     * @returns A promise which will resolve to `true` if the queue is already empty or drains before the timeout, and
     * `false` otherwise
     */
    drain(timeout?: number): PromiseLike<boolean>;
}
//# sourceMappingURL=promisebuffer.d.ts.map