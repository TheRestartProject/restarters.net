<?php
    foreach($data as $d) {
        $d = array_filter((array) $d, 'utf8_encode');
        echo implode(',', $d) . "\n";
    }

?>
