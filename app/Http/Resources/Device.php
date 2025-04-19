<?php

namespace App\Http\Resources;

use App\Party;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="Device",
 *     schema="Device",
 *     description="An item which was examined at an Event.",
 *     required={"id", "category", "item_type"},
 *     @OA\Property(
 *          property="id",
 *          title="id",
 *          description="Unique identifier of this item.",
 *          format="int64",
 *          example=1
 *     ),
 *     @OA\Property(
 *           property="eventid",
 *           title="eventid",
 *           description="The event to which this device belongs.",
 *           format="int64",
 *           example=1
 *     ),
 *     @OA\Property(
 *            property="eventtitle",
 *            title="eventtitle",
 *            description="Title of the event.  Provided for convenience to avoid extra API calls.",
 *            format="string",
 *            example="Europe/London"
 *     ),
 *     @OA\Property(
 *            property="groupid",
 *            title="groupid",
 *            description="The group to which this device belongs.",
 *            format="int64",
 *            example=1
 *     ),
 *     @OA\Property(
 *           property="groupname",
 *           title="groupname",
 *           description="Name of the group. Provided for convenience to avoid extra API calls.",
 *           format="string",
 *           example="Restarters HQ"
 *     ),
 *     @OA\Property(
 *           property="created_at",
 *           title="created_at",
 *           description="When this device was first added",
 *           format="date-time",
 *           example="2022-09-18T11:30:00+00:00"
 *     ),
 *     @OA\Property(
 *            property="updated_at",
 *            title="updated_at",
 *            description="When this device was last updated",
 *            format="date-time",
 *            example="2022-09-18T11:30:00+00:00"
 *     ),
 *     @OA\Property(
 *          property="category",
 *          title="category",
 *          description="The category to which this item belongs.",
 *          format="int64",
 *          example="16"
 *     ),
 *     @OA\Property(
 *           property="category_creation",
 *           title="category_creation",
 *           description="The id of the category to which this item belonged at the time of creation (if different).",
 *           format="int64",
 *           example="16"
 *      ),
 *     @OA\Property(
 *          property="item_type",
 *          title="item_type",
 *          description="The name of the item.",
 *          format="string",
 *          example="Blender"
 *     ),
 *     @OA\Property(
 *          property="brand",
 *          title="brand",
 *          description="The brand or manufacturer of this item.",
 *          format="string",
 *          example="ACME"
 *     ),
 *     @OA\Property(
 *          property="model",
 *          title="model",
 *          description="The specific model of this item.",
 *          format="string",
 *          example="Wunderblender 2000"
 *     ),
 *     @OA\Property(
 *          property="age",
 *          title="age",
 *          description="The age of this item in years.  0 means the age is not known.",
 *          format="number",
 *          minimum="0",
 *          maximum="500",
 *          example="1.5"
 *     ),
 *     @OA\Property(
 *          property="estimate",
 *          title="estimate",
 *          description="The weight estimage for this item in kg.",
 *          format="float",
 *          minimum="0",
 *          example="1.5"
 *     ),
 *     @OA\Property(
 *          property="problem",
 *          title="problem",
 *          description="Description of the problem/solution.",
 *          format="string",
 *          example="The power switch was broken."
 *     ),
 *     @OA\Property(
 *           property="shortProblem",
 *           title="shortProblem",
 *           description="Shortened version of the of the problem/solution field.",
 *           format="string",
 *           example="The power switch was broken."
 *      ),
 *     @OA\Property(
 *          property="notes",
 *          title="notes",
 *          description="Notes - repair difficulties, owner's perception of problem etc",
 *          format="string",
 *          example="I didn't have the right kind of screwdriver to open it up."
 *     ),
 *     @OA\Property(
 *          property="repair_status",
 *          title="repair_status",
 *          description="Whether the item was fixed, is repairable, or cannot be fixed.",
 *          format="string",
 *          enum={"Fixed", "Repairable", "End of life"},
 *          example="Fixed"
 *     ),
 *     @OA\Property(
 *          property="next_steps",
 *          title="next_steps",
 *          description="Iff repair_status is 'repairable', what the next steps are.",
 *          format="string",
 *          enum={"More time needed", "Professional help", "Do it yourself"},
 *          example="More time needed"
 *     ),
 *     @OA\Property(
 *           property="spare_parts",
 *           title="spare_parts",
 *           description="Iff repair_status is 'repairable', whether any spare parts are needed.",
 *           format="string",
 *           enum={"No", "Manufacturer", "Third party"},
 *           example="Manufacturer"
 *     ),
 *     @OA\Property(
 *          property="barrier",
 *          title="barrier",
 *          description="Iff repair_status is 'End of life',  the primary barrier to repair.",
 *          format="string",
 *          enum={"Spare parts not available", "Spare parts too expensive", "No way to open the product", "Repair information not available", "Lack of equipment"},
 *          example="Spare parts too expensive"
 *     ),
 *     @OA\Property(
 *         property="images",
 *         title="images",
 *         description="Any images associated with this devices",
 *         type="array",
 *         @OA\Items(
 *           ref="#/components/schemas/Image"
 *         )
 *     ),
 * )
 */

