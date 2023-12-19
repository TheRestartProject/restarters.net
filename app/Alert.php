<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

class Alert extends Model implements Auditable
{
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    protected $table = 'alerts';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'html',
        'ctatitle',
        'ctalink',
        'start',
        'end'
    ];
}
