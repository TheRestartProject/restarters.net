<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Host extends Model {

    public function getGroupName() {

      if( !empty($this->area) ) {
        return $this->location . ', ' . $this->area;
      } else {
        return $this->location;
      }

    }

}
