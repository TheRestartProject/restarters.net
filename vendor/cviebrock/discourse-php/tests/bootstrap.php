<?php

foreach (array('.', '..', '../../..') as $dir) {
    $autoload = "{$dir}/vendor/autoload.php";
    if (file_exists($autoload)) {
        include $autoload;

        return;
    }
}
throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
