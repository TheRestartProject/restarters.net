<?php
    session_start();
    $url =  (isset($_GET['url']) ? $_GET['url'] : '');


    header("Access-Control-Allow-Origin: *");

    require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config.php');
    require_once(ROOT . DS . 'config' . DS . 'database.php');
    require_once(ROOT . DS . 'config' . DS . 'definitions.php');

    /** check language **/
    if(isset($_COOKIE[LANGUAGE_COOKIE])){
      $lang = $_COOKIE[LANGUAGE_COOKIE];
    }
    else {
      $lang = 'en';
    }
    $langfile = ROOT . DS . 'lang' . DS . $lang . '.php';
    if(file_exists($langfile)){
      include($langfile);
    }
    else {
      echo "NO LANG FILE";
    }


    require_once(ROOT . DS . 'lib' . DS . 'functions.php');
    require_once(ROOT . DS . 'lib' . DS . 'main.php');
