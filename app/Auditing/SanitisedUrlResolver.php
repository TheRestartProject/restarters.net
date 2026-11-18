<?php

namespace App\Auditing;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Resolvers\UrlResolver;

/**
 * Strips the query string from the URL stored on every audit record.
 *
 * SECURITY: the package's own resolver stores Request::fullUrl(), and this API
 * accepts `?api_token=` authentication - so every audited write by a
 * token-authenticated caller wrote a VALID API TOKEN into the audits table,
 * where it is retained indefinitely and rendered to any Administrator opening
 * an audit log. Confirmed against a live request before this was added.
 *
 * A query string on an audit URL carries no information worth that risk: the
 * record already has the model, the user, the timestamp and the field-level
 * diff. So the whole query string goes, rather than filtering `api_token`
 * specifically - anything else sensitive that ever appears there (a reset
 * token, a signed-URL signature) is covered by the same rule without needing
 * to be predicted.
 */
class SanitisedUrlResolver extends UrlResolver
{
    public static function resolve(Auditable $auditable): string
    {
        $url = parent::resolve($auditable);

        // Console runs return "Command: ..." rather than a URL - leave those be.
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return strtok($url, '?');
    }
}
