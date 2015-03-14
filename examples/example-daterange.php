<?php

include '../src/gwtdata.php';

try {
    $email = 'username@gmail.com';
    $passwd = '******';

    $gdata = GWTdata::create($email, $passwd);
    $sites = $gdata->getSites();
    foreach ($sites as $site) {
        # Dates must be in valid ISO 8601 format.
        $gdata
            ->setDaterange(
                DateTime::createFromFormat('Y-m-d', '2012-01-10'),
                DateTime::createFromFormat('Y-m-d', '2012-01-12')
            )
            ->downloadCSV($site)
        ;
    }
} catch (Exception $e) {
    die($e->getMessage());
}
