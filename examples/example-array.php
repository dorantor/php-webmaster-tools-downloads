<?php

include '../src/Gwt/Client.php';
include '../src/Gwt/Processor/ProcessorInterface.php';
include '../src/Gwt/Processor/ProcessorAbstract.php';
include '../src/Gwt/Processor/Array.php';
include '../src/Gwt/Processor/ArrayFilter.php';

// load config values
include 'config.sample.php';

try {
    $client = Gwt_Client::create($email, $password)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->setWebsite($website)
        ->addProcessor(Gwt_Processor_Array::factory())
        ->addProcessor(
            Gwt_Processor_ArrayFilter::factory(array(
                'columnNamesToRemove'   => array('Change'),
                'columnKeysToRemove'    => array(5),
            ))
        )
    ;

    //list($fieldNames, $data) = $client->getTopPagesTableData();
    list($fieldNames, $data) = $client->getTopQueriesTableData();

    // take only three first rows
    $data = array_slice($data, 0, 3);
    foreach ($data as $row) {
        foreach ($fieldNames as $fieldKey => $fieldName) {
            if ($fieldKey) { // nice offset for nonzero fields
                echo '    ';
            }
            echo $fieldName, ' : ', $row[$fieldKey], "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    die($e->getMessage());
}
