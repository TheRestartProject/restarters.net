import { EventProcessor, Hub, Integration } from '@sentry/types';
/** Deduplication filter */
export declare class Dedupe implements Integration {
    /**
     * @inheritDoc
     */
    static id: string;
    /**
     * @inheritDoc
     */
    name: string;
    /**
     * @inheritDoc
     */
    private _previousEvent?;
    /**
     * @inheritDoc
     */
    setupOnce(addGlobalEventProcessor: (callback: EventProcessor) => void, getCurrentHub: () => Hub): void;
    /** JSDoc */
    private _shouldDropEvent;
    /** JSDoc */
    private _isSameMessageEvent;
    /** JSDoc */
    private _getFramesFromEvent;
    /** JSDoc */
    private _isSameStacktrace;
    /** JSDoc */
    private _getExceptionFromEvent;
    /** JSDoc */
    private _isSameExceptionEvent;
    /** JSDoc */
    private _isSameFingerprint;
}
//# sourceMappingURL=dedupe.d.ts.map