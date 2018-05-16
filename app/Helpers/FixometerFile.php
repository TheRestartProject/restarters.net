<?php

use App\Xref;
use App\Images;

use Illuminate\Database\Eloquent\Model;

class FixometerFile extends Model {

    public $path;
    public $file;
    public $ext;

    protected $table;

    protected $dates = true;

    /**
     * receives the POST data from an HTML form
     * processes file upload and saves
     * to database, depending on filetype
     * */

    public function upload($file, $type, $reference = null, $referenceType = null, $multiple = false, $profile = false){

        $clear = true; // purge pre-existing images from db - this is the default behaviour

        if(is_string($file)){
            $user_file = $_FILES[$file];
        }
        elseif(is_array($file)){ // multiple file uploads means we do not purge pre-existing images
            $user_file = $file;
            $clear = false;
        }

        /** if we have no error, proceed to elaborate and upload **/
        if($user_file['error'] == UPLOAD_ERR_OK){
            $filename = $this->filename($user_file);
            $this->file = $filename;
            $path = $_SERVER['DOCUMENT_ROOT'].'/uploads/'. $filename;
            if(!move_uploaded_file($user_file['tmp_name'], $path)){
                return false;
            }
            else {
                $data = array();
                $this->path = $path;
                $data['path'] = $this->file;

                if($type === 'image'){
                    $size = getimagesize($this->path);
                    $data['width']  = $size[0];
                    $data['height'] = $size[1];

                    if($profile == true) {
                        $data['alt_text'] = "Profile Picture";

                        if($this->ext == 'jpg') {
                            $profile_pic = imagecreatefromjpeg($this->path);

                        }
                        elseif($this->ext == 'png') {
                            $profile_pic = imagecreatefrompng($this->path);
                        }


                        if($data['width'] > $data['height']) {
                            $biggestSide = $data['width'];
                        }
                        else {
                            $biggestSide = $data['height'];
                        }


                        $cropPercent = 1;
                        $cropWidth   = $biggestSide*$cropPercent;
                        $cropHeight  = $biggestSide*$cropPercent;

                        //getting the top left coordinate
                        $c1 = array("x"=>( $data['width']-$cropWidth)/2, "y"=>( $data['height']-$cropHeight)/2);

                        $thumbSize = 60;
                        $midSize = 260;

                        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
                        $mid = imagecreatetruecolor($midSize, $midSize);

                        imagecopyresampled($thumb, $profile_pic, 0, 0, $c1['x'], $c1['y'], $thumbSize, $thumbSize, $cropWidth, $cropHeight);
                        imagecopyresampled($mid, $profile_pic, 0, 0, $c1['x'], $c1['y'], $midSize, $midSize, $cropWidth, $cropHeight);

                        if($this->ext == 'jpg'){
                            imagejpeg($thumb, $_SERVER['DOCUMENT_ROOT'].'/uploads/'. 'thumbnail_' . $filename, 85);
                            imagejpeg($mid, $_SERVER['DOCUMENT_ROOT'].'/uploads/'. 'mid_' . $filename, 85);
                        }
                        elseif($this->ext == 'png') {
                            imagepng($thumb, $_SERVER['DOCUMENT_ROOT'].'/uploads/'. 'thumbnail_' . $filename );
                            imagepng($mid, $_SERVER['DOCUMENT_ROOT'].'/uploads/'. 'mid_' . $filename );
                        }
                    }


                    $this->table = 'images';
                    $Images = new Images;

                    $image = $Images->create($data)->id;
                    //echo "REF: " . $reference. " - REF TYPE: ".$referenceType." - IMAGE: ".$image;
                    if(is_numeric($image) && !is_null($reference) && !is_null($referenceType)){
                        $xref = new Xref('object', $image, env('TBL_IMAGES'), $reference, $referenceType);
                        $xref->createXref($clear);
                    }
                }



            }
            return $filename;

        }
        /** else, we raise exceptions and errors! **/
        else {

        }

    }

    /**
     * generates filename and maintains
     * correct file extension
     * (MIME check with Finfo!)
     * */
    public function filename($file){
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $ext = array_search(
            $finfo->file($file['tmp_name']),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ),
            true);

        if(empty($ext) || !$ext || is_null($ext)) {
            return false;
        }

        else {
            $this->ext = $ext;
            $filename = time() . sha1_file( $file['tmp_name']) . rand(1, 15000) . '.' . $ext;
            return $filename;
        }


    }

    public function findImages($of_ref_type, $ref_id){

        $sql = 'SELECT * FROM `images` AS `i`
                    INNER JOIN `xref` AS `x` ON `x`.`object` = `i`.`idimages`
                    WHERE `x`.`object_type` = ' . env('TBL_IMAGES') . ' AND
                    `x`.`reference_type` = :refType AND
                    `x`.`reference` = :refId';
        try {
          return DB::select(DB::raw($sql), array('refType' => $of_ref_type, 'refId' => $ref_id));
        } catch (\Illuminate\Database\QueryException $e) {
          return db($e);
        }

    }

    public function deleteImage($id, $path){
        $del = unlink( $_SERVER['DOCUMENT_ROOT'].'/uploads/' . $path);

        $sql = 'DELETE FROM `images` WHERE `idimages` = :id';

        try {
          return DB::delete(DB::raw($sql), array('id' => $id));
        } catch (\Illuminate\Database\QueryException $e) {
          return db($e);
        }

        $sql = 'DELETE FROM `xref` WHERE `object` = :id AND `object_type` = '.env('TBL_IMAGES');

        try {
          return DB::delete(DB::raw($sql), array('id' => $id));
        } catch (\Illuminate\Database\QueryException $e) {
          return db($e);
        }
    }

    public function simpleUpload($file, $object = 'device', $object_id, $title = null){

      if($file['error'] == 0) {
        $filename = $this->filename($file);
        $path = $_SERVER['DOCUMENT_ROOT'].'/uploads/'. $filename;


        if(!move_uploaded_file($file['tmp_name'], $path)){
          return false;
        }
        else {

          $size = getimagesize($path);

          $data['path']     = $filename;
          $data['width']    = $size[0];
          $data['height']   = $size[1];
          $data['alt_text'] = $title;

          $Images = new Images;

          $image = $Images->create($data);

          if(is_numeric($image)  && !is_null($object_id)){
              $xref = new Xref('object', $image, env('TBL_IMAGES'), $object_id, env('TBL_DEVICES'));
              $xref->createXref(true);
          }
        }
      }


    }

}
