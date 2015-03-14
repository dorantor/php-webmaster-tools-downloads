<?php

include '../src/gwtdata.php';

try {
    $email = 'username@gmail.com';
    $passwd = '******';

    # Language must be set as valid ISO 639-1 language code.
    $language = 'de';

    # Valid values are 'TOP_PAGES', 'TOP_QUERIES', 'CRAWL_ERRORS',
    # 'CONTENT_ERRORS', 'CONTENT_KEYWORDS', 'INTERNAL_LINKS',
    # 'EXTERNAL_LINKS' and 'SOCIAL_ACTIVITY'.
    $tables = array('TOP_QUERIES');

    $gdata = GWTdata::create($email, $passwd)
        ->setLanguage($language)
        # Dates must be in valid ISO 8601 format.
        ->setDaterange(
            DateTime::createFromFormat('Y-m-d', '2012-01-10'),
            DateTime::createFromFormat('Y-m-d', '2012-01-12')
        )
        ->SetTables($tables)
    ;

    $sites = $gdata->GetSites();
    foreach($sites as $site) {
        $gdata->DownloadCSV($site);
    }
} catch (Exception $e) {
    die($e->getMessage());
}
