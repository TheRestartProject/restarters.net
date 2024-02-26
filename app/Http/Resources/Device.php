<?php

namespace App\Http\Resources;

use App\DeviceBarrier;
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
 *          property="category",
 *          title="category",
 *          description="The id of the category to which this item belongs.",
 *          format="int64",
 *          example="16"
 *     ),
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
 *          property="case_study",
 *          title="case_study",
 *          description="Whether this item is an interesting case study.",
 *          format="boolean",
 *          example="true"
 *     ),
 *     @OA\Property(
 *          property="barrier",
 *          title="barrier",
 *          description="Iff repair_status is 'End of life',  the primary barrier to repair.",
 *          format="string",
 *          enum={"Spare parts not available", "Spare parts too expensive", "No way to open the product", "Repair information not available", "Lack of equipment"},
 *          example="Spare parts too expensive"
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
    public function toArray($request)
    {
        $ret = [
            'id' => intval($this->iddevices),
            'category' => intval($this->category),
            'item_type' => $this->item_type,
            'brand' => $this->brand,
            'model' => $this->model,
            'age' => floatval($this->age),
            'estimate' => floatval($this->estimate),
            'problem' => $this->problem,
            'notes' => $this->notes,
            'case_study' => intval($this->case_study) ? true : false,
        ];

        // Our database has a slightly complex structure for historical reasons, so we need to map some underlying
        // fields to simpler values to keep the API clean.
        //
        // There is mirror code in API\DeviceController.
        switch ($this->repair_status) {
            case \App\Device::REPAIR_STATUS_FIXED:
                $ret['repair_status'] = 'Fixed';
                break;
            case \App\Device::REPAIR_STATUS_REPAIRABLE:
                $ret['repair_status'] = 'Repairable';

                if ($this->more_time_needed) {
                    $ret['next_steps'] = 'More time needed';
                } else if ($this->professional_help) {
                    $ret['next_steps'] = 'Professional help';
                } else if ($this->do_it_yourself) {
                    $ret['next_steps'] = 'Do it yourself';
                }

                if ($this->parts_provider == \App\Device::PARTS_PROVIDER_MANUFACTURER) {
                    $ret['spare_parts'] = 'Manufacturer';
                } else if ($this->parts_provider == \App\Device::PARTS_PROVIDER_THIRD_PARTY) {
                    $ret['spare_parts'] = 'Third party';
                } else {
                    $ret['spare_parts'] = 'No';
                }
                break;
            case \App\Device::REPAIR_STATUS_ENDOFLIFE:
                $ret['repair_status'] = 'End of life';

                // The underlying DB might have multiple barriers, but we only support one across the API.
                foreach ($this->resource->barriers as $barrier) {
                        $ret['barrier'] = $barrier->barrier;
                        break;
                }
                break;
        }

        return $ret;
    }
}
