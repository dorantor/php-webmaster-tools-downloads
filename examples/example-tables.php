<?php

include '../src/Gwt/Client.php';
include '../src/Gwt/Processor/ProcessorInterface.php';
include '../src/Gwt/Processor/ProcessorAbstract.php';
include '../src/Gwt/Processor/CsvWriter.php';
// load config values
include 'config.sample.php';

try {
    # Valid values are 'TOP_PAGES', 'TOP_QUERIES', 'CRAWL_ERRORS',
    # 'CONTENT_ERRORS', 'CONTENT_KEYWORDS', 'INTERNAL_LINKS',
    # 'EXTERNAL_LINKS' and 'SOCIAL_ACTIVITY'.
    $tables = array('TOP_QUERIES');

    $client = Gwt_Client::create($email, $passwd)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->addProcessor(
            Gwt_Processor_CsvWriter::factory(array(
                'savePath'          => '.',
                'dateFormat'        => 'Ymd',
                'filenameTemplate'  => '{website}' . DIRECTORY_SEPARATOR . '{tableName}-{dateStart}-{dateEnd}.csv',
            ))
        )
    ;

    $filenames = $client->getTableData($tableName);

} catch (Exception $e) {
    die($e->getMessage());
}