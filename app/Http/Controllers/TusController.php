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
     *
     * @OA\Post(
     *      path="/api/tus",
     *      operationId="tusCreateUpload",
     *      tags={"Tus (resumable upload)"},
     *      summary="Create a resumable upload (tus.io protocol)",
     *      description="Implements the tus.io resumable-upload protocol (server: ankitpokhrel/tus-php, see App\Helpers\Tus::buildServer()). **Deliberately UNAUTHENTICATED**: no Sanctum/API token is required or checked, and the route is exempt from CSRF verification (VerifyCsrfToken::$except), because tus clients (e.g. Uppy) cannot attach either to their chunked PATCH requests - see the class-level comment on TusController::serve(). Anyone who can reach this endpoint can create an anonymous, temporary upload; the file only becomes attached to a user once a separate, authenticated call (e.g. POST /api/v2/users/me/photo) later references its upload key. The only abuse constraint evident in the code is a 10MB max upload size enforced server-side; there is no rate limiting, no per-IP/per-session quota, and no automatic purge of abandoned uploads implemented in this codebase.",
     *      @OA\Parameter(name="Upload-Length", in="header", required=true, description="Total size in bytes of the upload being created.", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="Tus-Resumable", in="header", required=true, @OA\Schema(type="string", example="1.0.0")),
     *      @OA\Response(
     *          response=201,
     *          description="Upload created.",
     *          @OA\Header(header="Location", description="URL (including upload key) to PATCH subsequent chunks to, e.g. /api/tus/{key}.", @OA\Schema(type="string"))
     *      ),
     *      @OA\Response(response=413, description="Upload-Length exceeds the 10MB server-side cap set in App\Helpers\Tus::buildServer().")
     * )
     *
     * @OA\Patch(
     *      path="/api/tus/{key}",
     *      operationId="tusUploadChunk",
     *      tags={"Tus (resumable upload)"},
     *      summary="Append a chunk to a resumable upload (tus.io protocol)",
     *      description="Deliberately UNAUTHENTICATED - see the description on POST /api/tus and the class-level comment on TusController::serve(). The upload key is an opaque identifier, not scoped to any session/owner; anyone holding it can append to or otherwise manipulate that upload.",
     *      @OA\Parameter(name="key", in="path", required=true, description="Upload key returned in the Location header of the initial POST.", @OA\Schema(type="string")),
     *      @OA\Parameter(name="Upload-Offset", in="header", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="Content-Type", in="header", required=true, @OA\Schema(type="string", example="application/offset+octet-stream")),
     *      @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/offset+octet-stream", @OA\Schema(type="string", format="binary"))),
     *      @OA\Response(response=204, description="Chunk accepted; Upload-Offset response header reflects the new byte offset."),
     *      @OA\Response(response=404, description="Unknown upload key."),
     *      @OA\Response(response=409, description="Upload-Offset header does not match the server's current offset for this upload.")
     * )
     *
     * @OA\Head(
     *      path="/api/tus/{key}",
     *      operationId="tusUploadStatus",
     *      tags={"Tus (resumable upload)"},
     *      summary="Get the current offset of a resumable upload (tus.io protocol)",
     *      description="Used by clients to resume an interrupted upload. Deliberately UNAUTHENTICATED - see the description on POST /api/tus.",
     *      @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Upload-Offset and Upload-Length response headers reflect current progress."),
     *      @OA\Response(response=404, description="Unknown upload key.")
     * )
     *
     * @OA\Delete(
     *      path="/api/tus/{key}",
     *      operationId="tusDeleteUpload",
     *      tags={"Tus (resumable upload)"},
     *      summary="Abort/discard a resumable upload (tus.io protocol)",
     *      description="Deliberately UNAUTHENTICATED - see the description on POST /api/tus. Any caller who knows (or guesses) an upload key can delete it; keys are not validated against a session or owner.",
     *      @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=204, description="Upload discarded."),
     *      @OA\Response(response=404, description="Unknown upload key.")
     * )
     *
     * @OA\Options(
     *      path="/api/tus",
     *      operationId="tusOptions",
     *      tags={"Tus (resumable upload)"},
     *      summary="Discover tus protocol server capabilities (tus.io protocol)",
     *      description="Deliberately UNAUTHENTICATED, carries no request/response body. Used by clients to read the server's supported Tus-Version/Tus-Extension/Tus-Max-Size capabilities before starting an upload.",
     *      @OA\Response(response=204, description="No content; capabilities are returned via Tus-Resumable/Tus-Version/Tus-Extension/Tus-Max-Size response headers.")
     * )
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
