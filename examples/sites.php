<?php

include '../src/Gwt/Data.php';

try {
    $email    = 'dorantor@gmail.com';
    $password = 'zxssxftrvuqotlvl'; // use app password if you have two step verification

    $sites = Gwt_Data::create($email, $password)
        ->getSites()
    ;

    foreach($sites as $site) {
        echo $site, "\n";
    }
} catch (Exception $e) {
    die($e->getMessage());
}