class Device extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $event = \App\Party::find($this->event);
        $group = $event ? \App\Group::find($event->group) : NULL;

        $ret = [
            'id' => intval($this->iddevices),
            'eventid' => intval($this->event),
            'eventtitle' => $event ? $event->title : NULL,
            'groupid' => $event ? intval($event->group) : NULL,
            'groupname' => $group ? $group->name : NULL,
            'category' => intval($this->category),
            'item_type' => $this->item_type,
            'brand' => $this->brand,
            'model' => $this->model,
            'age' => floatval($this->age),
            'estimate' => floatval($this->estimate),
            'problem' => $this->problem,
            'short_problem' => $this->getShortProblem(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->created_at->toIso8601String(),
        ];

        // Our database has a slightly complex structure for historical reasons, so we need to map some underlying
        // fields to simpler values to keep the API clean.
        //
        // There is mirror code in API\DeviceController.
        switch ($this->repair_status) {
            case \App\Device::REPAIR_STATUS_FIXED:
                $ret['repair_status'] = \App\Device::REPAIR_STATUS_FIXED_STR;
                break;
            case \App\Device::REPAIR_STATUS_REPAIRABLE:
                $ret['repair_status'] = \App\Device::REPAIR_STATUS_REPAIRABLE_STR;
                break;
            case \App\Device::REPAIR_STATUS_ENDOFLIFE:
                $ret['repair_status'] = \App\Device::REPAIR_STATUS_ENDOFLIFE_STR;
                break;
        }

        if ($this->more_time_needed) {
            $ret['next_steps'] = \App\Device::NEXT_STEPS_MORE_TIME_NEEDED_STR;
        } else if ($this->professional_help) {
            $ret['next_steps'] = \App\Device::NEXT_STEPS_PROFESSIONAL_HELP_STR;
        } else if ($this->do_it_yourself) {
            $ret['next_steps'] = \App\Device::NEXT_STEPS_DO_IT_YOURSELF_STR;
        }

        if ($this->spare_parts === \App\Device::SPARE_PARTS_NOT_NEEDED) {
            $ret['spare_parts'] = \App\Device::PARTS_PROVIDER_NO_STR;
        } else if ($this->spare_parts === null) {
            $ret['spare_parts'] = null;
        } else {
            if ($this->parts_provider === \App\Device::PARTS_PROVIDER_MANUFACTURER) {
                $ret['spare_parts'] = \App\Device::PARTS_PROVIDER_MANUFACTURER_STR;
            } else if ($this->parts_provider === \App\Device::PARTS_PROVIDER_THIRD_PARTY) {
                $ret['spare_parts'] = \App\Device::PARTS_PROVIDER_THIRD_PARTY_STR;
            }
        }

        if ($this->professional_help) {
            $ret['next_steps'] = \App\Device::NEXT_STEPS_PROFESSIONAL_HELP_STR;
        } else if ($this->do_it_yourself) {
            $ret['next_steps'] = \App\Device::NEXT_STEPS_DO_IT_YOURSELF_STR;
        } else if ($this->more_time_needed) {
            $ret['next_steps'] = \App\Device::NEXT_STEPS_MORE_TIME_NEEDED_STR;
        } else {
            $ret['next_steps'] = null;
        }

        // The underlying DB might have multiple barriers, but we only support one across the API.
        foreach ($this->resource->barriers as $barrier) {
            $ret['barrier'] = $barrier->barrier;
            break;
        }

        $category = \App\Category::find($this->category);
        $ret['category']= \App\Http\Resources\Category::make($category);

        $images = $this->resource->getImages();
        $ret['images'] = \App\Http\Resources\Image::collection($images);

        return $ret;
    }
}
