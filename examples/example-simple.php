<?php

include '../src/Gwt/Client.php';
include 'config.sample.php';

try {
    $client = Gwt_Client::create($email, $password)
        ->setDaterange(
            new DateTime('-3 day', new DateTimeZone('UTC')),
            new DateTime('-2 day', new DateTimeZone('UTC'))
        )
        ->setSite($website)
    ;

    echo $client->getTopQueriesTableData();
} catch (Exception $e) {
    die($e->getMessage());
}
