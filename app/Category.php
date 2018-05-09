<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class Category extends Model
{

    protected $table = 'categories';
    private $revision = 1;
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
    public function findAll() {
      try {
        return DB::select(DB::raw('SELECT * FROM `' . $this->table . '` WHERE `revision` = :rev'), array('rev' => $this->revision));
      } catch (\Illuminate\Database\QueryException $e) {
        return false;
      }
    }

    public function listed(){
      $clusters = DB::select(DB::raw('SELECT * FROM clusters ORDER BY idclusters ASC'));

      $sql = 'SELECT * FROM `' . $this->table . '` WHERE `revision` = :rev AND `cluster` = :cluster ORDER BY `idcategories` ASC';

      foreach($clusters as $k => $cluster){
        $clusters[$k]->categories = DB::select(DB::raw($sql), array('rev' => $this->revision, 'cluster' => $cluster->idclusters));
      }

      return $clusters;
    }

    public function findAllByRevision($rev) {
      try {
        DB::select(DB::raw('SELECT * FROM `' . $this->table . '` WHERE `revision` = :rev'), array('rev' => $rev));
      } catch (\Illuminate\Database\QueryException $e) {
        return false;
      }
    }

}
