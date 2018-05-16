<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class Xref extends Model {

    protected $table = 'xref';

    protected $obj;
    protected $ref;
    protected $objType;
    protected $refType;

    private $index;
    private $search_id;
    private $search_type;

    /**
     * @ object -> can be image, link, or any other shared asset
     * @ reference -> the main App Element that needs the object (user, event, device, group)
     * */
    public function __construct($index, $object = null, $objectType = null, $reference = null, $referenceType = null){

        parent::__construct();

        $this->obj      = (integer)$object;
        $this->ref      = (integer)$reference;
        $this->objType  = (integer)$objectType;
        $this->refType  = (integer)$referenceType;

        $this->index    = ($index === 'object' || $index === 'reference') ? $index : false;
        if($this->index === 'object'){
            $this->search_id = $this->obj;
            $this->search_type = $this->objType;
        }
        elseif($this->index === 'reference'){
            $this->search_id = $this->ref;
            $this->search_type = $this->refType;


        }
        else {
            return new Error(500, 'Invalid Index in Xref search. (xref.class.php, line 32)');
        }
    }

    /**
     * returns object with cross-references to selected
     * ID and TABLE.
     * @ $index can be 'object' || 'reference'
     *   this selects in which direction to look
     * */
    public function findXref(){
        if($this->index !== false){
            $sql = 'SELECT * FROM `' . $this->table . '`
                    WHERE `' . $this->index . '` = :id
                    AND `' . $this->index . '_type` = :type';

            try {
              return DB::select(DB::raw($sql), array('id' => $this->search_id, 'type' => $this->search_type));
            } catch (\Illuminate\Database\QueryException $e) {
              return false;
            }
        }
        else {
            return false;
        }
    }

    public function findFullXref() {
        //switch ($table


    }

    public function createXref($clear = true){
        if($this->index !== false){
            if($clear == true){
                self::deleteXref();
            }
            $sql = 'INSERT INTO `' . $this->table . '`(`object`, `object_type`, `reference`, `reference_type`) VALUES (:obj, :objType, :ref, :refType)';

            try {
              return DB::insert(DB::raw($sql), array('obj' => $this->obj, 'objType' => $this->objType, 'ref' => $this->ref, 'refType' => $this->refType));
            } catch (\Illuminate\Database\QueryException $e) {
              return false;
            }
        }
        else {
            return false;
        }

    }

    public function deleteXref() {

        if($this->index !== false){
            $sql = 'DELETE FROM `' . $this->table . '` WHERE `reference` = :id AND `reference_type` = :type AND `object_type` = :objectType';

            try {
              return DB::delete(DB::raw($sql), array('id' => $this->ref, 'type' => $this->refType, 'objectType' => $this->objType));
            } catch (\Illuminate\Database\QueryException $e) {
              return false;
            }
        }
        else {
            return false;
        }

    }

    public function deleteObjectXref(){
            $sql = 'DELETE FROM `' . $this->table . '` WHERE `object` = :id AND `object_type` = :type';

            try {
              return DB::delete(DB::raw($sql), array('id' => $this->obj, 'type' => $this->objType));
            } catch (\Illuminate\Database\QueryException $e) {
              return false;
            }
    }

}
