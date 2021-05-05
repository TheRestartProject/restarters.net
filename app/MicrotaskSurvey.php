<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MicrotaskSurvey extends Model
{
    protected $table = 'microtask_surveys';
    protected $dates = ['created_at', 'updated_at'];
    protected $dateFormat = 'Y-m-d H:i';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['session_id', 'ip_address', 'task', 'question', 'order', 'score'];
}
