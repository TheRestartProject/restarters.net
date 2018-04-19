<?php

    class Usersgroups extends Model {
        
        protected $table = 'users_groups';
        
        
        /**
         * create associations
         * @ int $iduser
         * @ array $groups (group ids)
         * return boolean
         * */
        public function createUsersGroups($iduser, $groups){
            if(!self::deleteUsersGroups($iduser)){
                return false;
            }
            else {
                $sql = 'INSERT INTO `users_groups` (`user`, `group`) VALUES (:user, :group)';
                $stmt = $this->database->prepare($sql);
                $stmt->bindParam(':user', $iduser, PDO::PARAM_INT);
                
                foreach($groups as $k => &$group){
                    $stmt->bindParam(':group', $group, PDO::PARAM_INT);
                    $q = $stmt->execute();
                    if(!$q){
                        if(SYSTEM_STATUS == 'development'){
                            dbga($stmt->errorInfo());
                        }
                        return false;
                    }
                }
                return true;
            }
        }
        
        /**
         * delete associations by user
         * @ int $iduser
         * return boolean
         * */
        public function deleteUsersGroups($iduser){
            $sql = 'DELETE FROM `users_groups` WHERE `user` = :id';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $iduser, PDO::PARAM_INT);
            $q = $stmt->execute();
            if(!$q){
                if(SYSTEM_STATUS == 'development'){
                    dbga($stmt->errorInfo());
                }
                return false;
            }
            else {
                return true;
            }
        }
        
    }
    