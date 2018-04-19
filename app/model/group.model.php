<?php

    class Group extends Model {

        protected $table = 'groups';
        protected $dates = true;

        public function findAll() {
            $sql =  'SELECT
                        `g`.`idgroups` AS `id`,
                        `g`.`name` AS `name`,
                        `g`.`location` AS `location`,
                        `g`.`latitude` AS `latitude`,
                        `g`.`longitude` AS `longitude`,
                        `g`.`free_text` AS `free_text`,
                        `g`.`area` AS `area`,
                        `g`.`frequency` AS `frequency`,
                        GROUP_CONCAT(`u`.`name` ORDER BY `u`.`name` ASC SEPARATOR ", "  )  AS `user_list`
                    FROM `' . $this->table . '` AS `g`
                    LEFT JOIN `users_groups` AS `ug` ON `g`.`idgroups` = `ug`.`group`
                    LEFT JOIN `users` AS `u` ON `ug`.`user` = `u`.`idusers`
                    GROUP BY `g`.`idgroups`
                    ORDER BY `g`.`idgroups` ASC';
            $stmt = $this->database->prepare($sql);

            $q = $stmt->execute();
            if(!$q){
                dbga($stmt->errorInfo());
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }


        public function findList() {

                $sql =  'SELECT
                        `g`.`idgroups` AS `id`,
                        `g`.`name` AS `name`,
                        `g`.`location` AS `location`,
                        `g`.`area` AS `area`,
                        `xi`.`path` AS `path`

                    FROM `' . $this->table . '` AS `g`

                    LEFT JOIN (
                        SELECT * FROM `images`
                            INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                            WHERE `xref`.`object_type` = 5
                            AND `xref`.`reference_type` = ' . TBL_GROUPS . '
                            GROUP BY `images`.`path`
                    ) AS `xi`
                    ON `xi`.`reference` = `g`.`idgroups`

                    GROUP BY `g`.`idgroups`

                    ORDER BY `g`.`name` ASC';
            $stmt = $this->database->prepare($sql);

            $q = $stmt->execute();
            if(!$q){
                dbga($stmt->errorInfo());
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);

        }


        public function findOne($id){

            $sql = 'SELECT * FROM `' . $this->table . '` AS `g`
                    LEFT JOIN (
                        SELECT * FROM `images`
                            INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                            WHERE `xref`.`object_type` = 5
                            AND `xref`.`reference_type` = ' . TBL_GROUPS . '
                            GROUP BY `images`.`path`
                    ) AS `xi`
                    ON `xi`.`reference` = `g`.`idgroups`
                    WHERE `id' . $this->table . '` = :id';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $q = $stmt->execute();

            if(!$q){
                new Error(601, 'Could not execute query. (model.class.php, 61)');
                return false;
            }
            else {
                return $stmt->fetch(PDO::FETCH_OBJ);
            }

        }

        public function findHost($id){
            $sql = 'SELECT *,
                        `g`.`name` AS `groupname`,
                        `u`.`name` AS `hostname`
                    FROM `' . $this->table . '` AS `g`
                    INNER JOIN `users_groups` AS `ug`
                        ON `ug`.`group` = `g`.`idgroups`
                    INNER JOIN `users` AS `u`
                        ON `u`.`idusers` = `ug`.`user`
                    LEFT JOIN (
                        SELECT * FROM `images`
                            INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                            WHERE `xref`.`object_type` = 5
                            AND `xref`.`reference_type` = ' . TBL_USERS . '
                            GROUP BY `images`.`path`
                    ) AS `xi`
                    ON `xi`.`reference` = `u`.`idusers`

                    WHERE `g`.`idgroups` = :id
                    AND `u`.`role` = 3'; // force Role


            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();



            return $stmt->fetch(PDO::FETCH_OBJ);
        }


        public function ofThisUser($id){
            $sql = 'SELECT * FROM `' . $this->table . '` AS `g`
                    INNER JOIN `users_groups` AS `ug`
                        ON `ug`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT * FROM `images`
                            INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                            WHERE `xref`.`object_type` = 5
                            AND `xref`.`reference_type` = ' . TBL_GROUPS . '
                            GROUP BY `images`.`path`
                    ) AS `xi`
                    ON `xi`.`reference` = `g`.`idgroups`

                    WHERE `ug`.`user` = :id
                    ORDER BY `g`.`name` ASC';

            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();



            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }

    }
