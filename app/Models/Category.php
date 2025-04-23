<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\Fixometer;
use DB;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    private $revision = 2;
    protected $primaryKey = 'idcategories';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'weight', 'footprint', 'footprint_reliability', 'lifecycle', 'lifecycle_reliability', 'extended_lifecycle', 'extended_lifecycle_reliability', 'revision', 'cluster', 'powered', 'description_short' ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations

    // Setters

    //Getters
    public function findAll()
    {
        try {
            return DB::select("SELECT * FROM `{$this->table}` WHERE `revision` = :rev", ['rev' => $this->revision]);
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    public function listed()
    {
        $clusters = DB::select('SELECT * FROM clusters ORDER BY idclusters ASC');

        $sql = "SELECT * FROM `{$this->table}` WHERE `revision` = :rev AND `cluster` = :cluster ORDER BY `idcategories` ASC";

        foreach ($clusters as $k => $cluster) {
            $clusters[$k]->categories = DB::select($sql, ['rev' => $this->revision, 'cluster' => $cluster->idclusters]);
        }

        return $clusters;
    }

    public function findAllByRevision($rev)
    {
        try {
            return DB::select("SELECT * FROM `{$this->table}` WHERE `revision` = :rev", ['rev' => $rev]);
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    public function isMiscPowered()
    {
        return $this->idcategories == env('MISC_CATEGORY_ID_POWERED');
    }

    public function isMiscUnpowered()
    {
        return $this->idcategories == env('MISC_CATEGORY_ID_UNPOWERED');
    }

    public function isPowered()
    {
        return $this->powered == 1;
    }

    public function isUnpowered()
    {
        return $this->powered == 0;
    }
}
