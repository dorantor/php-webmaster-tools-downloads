<?php
/**
 *  PHP class for downloading CSV files from Google Webmaster Tools.
 *
 *  This class does NOT require the Zend gdata package be installed
 *  in order to run.
 *
 *  Copyright 2012 eyecatchUp UG. All Rights Reserved.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 * @author Stephan Schmitz <eyecatchup@gmail.com>
 * @author Anton Kolenkov <dorantor@gmail.com>
 * @link   https://code.google.com/p/php-webmaster-tools-downloads/
 * @link   https://github.com/eyecatchup/php-webmaster-tools-downloads/
 * @link   https://github.com/dorantor/php-webmaster-tools-downloads/
 */

/**
 * Class for downloading data from CSV files from Google Webmaster Tools.
 *
 * @method mixed getTopPagesTableData()
 * @method mixed getTopQueriesTableData()
 * @method mixed getCrawlErrorsTableData()
 * @method mixed getContentKeywordsTableData()
 * @method mixed getInternalLinksTableData()
 * @method mixed getExternalLinksTableData()
 * @method mixed getSocialActivityTableData()
 * @method mixed getLatestBacklinksTableData()
 */
class Gwt_Client
{
    const HOST = 'https://www.google.com';
    const SERVICEURI = '/webmasters/tools/';

    /**
     * Language
     *
     * @var string
     */
    protected $_language = 'en';

    /**
     * Website
     *
     * @var string
     */
    protected $_website = null;

    /**
     * Tables to download
     *
     * @deprecated
     * @var array
     */
    public $_tables;

    /**
     * Date for start of date range
     *
     * @var DateTime
     */
    protected $_dateStart;

    /**
     * Date for end of date range
     *
     * @var DateTime
     */
    protected $_dateEnd;

    /**
     * Downloaded files
     *
     * @deprecated
     * @var array
     */
    public $_downloaded = array();

    /**
     * Skipped tables
     *
     * @deprecated
     * @var array
     */
    public $_skipped = array();

    /**
     * After successful login will contain auth key
     *
     * @var mixed
     */
    private $_auth = false;

    /**
     * Constructor
     * It would be better to keep constructor free of any params
     *
     * return void
     */
    private function __construct()
    {
        $this->_tables = $this->getAllowedTableNames();
    }

    /**
     * Factory for new object
     *
     * @param string $login
     * @param string $pass
     * @return Gwt_Client
     */
    public static function create($login, $pass)
    {
        $self = new self();
        return $self->logIn($login, $pass);
    }

    /**
     * Get list of possible tables
     *
     * @return array
     */
    public function getAllowedTableNames()
    {
        return array(
            'TOP_PAGES',
            'TOP_QUERIES',
            'CRAWL_ERRORS',
            'CONTENT_ERRORS',
            'CONTENT_KEYWORDS',
            'INTERNAL_LINKS',
            'EXTERNAL_LINKS',
            'SOCIAL_ACTIVITY',
            'LATEST_BACKLINKS',
        );
    }

    /**
     * Magic method to support shorthands for getTableData methods
     * getTopPagesTableData
     *
     * @throws Exception
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, array $args)
    {
        // p.ex: getTopPagesTableData or gettoppagestabledata
        if (preg_match('#get([a-z]+)TableData#i', $name, $matches)) {
            // prepare table names w/o underscore
            $names = array_map(
                function ($item)
                {
                    return str_replace('_', '', $item);
                },
                $this->getAllowedTableNames()
            );

            // find corresponding table name
            $key = array_search(
                strtoupper($matches[1]),
                $names
            );

            array_unshift($args, $this->getAllowedTableNames()[$key]);
            // call getTableData
            return call_user_func_array(
                array($this, 'getTableData'),
                $args
            );
        }

        throw new Exception('Method ' . var_export($name, true) . ' does not exist.');
    }

    /**
     * Get data for requested table
     *
     * @param string $tableName
     * @param string $site
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     * @param string $lang
     * @return string
     */
    public function getTableData($tableName)
    {
        switch ($tableName) {
            case 'CRAWL_ERRORS':
                return $this->downloadCSV_CrawlErrors($this->getWebsite());
                break;
            case 'CONTENT_ERRORS':
            case 'CONTENT_KEYWORDS':
            case 'INTERNAL_LINKS':
            case 'EXTERNAL_LINKS':
            case 'SOCIAL_ACTIVITY':
            case 'LATEST_BACKLINKS':
                return $this->downloadCSV_XTRA(
                    $this->getWebsite(),
                    $tableName,
                    $this->_dateStart,
                    $this->_dateEnd
                );
                break;
            default: // TOP_QUERIES || TOP_PAGES
                $downloadUrls = $this->getDownloadUrls($this->getWebsite());
                $finalUrl = $downloadUrls[$tableName] . '&prop=ALL&db=%s&de=%s&more=true';
                $finalUrl = sprintf(
                    $finalUrl,
                    $this->_dateStart->format('Ymd'), $this->_dateEnd->format('Ymd')
                );
                return $this->getData($finalUrl);
        }
    }

