<?php

include '../src/Gwt/Client.php';

// load config values
include 'config.sample.php';

try {
    $client = Gwt_Client::create($email, $password)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->setWebsite($website)
    ;

    echo $client->getTopQueriesTableData();
} catch (Exception $e) {
    die($e->getMessage());
}
