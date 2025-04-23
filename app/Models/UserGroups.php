<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class UserGroups extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'users_groups';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user', 'group', 'status', 'role'];

    protected $primaryKey = 'idusers_groups';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public $timestamps = false;

    //Table Relations

    // Setters

    /**
     * create associations
     * @ int $i
     duser
     * @ array $groups (group ids)
     * return boolean
     * */
    public function createUsersGroups($iduser, $groups)
    {
        if (! self::deleteUsersGroups($iduser)) {
            return false;
        } else {
            $sql = 'INSERT INTO `users_groups` (`user`, `group`) VALUES (:user, :group)';

            foreach ($groups as &$group) {
                try {
                    DB::insert($sql, ['user' => $iduser, 'group' => $group]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if (env('APP_ENV') == 'local' || env('APP_ENV') == 'development') {
                        dd($e);
                    }

                    return false;
                }
            }

            return true;
        }
    }

    //Getters

    /**
     * delete associations by user
     * @ int $iduser
     * return boolean
     * */
    public function deleteUsersGroups($iduser)
    {
        $sql = 'DELETE FROM `users_groups` WHERE `user` = :id';
        try {
            DB::delete($sql, ['id' => $iduser]);

            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            if (env('APP_ENV') == 'local' || env('APP_ENV') == 'development') {
                dd($e);
            }

            return false;
        }
    }

    //Table Relations
    public function volunteer(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'id', 'user');
    }

    public function isConfirmed()
    {
        return $this->status == '1';
    }

    #[Scope]
    protected function confirmedInvitation($query)
    {
        return $query->where('status', 1)->orWhereNull('status');
    }

    public function getFullName()
    {
        if ($this->volunteer) {
            return $this->volunteer->getFullName();
        }

        return 'N/A';
    }

    public function user(): BelongsTo {
        return $this->belongsTo(\App\Models\User::class, 'user', 'id');
    }

    public function group(): BelongsTo {
        return $this->belongsTo(\App\Groups::class, 'group', 'idgroups');
    }
}
