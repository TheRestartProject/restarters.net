<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class StatsShareImageController extends Controller
{
    // Filenames client/app/composables/useStatsShareImage.js's CO2e -> image
    // lookup table can actually produce (ImpactRange{1-6}{Landscape,Square}-
    // {increment}.png), plus WavyDividerLine.png, the one fixed decoration
    // image painted onto every share image. This is a strict allowlist, not
    // sanitisation: $filename comes straight from the URL, so anything not
    // matching this pattern is rejected before the filesystem is touched at
    // all - stripping '../' or similar would still leave room for a miss.
    private const ALLOWED_PATTERN = '/^(ImpactRange[1-6](Landscape|Square)-\d+\.png|WavyDividerLine\.png)$/';

    /**
     * @OA\Get(
     *      path="/api/v2/stats/share-image/{filename}",
     *      operationId="getStatsShareImagev2",
     *      tags={"Devices"},
     *      summary="Background/decoration image for the canvas-painted social-share image",
     *      description="Public. Unlike the rest of public/ (which sits outside config/cors.php's 'api/*' scope), this is reachable with CORS headers, so the Nuxt client can draw it onto a <canvas> and export the result via toDataURL()/toBlob() without tainting the canvas. Serves only the fixed set of PNGs useStatsShareImage.js's CO2e->image lookup table can produce, plus WavyDividerLine.png - filename is checked against an allowlist pattern (not sanitised) before the filesystem is touched; anything else, or a path that resolves outside the images/stats directory, 404s.",
     *      @OA\Parameter(
     *          name="filename",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="string", pattern="^(ImpactRange[1-6](Landscape|Square)-\d+\.png|WavyDividerLine\.png)$"),
     *          example="ImpactRange2Landscape-10.png"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="The PNG image",
     *          @OA\MediaType(
     *              mediaType="image/png",
     *              @OA\Schema(type="string", format="binary")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Unknown, invalid, or missing filename")
     * )
     */
    public function shareImagev2(string $filename): Response
    {
        if (! preg_match(self::ALLOWED_PATTERN, $filename)) {
            abort(404);
        }

        $baseDir = realpath(public_path('images/stats'));

        if ($baseDir === false) {
            abort(404);
        }

        // realpath() collapses any '../' and resolves symlinks; it also
        // returns false outright if the file doesn't exist, so a missing
        // file 404s here too. Belt-and-braces on top of the allowlist above:
        // confirm the fully-resolved path still sits inside $baseDir before
        // serving anything from it.
        $path = realpath($baseDir . DIRECTORY_SEPARATOR . $filename);

        if ($path === false || strncmp($path, $baseDir . DIRECTORY_SEPARATOR, strlen($baseDir) + 1) !== 0) {
            abort(404);
        }

        // A plain in-memory Response rather than Laravel's file()/
        // BinaryFileResponse helper: these images are at most a few hundred
        // KB (nowhere near worth streaming), and BinaryFileResponse writes
        // its body straight to the output buffer on send() rather than
        // exposing it via getContent() - which makes it awkward to assert
        // against in tests and gains nothing at this size.
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'image/png',
            // Every request for a given filename returns byte-identical
            // content (the images are static, checked-in assets) - cache
            // aggressively.
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
