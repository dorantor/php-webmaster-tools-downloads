<?php

include '../src/gwtdata.php';

try {
    $email = 'username@gmail.com';
    $passwd = '******';

    $gdata = GWTdata::create($email, $passwd);

    $sites = $gdata->GetSites();
    foreach($sites as $site) {
        $gdata->downloadCSV($site, './csv');
    }

    $files = $gdata->getDownloadedFiles();
    foreach($files as $file) {
        echo 'Saved ', $file, "\n";
    }
} catch (Exception $e) {
    die($e->getMessage());
}
