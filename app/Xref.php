<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class Xref extends Model
{
    protected $table = 'xref';

    public $timestamps = false;

    protected $obj;
    protected $ref;
    protected $objType;
    protected $refType;

    private $index;
    private $search_id;
    private $search_type;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['object', 'object_type', 'reference', 'reference_type'];

    /**
     * returns object with cross-references to selected
     * ID and TABLE.
     * @ $index can be 'object' || 'reference'
     *   this selects in which direction to look
     * */
    public function findXref()
    {
        if ($this->index !== false) {
            $sql = 'SELECT * FROM `'.$this->table.'`
                    WHERE `'.$this->index.'` = :id
                    AND `'.$this->index.'_type` = :type';

            try {
                return DB::select(DB::raw($sql), ['id' => $this->search_id, 'type' => $this->search_type]);
            } catch (\Illuminate\Database\QueryException $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    public function findFullXref()
    {
        //switch ($table
    }

    public function createXref($clear = true)
    {
        if ($this->index !== false) {
            if ($clear) {
                self::deleteXref();
            }
            $sql = 'INSERT INTO `'.$this->table.'`(`object`, `object_type`, `reference`, `reference_type`) VALUES (:obj, :objType, :ref, :refType)';

            try {
                return DB::insert(DB::raw($sql), ['obj' => $this->obj, 'objType' => $this->objType, 'ref' => $this->ref, 'refType' => $this->refType]);
            } catch (\Illuminate\Database\QueryException $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteXref()
    {
        if ($this->index !== false) {
            $sql = 'DELETE FROM `'.$this->table.'` WHERE `reference` = :id AND `reference_type` = :type AND `object_type` = :objectType';

            try {
                return DB::delete(DB::raw($sql), ['id' => $this->ref, 'type' => $this->refType, 'objectType' => $this->objType]);
            } catch (\Illuminate\Database\QueryException $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteObjectXref()
    {
        $sql = 'DELETE FROM `'.$this->table.'` WHERE `object` = :id AND `object_type` = :type';

        try {
            return DB::delete(DB::raw($sql), ['id' => $this->obj, 'type' => $this->objType]);
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }
    }

    public function image()
    {
        return $this->hasOne(\App\Images::class, 'idimages', 'object');
    }
}
