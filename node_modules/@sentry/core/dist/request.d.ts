import { Event, SentryRequest, Session, SessionAggregates } from '@sentry/types';
import { API } from './api';
/** Creates a SentryRequest from a Session. */
export declare function sessionToSentryRequest(session: Session | SessionAggregates, api: API): SentryRequest;
/** Creates a SentryRequest from an event. */
export declare function eventToSentryRequest(event: Event, api: API): SentryRequest;
//# sourceMappingURL=request.d.ts.map