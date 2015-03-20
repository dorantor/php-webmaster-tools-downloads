<?php

include '../src/Gwt/Client.php';

try {
    $email = 'username@gmail.com';
    $passwd = '******';

    # Language must be set as valid ISO 639-1 language code.
    $language = 'de';

    # Valid values are 'TOP_PAGES', 'TOP_QUERIES', 'CRAWL_ERRORS',
    # 'CONTENT_ERRORS', 'CONTENT_KEYWORDS', 'INTERNAL_LINKS',
    # 'EXTERNAL_LINKS' and 'SOCIAL_ACTIVITY'.
    $tables = array('TOP_QUERIES');

    $gdata = Gwt_Client::create($email, $passwd)
        ->setLanguage($language)
        ->setDaterange(
            DateTime::createFromFormat('Y-m-d', '2012-01-10'),
            DateTime::createFromFormat('Y-m-d', '2012-01-12')
        )
        ->setTables($tables)
    ;

    $sites = $gdata->getSites();
    foreach($sites as $site) {
        $gdata->downloadCSV($site);
    }
} catch (Exception $e) {
    die($e->getMessage());
}
