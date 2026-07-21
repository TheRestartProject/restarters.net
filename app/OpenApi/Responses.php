<?php

namespace App\OpenApi;

/**
 * Reusable OpenAPI response components shared across the API.
 *
 * These mirror the uniform JSON error envelopes rendered by
 * app/Exceptions/Handler.php so that individual operations can
 * reference them with `@OA\Response(response=401, ref="#/components/responses/Unauthenticated")`
 * instead of hand-copying the same inline block. Includes 429, the
 * `throttle:auth` outcome.
 *
 * This class holds no runtime logic; it exists purely as an annotation host
 * scanned by darkaonline/l5-swagger (config/l5-swagger.php scans base_path('app')).
 *
 * @OA\Response(
 *     response="Unauthenticated",
 *     description="No valid Sanctum bearer token was supplied.",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Unauthenticated.")
 *     )
 * )
 *
 * @OA\Response(
 *     response="Forbidden",
 *     description="The authenticated user is not permitted to perform this action.",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="This action is unauthorized.")
 *     )
 * )
 *
 * @OA\Response(
 *     response="NotFound",
 *     description="The requested resource does not exist.",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Not found.")
 *     )
 * )
 *
 * @OA\Response(
 *     response="ValidationError",
 *     description="The request payload failed validation.",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="The given data was invalid."),
 *         @OA\Property(
 *             property="errors",
 *             type="object",
 *             description="Field-keyed arrays of validation messages.",
 *             additionalProperties=@OA\AdditionalProperties(
 *                 type="array",
 *                 @OA\Items(type="string")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="TooManyRequests",
 *     description="Rate limit exceeded (login/register/password endpoints are gated by throttle:auth).",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Too Many Attempts.")
 *     )
 * )
 */
class Responses
{
}
