<?php

include '../src/Gwt/Client.php';
include '../src/Gwt/Processor/ProcessorInterface.php';
include '../src/Gwt/Processor/ProcessorAbstract.php';
include '../src/Gwt/Processor/CsvWriter.php';
// load config values
include 'config.sample.php';

try {
    # Valid values are 'TOP_PAGES', 'TOP_QUERIES',
    # 'CONTENT_ERRORS', 'CONTENT_KEYWORDS', 'INTERNAL_LINKS',
    # 'EXTERNAL_LINKS' and 'SOCIAL_ACTIVITY'.
    $tableName = 'TOP_QUERIES';

    $client = Gwt_Client::create($email, $password)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->setWebsite($website)
        ->addProcessor(
            Gwt_Processor_CsvWriter::factory(array(
                'savePath'          => '.',
                'dateFormat'        => 'Ymd',
                'filenameTemplate'  => '{website}' . DIRECTORY_SEPARATOR . '{tableName}-{dateStart}-{dateEnd}.csv',
            ))
        )
    ;

    echo $client->getTableData($tableName);


} catch (Exception $e) {
    die($e->getMessage());
}