<?php

include '../src/gwtdata.php';

try {
    $email    = 'example@gmail.com';
    $password = '*********'; // use app password if you have two step verification

    $sites = GWTdata::create($email, $password)
        ->getSites()
    ;

    foreach($sites as $site) {
        echo $site, "\n";
    }

} catch (Exception $e) {
    die($e->getMessage());
}

