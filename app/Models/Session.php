<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions';

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
    public static function createSession($user)
    {
        $session = '$1'.md5(substr(time(), -8));
        $created_at = date('Y-m-d H:i:s', time());

        try {
            DB::insert(
                'INSERT INTO `sessions`(`session`, `user`, created_at VALUES (:session, :user, :tm)',
                ['session' => $session, 'user' => $user, 'tm' => $created_at]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return false;
        }

        return true;
    }

    public function setSession($user, $sessionToken)
    {
        $sql = 'UPDATE `sessions` SET `session` = :session WHERE `user` = :user';

        try {
            DB::update($sql, ['session' => $sessionToken, 'user' => $user]);

            unset($_SESSION[env('APP_NAME')]);
            $_SESSION[env('APP_NAME')][env('APP_KEY')] = $sessionToken; //was $_SESSION[APPNAME][SESSIONKEY] will need a config file for SESSIONKEY
            //SESSIONKEY was defined as `define('SESSIONKEY', md5(APPKEY));`
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    //Getters
    protected function getSession()
    {
        $session = $_SESSION[env('APP_NAME')][env('APP_KEY')]; //was $_SESSION[APPNAME][SESSIONKEY] will need a config file for SESSIONKEY
        //SESSIONKEY was defined as `define('SESSIONKEY', md5(APPKEY));`

        $sql = 'SELECT users.idusers AS id, users.name, users.email, roles.role, xi.path FROM users
                  INNER JOIN roles ON roles.idroles = users.role
                  INNER JOIN sessions ON sessions.user = users.idusers
                  LEFT JOIN (
                  SELECT * FROM `images`
                      INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                      WHERE `xref`.`object_type` = 5
                      AND `xref`.`reference_type` = 1
                      GROUP BY `images`.`path`
              ) AS `xi`
              ON `xi`.`reference` = `users`.`idusers`
              WHERE sessions.session = :session';

        try {
            $objectUser = DB::select($sql, ['session' => $session]);

            if (is_object($objectUser)) {
                $User = new User;
                $objectUser->permissions = $User->getRolePermissions($objectUser->role);
            }

            return $objectUser;
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    protected function destroySession($session, $user)
    {
    }
}
