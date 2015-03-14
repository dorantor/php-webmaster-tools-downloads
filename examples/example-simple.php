<?php

include '../src/gwtdata.php';

try {
    $email = "username@gmail.com";
    $passwd = "******";

    # If hardcoded, don't forget trailing slash!
    $website = "http://www.domain.com/";

    GWTdata::create($email, $passwd)
        ->downloadCSV($website)
    ;
} catch (Exception $e) {
    die($e->getMessage());
}
