<?php

include '../src/Gwt/Client.php';

// load config values
include 'config.sample.php';

try {
    # Language must be set as valid ISO 639-1 language code.
    $language = 'de';

    $data = Gwt_Client::create($email, $password)
        ->setLanguage($language)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->setWebsite($website)
        ->getTopQueriesTableData($tables)
    ;

    $rows = str_getcsv($data, "\n");
    echo $rows[0], "\n";
    echo $rows[1], "\n";
    echo $rows[2], "\n";
} catch (Exception $e) {
    die($e->getMessage());
}
