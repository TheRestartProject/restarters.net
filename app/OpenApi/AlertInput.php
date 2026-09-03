<?php

namespace App\OpenApi;

/**
 * Reusable OpenAPI request-body schema for the alert admin endpoints
 * (PUT /api/v2/alerts and PATCH /api/v2/alerts/{id}).
 *
 * Both AlertController::addAlertv2 and updateAlertv2 accept the same
 * multipart form fields, so the schema is defined once here and referenced
 * from both. See app/OpenApi/Responses.php for the equivalent pattern used
 * for the shared error responses.
 *
 * This class holds no runtime logic; it exists purely as an annotation host
 * scanned by darkaonline/l5-swagger (config/l5-swagger.php scans base_path('app')).
 *
 * @OA\Schema(
 *     schema="AlertInput",
 *     title="AlertInput",
 *     description="Fields accepted when creating or editing a site alert.",
 *     required={"start", "end", "title", "html"},
 *     @OA\Property(
 *         property="start",
 *         type="string",
 *         format="date-time",
 *         description="When the alert starts showing (ISO-8601, e.g. 2001-01-01T00:00:00Z).",
 *         example="2001-01-01T00:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="end",
 *         type="string",
 *         format="date-time",
 *         description="When the alert stops showing (ISO-8601).",
 *         example="2038-01-01T02:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         maxLength=255,
 *         description="The alert title.",
 *         example="Scheduled maintenance"
 *     ),
 *     @OA\Property(
 *         property="html",
 *         type="string",
 *         description="The alert body (HTML).",
 *         example="<p>The site will be down briefly this evening.</p>"
 *     ),
 *     @OA\Property(
 *         property="ctatitle",
 *         type="string",
 *         maxLength=255,
 *         nullable=true,
 *         description="Optional call-to-action button label.",
 *         example="Learn more"
 *     ),
 *     @OA\Property(
 *         property="ctalink",
 *         type="string",
 *         format="uri",
 *         nullable=true,
 *         description="Optional call-to-action button URL.",
 *         example="https://therestartproject.org"
 *     )
 * )
 */
class AlertInput
{
}
