<?php
    interface ModelInterface {
        
         function find($params);
         function findAll();
         function findOne($id);
        
    }