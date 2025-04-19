<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Network",
 *     schema="Network",
 *     description="A network of Groups using the Restarters platform.",
 *     required={"id", "full"},
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this network",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *          property="name",
 *          title="name",
 *          description="Unique name of this network",
 *          format="string",
 *          example="Default Network"
 *     ),
 *     @OA\Property(
 *          property="logo",
 *          title="image",
 *          description="URL of a logo for this network.",
 *          format="string",
 *          example="/mid_1597853610178a4b76e4d666b2a7b32ee75d8a24c706f1cbf213970.png"
 *     ),
 *     @OA\Property(
 *          property="description",
 *          title="description",
 *          description="HTML description of the network.",
 *          format="string",
 *          example="<p>This is a description.</p>"
 *     ),
 *     @OA\Property(
 *          property="website",
 *          title="website",
 *          description="An URL for the networks's own separate website.",
 *          format="string",
 *          example="https://therestartproject.org"
 *     ),
 *     @OA\Property(
 *          property="shortname",
 *          title="shortname",
 *          description="A short name for the network..",
 *          format="string",
 *          example="resarters"
 *     ),
 *     @OA\Property(
 *          property="default_language",
 *          title="default_language",
 *          description="The default language for users in this network.",
 *          format="string",
 *          example="fr-BE"
 *     ),
 *     @OA\Property(
 *          property="timezone",
 *          title="timezone",
 *          description="Default timezone for this network.",
 *          format="string",
 *          example="Europe/London"
 *     ),
 *     @OA\Property(
 *          property="stats",
 *          title="stats",
 *          description="An array of statistics about the activity of a network.",
 *          format="object",
 *          @OA\Property(
 *              property="co2_powered",
 *              title="co2_powered",
 *              description="The amount of CO2 saved by repairing powered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="co2_unpowered",
 *              title="co2_unpowered",
 *              description="The amount of CO2 saved by repairing unpowered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="co2_total",
 *              title="co2_total",
 *              description="co2_powered + co2_unpowered",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="waste_powered",
 *              title="waste_powered",
 *              description="The weight in kg of waste saved by repairing powered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="waste_unpowered",
 *              title="waste_unpowered",
 *              description="The weight in kg of waste saved by repairing unpowered items.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="fixed_powered",
 *              title="fixed_powered",
 *              description="The number of powered items which have been repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="fixed_unpowered",
 *              title="fixed_unpowered",
 *              description="The number of unpowered items which have been repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="fixed_devices",
 *              title="fixed_devices",
 *              description="fixed_powered + fixed_unpowered",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="repairable_devices",
 *              title="repairable_devices",
 *              description="The number of devices which were capable of being repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="dead_devices",
 *              title="dead_devices",
 *              description="The number of devices which were not capable of being repaired.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="unknown_repair_status",
 *              title="unknown_repair_status",
 *              description="The number of devices where the repair status was not known.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="devices_powered",
 *              title="devices_powered",
 *              description="The number of powered devices seen.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="devices_unpowered",
 *              title="devices_unpowered",
 *              description="The number of unpowered devices seen.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="no_weight_powered",
 *              title="no_weight_powered",
 *              description="The number of powered devices where no weight was provided.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="no_weight_unpowered",
 *              title="no_weight_unpowered",
 *              description="The number of unpowered devices where no weight was provided.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="participants",
 *              title="participants",
 *              description="The number of people who attended.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="volunteers",
 *              title="volunteers",
 *              description="The number of volunteers.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="hours_volunteered",
 *              title="hours_volunteered",
 *              description="The estimated number of hours volunteered for this network.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *              property="events",
 *              title="events",
 *              description="The number of events created by this network.",
 *              type="number",
 *          ),
 *          @OA\Property(
 *               property="full",
 *               title="full",
 *               description="Indicates that this is a full result, not summary network information.",
 *               format="boolean",
 *               example="true"
 *          )
 *     ),
 *     @OA\Property(
 *          property="updated_at",
 *          title="updated_at",
 *          description="The last change to this network.  This includes changes which affect the stats.",
 *          format="date-time",
 *     )
 * )
 */

class Network extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? ($request->root() . '/uploads/' . $this->logo) : null,
            'description' => $this->description,
            'website' => $this->website,
            'shortname' => $this->shortname,
            'default_language' => $this->default_language,
            'stats' => $this->resource->stats(),
            'timezone' => $this->resource->timezone,
            'full' => true
        ];
    }
}
