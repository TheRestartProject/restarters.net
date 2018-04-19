<?php

    class Category extends Model {
        
        protected $table = 'categories';
        private $revision = 1;
        
        public function findAll() {
            
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE `revision` = :rev';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':rev', $this->revision, PDO::PARAM_INT);
            
            $q = $stmt->execute();
            
            if(!$q){
                new Error(601, 'Could not execute query. (category.model.php, 17)');
                return false;
            }
            else {
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
        }

        public function listed(){
            $sql = 'SELECT * FROM clusters ORDER BY idclusters ASC';
            $stmt = $this->database->prepare($sql);
            $q = $stmt->execute();
            $clusters = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE `revision` = :rev AND `cluster` = :cluster ORDER BY `idcategories` ASC';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':rev', $this->revision, PDO::PARAM_INT);
            
            foreach($clusters as $k => $cluster){
                $stmt->bindParam(':cluster', $cluster->idclusters, PDO::PARAM_INT);
                $q = $stmt->execute();
                $clusters[$k]->categories = $stmt->fetchAll(PDO::FETCH_OBJ); 
            }
            
            return $clusters;
            
        }
        
        public function findAllByRevision($rev) {
            
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE `revision` = :rev';
            $stmt = $this->database->prepare($sql);
            $q = $stmt->execute();
            
            if(!$q){
                new Error(601, 'Could not execute query. (model.class.php, 76)');
                return false;
            }
            else {
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
        }
    }
    