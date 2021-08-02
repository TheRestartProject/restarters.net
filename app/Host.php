<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'idgroups';

    public function hostImage()
    {
        return $this->hasOne(\App\Xref::class, 'reference', 'idgroups')->where('object_type', 5);
    }

    public function getGroupName()
    {
        if (! empty($this->area)) {
            return $this->location.', '.$this->area;
        } else {
            return $this->location;
        }
    }
}
