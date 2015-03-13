<?php

include '../src/gwtdata.php';

try {
    $email = "username@gmail.com";
    $passwd = "******";

    # If hardcoded, don't forget trailing slash!
    $website = "http://www.domain.com/";

    # Valid values are "TOP_PAGES", "TOP_QUERIES", "CRAWL_ERRORS",
    # "CONTENT_ERRORS", "CONTENT_KEYWORDS", "INTERNAL_LINKS",
    # "EXTERNAL_LINKS" and "SOCIAL_ACTIVITY".
    $tables = array("TOP_QUERIES");

    $gdata = new GWTdata();
    if ($gdata->logIn($email, $passwd) === true) {
        $gdata->setTables($tables);
        $gdata->downloadCSV($website);
    }
} catch (Exception $e) {
    die($e->getMessage());
}