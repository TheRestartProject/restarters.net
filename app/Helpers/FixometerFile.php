<?php

use App\Images;
use App\Xref;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManagerStatic as Image;

class FixometerFile extends Model
{
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
    public function upload($file, $type, $reference = null, $referenceType = null, $multiple = false, $profile = false, $ajax = false, $crop = true)
    {
        $clear = true; // purge pre-existing images from db - this is the default behaviour

        if (is_string($file) && isset($_FILES[$file])) {
            $user_file = $_FILES[$file];
        } elseif (is_array($file)) { // multiple file uploads means we do not purge pre-existing images
            $user_file = $file;
            $clear = false;
        }

        if ($multiple) {
            $clear = false;
        }

        if ($clear) {
            Xref::where('reference', $reference)
                  ->where('reference_type', $referenceType)
                    ->forceDelete();
        }

        if ($ajax) {
            $error = $user_file['error'][0];
            $tmp_name = $user_file['tmp_name'][0];
        } else {
            $error = $user_file['error'];
            $tmp_name = $user_file['tmp_name'];
        }

        /** if we have no error, proceed to elaborate and upload **/
        if ($error == UPLOAD_ERR_OK) {
            $filename = $this->filename($tmp_name);
            $this->file = $filename;
            $lpath = $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$filename;
            if (! @move_uploaded_file($tmp_name, $lpath)) {
                return false;
            }
            $data = [];
            $this->path = $lpath;
            $data['path'] = $this->file;

            // Fix orientation
            Image::make($lpath)->orientate()->save($lpath);

            if ($type === 'image') {
                $size = getimagesize($this->path);
                $data['width'] = $size[0];
                $data['height'] = $size[1];

                if ($profile) {
                    $data['alt_text'] = 'Profile Picture';
                }

                if ($data['width'] > $data['height']) {
                    $biggestSide = $data['width'];
                    $resize_height = true;
                } else {
                    $biggestSide = $data['height'];
                    $resize_height = false;
                }

                $thumbSize = 80;
                $midSize = 260;

                // Let's make images, which we will resize or crop
                $thumb = Image::make($lpath);
                $mid = Image::make($lpath);

                if ($resize_height) { // Resize before crop
                    $thumb->resize(null, $thumbSize, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    $mid->resize(null, $midSize, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                } else {
                    $thumb->resize($thumbSize, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    $mid->resize($midSize, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }

                if ($crop) {
                    $thumb->crop($thumbSize, $thumbSize);
                    $mid->crop($midSize, $midSize);
                }

                $thumb->save($_SERVER['DOCUMENT_ROOT'].'/uploads/'.'thumbnail_'.$filename, 85);
                $mid->save($_SERVER['DOCUMENT_ROOT'].'/uploads/'.'mid_'.$filename, 85);

                $this->table = 'images';
                $Images = new Images;

                $image = $Images->create($data)->id;

                if (is_numeric($image) && ! is_null($reference) && ! is_null($referenceType)) {
                    Xref::create([
                        'object' => $image,
                        'object_type' => env('TBL_IMAGES'),
                        'reference' => $reference,
                        'reference_type' => $referenceType,
                    ]);
                }
            }

            return $filename;
        }
    }

    /**
     * generates filename and maintains
     * correct file extension
     * (MIME check with Finfo!)
     * */
    public function filename($tmp_name)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $lext = array_search(
            $finfo->file($tmp_name),
            [
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ],
            true
        );

        if (empty($lext) || ! $lext || is_null($lext)) {
            return false;
        }
        $this->ext = $lext;

        return time().sha1_file($tmp_name).rand(1, 15000).'.'.$lext;
    }

    public function findImages($of_ref_type, $ref_id)
    {
        $sql = 'SELECT * FROM `images` AS `i`
                    INNER JOIN `xref` AS `x` ON `x`.`object` = `i`.`idimages`
                    WHERE `x`.`object_type` = '.env('TBL_IMAGES').' AND
                    `x`.`reference_type` = :refType AND
                    `x`.`reference` = :refId';

        try {
            return DB::select(DB::raw($sql), ['refType' => $of_ref_type, 'refId' => $ref_id]);
        } catch (\Illuminate\Database\QueryException $e) {
            return db($e);
        }
    }

    public function deleteImage($id, $path)
    {
        unlink($_SERVER['DOCUMENT_ROOT'].'/uploads/'.basename($path));

        $sql = 'DELETE FROM `images` WHERE `idimages` = :id';

        try {
            return DB::delete(DB::raw($sql), ['id' => $id]);
        } catch (\Illuminate\Database\QueryException $e) {
            return db($e);
        }

        $sql = 'DELETE FROM `xref` WHERE `object` = :id AND `object_type` = '.env('TBL_IMAGES');

        try {
            return DB::delete(DB::raw($sql), ['id' => $id]);
        } catch (\Illuminate\Database\QueryException $e) {
            return db($e);
        }
    }

    public function simpleUpload($file, $object_id, $object = 'device', $title = null)
    {
        if ($file['error'] == 0) {
            $filename = $this->filename($file);
            $lpath = $_SERVER['DOCUMENT_ROOT'].'/uploads/'.$filename;

            if (! move_uploaded_file($file['tmp_name'], $lpath)) {
                return false;
            }
            $size = getimagesize($lpath);

            $data['path'] = $filename;
            $data['width'] = $size[0];
            $data['height'] = $size[1];
            $data['alt_text'] = $title;

            $Images = new Images;

            $image = $Images->create($data);

            if (is_numeric($image) && ! is_null($object_id)) {
                $xref = new Xref('object', $image, env('TBL_IMAGES'), $object_id, env('TBL_DEVICES'));
                $xref->createXref(true);
            }
        }
    }
}
