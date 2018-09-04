<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UsersPreferences extends Model {

  protected $table = 'users_preferences';
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['preference_id', 'user_id'];

  protected $primaryKey = 'id';

  public function reference() {

    return $this->belongsToMany('App\Preferences', 'users_preferences');
    
  }

}
