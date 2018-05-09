<?php
    foreach($data as $d) {
        $d = array_filter($d, 'utf8_encode');
        echo implode(',', $d) . "\n";
    }

?>
