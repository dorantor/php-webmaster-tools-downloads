<?php

include '../src/Gwt/Client.php';
// load config values
include 'config.sample.php';


try {
    $sites = Gwt_Client::create($email, $password)
        ->getSites()
    ;

    foreach ($sites as $site => $options) {
        echo $site, ' : ', ($options['verified'] ? 'verified' : 'not verified') ,"\n";
    }
} catch (Exception $e) {
    die($e->getMessage());
}
