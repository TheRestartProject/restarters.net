<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class DeviceList extends Model
{

    protected $table = 'view_devices_list';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations


    // Setters


    //Getters
    function getRepairStatus() {

      switch( $this->repair_status ) {
        case 1:
          return 'Fixed';
        case 2:
          return 'Repairable';
        case 3:
          return 'End of life';
        default:
          return 'Unknown';
      }

    }

    function getSpareParts() {

      return ($this->spare_parts == 1 ? 'Yes' : 'No');

    }

}
