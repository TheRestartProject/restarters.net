<?php

namespace App\Http\Controllers;

use App\Helpers\Tus;
use Symfony\Component\HttpFoundation\Response;

class TusController extends Controller
{
    /**
     * Handle every tus protocol verb (POST/HEAD/PATCH/DELETE/OPTIONS/GET) at /api/tus
     * and /api/tus/{key}. This route is intentionally NOT behind auth:api - the tus
     * protocol's chunked PATCH requests are not a good fit for carrying an API token
     * (see routes/api.php and VerifyCsrfToken::$except for the matching exemptions),
     * so this endpoint only assembles anonymous, temporary uploads. The uploaded file
     * is not treated as trusted/attached to anything until a subsequent authenticated
     * call (e.g. API\UserController::updateMyPhotov2) references its upload key.
     */
    public function serve(): Response
    {
        $server = Tus::buildServer();

        $response = $server->serve();

        // Tus is a cross-origin-friendly protocol by design (resumable uploads from
        // browser JS); make sure the handful of custom response headers clients need
        // to read (to resume/complete an upload) are actually exposed to them.
        $response->headers->set(
            'Access-Control-Expose-Headers',
            'Location, Upload-Offset, Upload-Length, Upload-Expires, Tus-Resumable, Tus-Version, Tus-Extension, Tus-Max-Size, Tus-Checksum-Algorithm'
        );

        return $response;
    }
}
