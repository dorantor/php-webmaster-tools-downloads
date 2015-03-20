<?php

include '../src/Gwt/Client.php';

try {

    $sites = Gwt_Client::create($email, $password)
        ->getSites()
    ;

    foreach($sites as $site) {
        echo $site, "\n";
    }
} catch (Exception $e) {
    die($e->getMessage());
}