    /**
     * Get errors list
     *
     * @return array
     */
    private function getErrTablesSort()
    {
        return array(
            0 => 'http',
            1 => 'not-found',
            2 => 'restricted-by-robotsTxt',
            3 => 'unreachable',
            4 => 'timeout',
            5 => 'not-followed',
            'kAppErrorSoft-404s' => 'soft404',
            'sitemap' => 'in-sitemaps',
        );
    }

    /**
     * Get error types
     *
     * @return array
     */
    private function getErrTableTypes()
    {
        return array(
            0 => 'web-crawl-errors',
            1 => 'mobile-wml-xhtml-errors',
            2 => 'mobile-chtml-errors',
            3 => 'mobile-operator-errors',
            4 => 'news-crawl-errors',
        );
    }

    /**
     * Get options for given table
     *
     * @throws Exception
     * @param string $tableName
     * @return array
     */
    private function getTableOptions($tableName)
    {
        $options = array(
            'CONTENT_ERRORS' => array(
                'token_uri'         => 'html-suggestions',
                'token_delimiter'   => '\)',
                'dl_uri'            => 'content-problems-dl',
            ),
            'CONTENT_KEYWORDS' => array(
                'token_uri'         => 'keywords',
                'token_delimiter'   => '\)',
                'dl_uri'            => 'content-words-dl',
            ),
            'INTERNAL_LINKS' => array(
                'token_uri'         => 'internal-links',
                'token_delimiter'   => '\)',
                'dl_uri'            => 'internal-links-dl',
            ),
            'EXTERNAL_LINKS' => array(
                'token_uri'         => 'external-links-domain',
                'token_delimiter'   => '\)',
                'dl_uri'            => 'external-links-domain-dl',
            ),
            'SOCIAL_ACTIVITY' => array(
                'token_uri'         => 'social-activity',
                'token_delimiter'   => 'x26',
                'dl_uri'            => 'social-activity-dl',
            ),
            'LATEST_BACKLINKS' => array(
                'token_uri'         => 'external-links-domain',
                'token_delimiter'   => '\)',
                'dl_uri'            => 'backlinks-latest-dl',
            ),
        );

        if (!array_key_exists($tableName, $options)) {
            throw new Exception('Requested options for unknown table.');
        }

        return $options[$tableName];
    }

    /**
     * Sets content language.
     *
     * @param string $str Valid ISO 639-1 language code, supported by Google.
     * @return $this
     */
    public function setLanguage($str)
    {
        $this->_language = $str;

        return $this;
    }

    /**
     * Get currently chosen language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * Set website value
     *
     * @param $website
     * @return $this
     */
    public function setWebsite($website)
    {
        $this->_website = $website;

        return $this;
    }

    /**
     * Get website name
     *
     * @throws Exception
     * @return string
     */
    public function getWebsite()
    {
        if (null === $this->_website) {
            throw new Exception('You must set a website value.');
        }

        return $this->_website;
    }


    /**
     * Sets features that should be downloaded.
     *
     * @param array $tables For valid values see getAllowedTableNames() method
     * @return $this
     */
    public function setTables(array $tables)
    {
        $this->_tables = array_intersect(
            $this->getAllowedTableNames(),
            $tables
        );

        return $this;
    }

