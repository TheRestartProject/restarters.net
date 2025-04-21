<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Alert",
 *     schema="Alert",
 *     description="An alert shown to users onsite.",
 *     required={"id", "title", "html", "start", "end"},
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this alert",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="title",
 *          title="title",
 *          description="The title of this alert",
 *          format="string",
 *          example="Support our work!"
 *     ),
 *     @OA\Property(
 *          property="html",
 *          title="html",
 *          description="HTML body of the alert",
 *          format="string",
 *     ),
 *     @OA\Property(
 *          property="ctatitle",
 *          title="ctatitle",
 *          description="(Optional) The text for a Call To Action button.",
 *          format="string",
 *          example="Double Your Donation Now"
 *     ),
 *     @OA\Property(
 *          property="variant",
 *          title="variant",
 *          description="(Optional) The alert variant (default is secondary).",
 *          format="string",
 *          example="secondary"
 *     ),
 *     @OA\Property(
 *          property="ctalink",
 *          title="ctalink",
 *          description="(Optional) The link for the button to direct to.",
 *          format="string",
 *          example="https://www.paypal.com/gb/fundraiser/charity/61071"
 *     ),
 *     @OA\Property(
 *          property="start",
 *          title="start",
 *          description="Start showing the alert at this time, in ISO8601 format.",
 *          format="date-time",
 *          example="2022-09-18T11:30:00+00:00"
 *     ),
 *     @OA\Property(
 *          property="end",
 *          title="end",
 *          description="Stop showing the alert at this time, in ISO8601 format.",
 *          format="date-time",
 *          example="2022-09-18T12:30:00+00:00"
 *     )
 * )
 */
class Alert extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $ret = [
            'id' => $this->id,
            'title' => $this->title,
            'html' => $this->html,
            'ctatitle' => $this->ctatitle,
            'ctalink' => $this->ctalink,
            'start' => Carbon::parse($this->start)->toIso8601String(),
            'end' => Carbon::parse($this->end)->toIso8601String(),
            'variant' => $this->variant,
        ];

        return $ret;
    }
}
