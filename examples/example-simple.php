<?php

include '../src/Gwt/Client.php';

try {
    $email = "username@gmail.com";
    $passwd = "******";

    # If hardcoded, don't forget trailing slash!
    $website = "http://www.domain.com/";

    Gwt_Client::create($email, $passwd)
        ->downloadCSV($website)
    ;
} catch (Exception $e) {
    die($e->getMessage());
}
