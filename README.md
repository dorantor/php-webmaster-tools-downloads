# GWT data tools: Download website data from Google Webmaster Tools for automated processing

## Introduction

NB! This fork is not backward compatible with eyecatchup/php-webmaster-tools-downloads.
Consider it as major version change.
This project provides an easy way to automate retrieval of data from Google Webmaster Tools
and makes it possible to either save data as it is(in CSV) or have it in PHP arrays for further processing(saving to DB, for example).
Compared to ancestor it's a little bit more verbose, but provides more control, which is important for integration into bigger projects.
Best description of how to use it you will find in examples. Feel free to copy code from there.

### Features

List of data sources is almost the same as in eyecatchup/php-webmaster-tools-downloads except dropped support for CRAWL_ERRORS(see below):

 - TOP_PAGES
 - TOP_QUERIES
 - CONTENT_ERRORS
 - CONTENT_KEYWORDS
 - LATEST_BACKLINKS
 - INTERNAL_LINKS
 - EXTERNAL_LINKS
 - SOCIAL_ACTIVITY

Also you can choose what you want to do with downloaded data using processors(see examples below). By default you have only three processors:
 - CsvWriter - if you need similar functionality to eyecatchup/php-webmaster-tools-downloads
 - Array - if you need it converted to php array
 - ArrayFilter - if you want to cleanup a little prepared php array

Right now prepared array consists of two pieces:
 - field names
 - data itself w/o field names
It makes it more memory efficient. But you always can write your own processor!

### CRAWL_ERRORS notice

In case you want to automate downloading of <b>crawl errors</b>, please go here: https://github.com/eyecatchup/GWT_CrawlErrors-php

## Installation

Install the library using [composer][1]. Add the following to your `composer.json`:

```json
{
    "require": {
        "dorantor/php-webmaster-tools-downloads": "dev-master"
    },
    "minimum-stability": "dev"
}
```

Now run the `install` command.

```sh
$ composer.phar install
```
## Examples

In all examples all error handling is omitted.

### Example 1 - Introduction

In case you just need raw csv data:

```php
<?php
    $client = Gwt_Client::create($email, $password)
        ->setDaterange(
            new DateTime('-10 day', new DateTimeZone('UTC')),
            new DateTime('-9 day',  new DateTimeZone('UTC'))
        )
        ->setWebsite($website)
    ;

    $csvData = $client->getTopQueriesTableData();
```

### Example 2 - Save CSV files for me

Next example will fetch and save data for TOP_QUERIES to `www.domain.com/TOP_QUERIES-YYYYmmdd-YYYYmmdd.csv` in current folder:

```php
<?php

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
```
Take note, it will try to create all required directories, but by default it will created with 0777. Usually it is not what you want. You have few options here:
- use [umask()][2]
- create directories by yourself

### Example 3 - I want clean data!

Still quite easy if we don't care about errors. BTW, processors can be chained:

```php
<?php
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

    list($fieldNames, $data) = $client->getTopQueriesTableData();
```

More details and examples you can find in `examples` folder.

[1]: http://getcomposer.org/
[2]: http://php.net/manual/en/function.umask.php
