<?php

include '../src/gwtdata.php';

try {
    $email = "username@gmail.com";
    $passwd = "******";

    $gdata = new GWTdata();
    if ($gdata->logIn($email, $passwd) === true)
    {
        $sites = $gdata->getSites();
        foreach($sites as $site) {
            # Dates must be in valid ISO 8601 format.
            $gdata->setDaterange('2012-01-10', '2012-01-12');
            $gdata->downloadCSV($site);
        }
    }
} catch (Exception $e) {
    die($e->getMessage());
}