    /**
     *  Sets date range for download data.
     *
     * @throws Exception
     * @param DateTime $dateStart
     * @param DateTime $dateEnd
     * @return $this
     */
    public function setDateRange(DateTime $dateStart, DateTime $dateEnd)
    {
        $this->_dateStart   = $dateStart;
        $this->_dateEnd     = $dateEnd;

        return $this;
    }

    /**
     *  Returns array of downloaded filenames.
     *
     *  @return  array   Array of filenames that have been written to disk.
     */
    public function getDownloadedFiles()
    {
        return $this->_downloaded;
    }

    /**
     * Returns array of downloaded filenames.
     *
     * @return array   Array of skipped tables (no data or was not able to save)
     * @deprecated
     */
    public function getSkippedFiles()
    {
        return $this->_skipped;
    }

    /**
     * Attempts to log into the specified Google account.
     *
     * @throws Exception
     * @param string $email     User's Google email address.
     * @param string $pwd       Password for Google account.
     * @return boolean          Login result
     */
    protected function logIn($email, $pwd)
    {
        $url = self::HOST . '/accounts/ClientLogin';
        $postRequest = array(
            'accountType'   => 'HOSTED_OR_GOOGLE',
            'Email'         => $email,
            'Passwd'        => $pwd,
            'service'       => 'sitemaps',
            'source'        => 'Google-WMTdownloadscript-0.1-php'
        );

        // when the first char of $pass is an @ symbol,
        // send data in CURLOPT_POSTFIELDS as urlencoded string.
        if ('@' === (string) $pwd[0]) {
            $postRequest = http_build_query($postRequest);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postRequest);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200) {
            throw new Exception(
                'Bad response code: ' . var_export($info['http_code'], true)
            );
        }

        preg_match('/Auth=(.*)/', $output, $match);
        if (isset($match[1])) {
            $this->_auth = $match[1];
        } else {
            throw new Exception('Auth code not found.');
        }

