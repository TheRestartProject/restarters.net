<?php

    class Role extends Model {
        
        protected $table = 'roles';
        
        
        /**
         * Extended to include connected permissions
         * and display all data (users too)
         * */
        public function findAll() {
            
            $sql = 'SELECT
                        `r`.`idroles` AS `id`,
                        `r`.`role` AS `role`,
                        GROUP_CONCAT(`p`.`permission` ORDER BY `p`.`permission` ASC SEPARATOR ", "  )  as `permissions_list`
                    FROM `' . $this->table . '` AS `r`
                    LEFT JOIN `roles_permissions` AS `rp` ON `r`.`idroles` = `rp`.`role`
                    LEFT JOIN `permissions` AS `p` ON `rp`.`permission` = `p`.`idpermissions`
                    GROUP BY `r`.`idroles` 
                    ORDER BY `r`.`idroles` ASC';
                    
            $stmt = $this->database->prepare($sql);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
        }
        
        public function permissions(){
            $sql = 'SELECT * FROM `permissions` ORDER BY `idpermissions` ASC';            
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function rolePermissions($role){
            $sql = 'SELECT * FROM `permissions`
                    INNER JOIN `roles_permissions` ON `roles_permissions`.`permission` = `permissions`.`idpermissions`
                    WHERE `roles_permissions`.`role` = :role
                    ORDER BY `idpermissions` ASC';            
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':role', $role, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        
        public function edit($id, $data){
            
            // delete permissions before updating references
            $sql = 'DELETE FROM roles_permissions WHERE role = :role';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':role', $id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // insert data here
            $sql = 'INSERT INTO roles_permissions(role, permission) VALUES (:role, :permission)';
            $stmt = $this->database->prepare($sql);
            
            $stmt->bindParam(':role', $id, PDO::PARAM_INT);
            foreach($data as &$p){
                $stmt->bindParam(':permission', $p, PDO::PARAM_INT);
                $stmt->execute();    
            }
            
            if($stmt->errorCode() == '00000'){
                return true;
            }
            else {
                return false; 
            }
        }
    }
    