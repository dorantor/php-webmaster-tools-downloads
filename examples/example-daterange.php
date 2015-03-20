<?php

include '../src/Gwt/Data.php';

try {
    $email = 'username@gmail.com';
    $passwd = '******';

    $gdata = Gwt_Client::create($email, $passwd);
    $sites = $gdata->getSites();
    foreach ($sites as $site) {
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