        return $this;
    }

    /**
     * Attempts authenticated GET Request.
     *
     * @throws Exception
     * @param string $url  URL for the GET request.
     * @return mixed       Curl result as String,
     *                     or false (Boolean) when Authentication fails.
     */
    protected function getData($url)
    {
        $url = self::HOST . $url;
        $head = array(
            'Authorization: GoogleLogin auth=' . $this->_auth,
            'GData-Version: 2'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] != 200) {
            throw new Exception(
                'Bad response code: ' . var_export($info['http_code'], true)
            );
        }

        return $result;
    }

    /**
     * Gets all available sites from Google Webmaster Tools account.
     *
     * @throws Exception
     * @return array  Array with all site URLs registered in GWT account
     */
    public function getSites()
    {
        $feed = $this->getData(self::SERVICEURI . 'feeds/sites/');
        if ($feed !== false) {
            $sites = array();
            $doc = new DOMDocument();
            $doc->loadXML($feed);
            foreach ($doc->getElementsByTagName('entry') as $node) {
                array_push(
                    $sites,
                    $node->getElementsByTagName('title')->item(0)->nodeValue
                );
            }

            return $sites;
        }

        throw new Exception('Got no feed data for sites.');
    }

    /**
     *  Gets the download links for an available site
     *  from the Google Webmaster Tools account.
     *
     *  @param string $site Site URL registered in GWT.
     *  @return mixed  Array with keys TOP_PAGES and TOP_QUERIES
     */
    protected function getDownloadUrls($site)
    {
        $url = sprintf(
            self::SERVICEURI.'downloads-list?hl=%s&siteUrl=%s',
            $this->getLanguage(), urlencode($site)
        );
        $downloadList = $this->getData($url);

        return json_decode($downloadList, true);
    }

    /**
     *  Downloads "unofficial" downloads based on the given URL.
     *
     * @param string $site       Site URL available in GWT Account.
     * @param string $tableName  Table name to be downloaded
     * @return mixed downloaded data
     */
    private function downloadCSV_XTRA($site, $tableName, DateTime $dateStart, DateTime $dateEnd)
    {
        $options = $this->getTableOptions($tableName);

        $uri = sprintf(
            self::SERVICEURI . $options['token_uri'] . '?hl=%s&siteUrl=%s',
            $this->getLanguage(), $site
        );
        $token = $this->getToken($uri, $options['token_delimiter'], $options['dl_uri']);

        $url = sprintf(
            self::SERVICEURI . $options['dl_uri'] . '?hl=%s&siteUrl=%s&security_token=%s&prop=ALL&db=%s&de=%s&more=true',
            $this->getLanguage(), $site, $token, $dateStart->format('Ymd'), $dateEnd->format('Ymd')
        );

        return $this->getData($url);
    }

    /**
     * Downloads the Crawl Errors file based on the given URL.
     *
     * @todo Make separated crawl errors accessible
     * @param string $site      Site URL available in GWT Account.
     * @param bool $separated   Optional: If true, the method saves separated CSV files
     *                             for each error type. Default: Merge errors in one file.
     * @return $this
     */
    private function downloadCSV_CrawlErrors($site, $separated = false)
    {
        $type_param = 'we';
        $filename = parse_url($site, PHP_URL_HOST) . '-' . date('Ymd-His');
        if ($separated) {
            $data = array();
            foreach ($this->getErrTablesSort() as $sortid => $sortname) {
                foreach ($this->getErrTableTypes() as $typeid => $typename) {
                    if ($typeid == 1) {
                        $type_param = 'mx';
                    } else if($typeid == 2) {
                        $type_param = 'mc';
                    } else {
                        $type_param = 'we';
                    }
                    $uri = self::SERVICEURI . "crawl-errors?hl=en&siteUrl=$site&tid=$type_param";
                    $token = $this->getToken($uri, 'x26');
                    $finalName = "$savepath/CRAWL_ERRORS-$typename-$sortname-$filename.csv";
                    $url = sprintf(
                        self::SERVICEURI . 'crawl-errors-dl?hl=%s&siteUrl=%s&security_token=%s&type=%s&sort=%s',
                        $this->getLanguage(), $site, $token, $typeid, $sortid
                    );

                    // TODO: find a better solution - this one might require a lot of memory
                    $data[$sortname][$typename] = $this->getData($url);
                }
            }

            return $data;
        } else {
            $uri = self::SERVICEURI."crawl-errors?hl=en&siteUrl=$site&tid=$type_param";
            $token = $this->getToken($uri, 'x26');
            $url = sprintf(
                self::SERVICEURI.'crawl-errors-dl?hl=%s&siteUrl=%s&security_token=%s&type=0',
                $this->getLanguage(), $site, $token
            );

            return $this->getData($url);
        }
    }

    /**
     * Downloads the file based on the given URL.
     *
     * @param string $site       Site URL available in GWT Account.
     * @param string $savepath   Optional path to save CSV to (no trailing slash!).
     * @return $this
     * @deprecated
     */
    public function downloadCSV($site, $savepath = '.')
    {
        $filename = parse_url($site, PHP_URL_HOST) . '-' . date('Ymd-His');
        $tables = $this->_tables;
        foreach ($tables as $table) {
            $this->saveData(
                $this->getTableData($table, $site, $this->_dateStart, $this->_dateEnd, $this->getLanguage()),
                "$savepath/$table-$filename.csv"
            );
        }

        return $this;
    }

    /**
     * Saves data to a CSV file based on the given URL.
     *
     * @param string $data      Downloaded CSV data
     * @param string $finalName Filepointer to save location.
     * @return bool
     * @deprecated
     */
    private function saveData(&$data, $finalName)
    {
        if (strlen($data) > 1 && file_put_contents($finalName, utf8_decode($data))) {
            array_push($this->_downloaded, realpath($finalName));

            return true;
        } else {
            array_push($this->_skipped, $finalName);

            return false;
        }
    }

    /**
     *  Regular Expression to find the Security Token for a download file.
     *
     * @param string $uri       A Webmaster Tools Desktop Service URI.
     * @param string $delimiter Trailing delimiter for the regex.
     * @param string $dlUri
     * @return string           Security token.
     */
    private function getToken($uri, $delimiter, $dlUri='')
    {
        $matches = array();
        $tmp = $this->getData($uri);
        preg_match_all("#$dlUri.*?46security_token(.*?)$delimiter#si", $tmp, $matches);
        return isset($matches[1][0])
            ? substr($matches[1][0], 3, -1)
            : ''
            ;
    }
}
