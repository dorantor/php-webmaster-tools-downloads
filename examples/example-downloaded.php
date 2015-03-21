<?php

include '../src/Gwt/Client.php';
include '../src/Gwt/Processor/ProcessorInterface.php';
include '../src/Gwt/Processor/ProcessorAbstract.php';
include '../src/Gwt/Processor/CsvWriter.php';
// load config values
include 'config.sample.php';

// full scale example for downloading everything what's possible and handling all errors
try {
    $client = Gwt_Client::create($email, $password)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->setLanguage('en')
    ;

    $sites = $client->getSites();

    $client->addProcessor(
        Gwt_Processor_CsvWriter::factory(array(
            'savePath'          => '.',
            'dateFormat'        => 'Ymd',
            'filenameTemplate'  => '{website}' . DIRECTORY_SEPARATOR . '{tableName}-{dateStart}-{dateEnd}.csv',
        ))
    );

    foreach ($sites as $site => $siteOptions) {
        echo "Processing {$site} \n";

        try {
            $client->setWebsite($site);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            continue;
        }

        // looping through all available tables
        $allowedTables = $client->getAllowedTableNames();
        foreach ($allowedTables as $tableName) {
            try { // any table may fail with exception
                $filename = $client->getTableData($tableName);
            } catch (Exception $e) {
                echo "Error during loading data for {$tableName} : ", $e->getMessage(), "\n";
                continue;
            }
        }
        if (!$filename) {
            echo "Data for {$tableName} from {$site} saved to {$filename} \n";
        } else {
            echo "Unable to save data for {$tableName} from {$site} \n";
        }
    }
} catch (Exception $e) {
    throw $e;
    die($e->getMessage());
}
