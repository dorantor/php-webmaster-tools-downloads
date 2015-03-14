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
 */

class GWTdata
{
    const HOST = 'https://www.google.com';
    const SERVICEURI = '/webmasters/tools/';

    public $_language = 'en';
    public $_tables;

    /**
     * Date for start of date range
     *
     * @var string
     */
    protected $_dateStart;

    /**
     * Date for end of date range
     *
     * @var string
     */
    protected $_dateEnd;

    public $_downloaded = array();
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
     * @return GWTdata
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
     * Shorthand for validating logged in state
     *
     * @throws Exception
     * @return void
     */
    public function validateIsLoggedIn()
    {
        if (!(bool) $this->_auth) {
            throw new Exception('Must be logged in.');
        }

        return $this;
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
     * @param DateTime $dateStart ISO 8601 formatted date string
     * @param DateTime $dateEnd ISO 8601 formatted date string
     * @return $this
     */
    public function setDateRange(DateTime $dateStart, DateTime $dateEnd)
    {
        $this->_dateStart   = $dateStart->format('Ymd');
        $this->_dateEnd     = $dateEnd->format('Ymd');

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
     *  Returns array of downloaded filenames.
     *
     *  @return  array   Array of filenames that have been written to disk.
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
    public function logIn($email, $pwd)
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

        if ($info['http_code'] == 200) {
            preg_match('/Auth=(.*)/', $output, $match);
            if (isset($match[1])) {
                $this->_auth = $match[1];
            } else {
                throw new Exception('Auth code not found.');
            }
        } else {
            throw new Exception(
                'Bad response code: ' . var_export($info['http_code'], true)
            );
        }

        return $this;
    }

    /**
     *  Attempts authenticated GET Request.
     *
     * @throws Exception
     * @param string $url       URL for the GET request.
     * @return mixed  Curl result as String,
     *                 or false (Boolean) when Authentication fails.
     */
    public function getData($url)
    {
        $this->validateIsLoggedIn();

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
     * @return mixed  Array with all site URLs registered in GWT account,
     *                 or false (Boolean) if request failed.
     */
    public function getSites()
    {
        $this->validateIsLoggedIn();

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
     *  @param string $url       Site URL registered in GWT.
     *  @return mixed  Array with keys TOP_PAGES and TOP_QUERIES,
     *                 or false (Boolean) when Authentication fails.
     */
    public function getDownloadUrls($url)
    {
        $this->validateIsLoggedIn();

        $_url = sprintf(
            self::SERVICEURI.'downloads-list?hl=%s&siteUrl=%s',
            $this->_language,
            urlencode($url)
        );
        $downloadList = $this->getData($_url);

        return json_decode($downloadList, true);
    }

    /**
     * Downloads the file based on the given URL.
     *
     * @param string $site       Site URL available in GWT Account.
     * @param string $savepath   Optional path to save CSV to (no trailing slash!).
     * @return $this
     */
    public function downloadCSV($site, $savepath = '.')
    {
        $this->validateIsLoggedIn();

        $downloadUrls = $this->getDownloadUrls($site);
        $filename = parse_url($site, PHP_URL_HOST) . '-' . date('Ymd-His');
        $tables = $this->_tables;
        foreach ($tables as $table) {
            switch ($table) {
                case 'CRAWL_ERRORS':
                    $this->downloadCSV_CrawlErrors($site, $savepath);
                    break;
                case 'CONTENT_ERRORS':
                case 'CONTENT_KEYWORDS':
                case 'INTERNAL_LINKS':
                case 'EXTERNAL_LINKS':
                case 'SOCIAL_ACTIVITY':
                case 'LATEST_BACKLINKS':
                    $this->downloadCSV_XTRA($site, $table, $savepath);
                    break;
                default:
                    $finalName = "$savepath/$table-$filename.csv";
                    $finalUrl = $downloadUrls[$table] .'&prop=ALL&db=%s&de=%s&more=true';
                    $finalUrl = sprintf($finalUrl, $this->_dateStart, $this->_dateEnd);
                    $this->saveData($finalUrl, $finalName);
            }
        }

        return $this;
    }

    /**
     *  Downloads "unofficial" downloads based on the given URL.
     *
     * @param string $site       Site URL available in GWT Account.
     * @param string $savepath   Optional path to save CSV to (no trailing slash!).
     * @return $this
     */
    public function downloadCSV_XTRA($site, $tableName, $savepath='.')
    {
        $this->validateIsLoggedIn();

        $options = $this->getTableOptions($tableName);
        $tokenUri = $options['token_uri'];
        $tokenDelimiter = $options['token_delimiter'];
        $filenamePrefix = $tableName;
        $dlUri = $options['dl_uri'];

        $uri = sprintf(
            self::SERVICEURI . $tokenUri . '?hl=%s&siteUrl=%s',
            $this->_language, $site
        );
        $token = $this->getToken($uri, $tokenDelimiter, $dlUri);
        $filename = parse_url($site, PHP_URL_HOST) . '-' . date('Ymd-His');
        $finalName = "$savepath/$filenamePrefix-$filename.csv";

        $url = sprintf(
            self::SERVICEURI . $dlUri . '?hl=%s&siteUrl=%s&security_token=%s&prop=ALL&db=%s&de=%s&more=true',
            $this->_language, $site, $token, $this->_dateStart, $this->_dateEnd
        );
        $this->saveData($url, $finalName);

        return $this;
    }

    /**
     * Downloads the Crawl Errors file based on the given URL.
     *
     * @param string $site      Site URL available in GWT Account.
     * @param string $savepath  Optional: Path to save CSV to (no trailing slash!).
     * @param bool $separated   Optional: If true, the method saves separated CSV files
     *                             for each error type. Default: Merge errors in one file.
     * @return $this
     */
    public function downloadCSV_CrawlErrors($site, $savepath='.', $separated=false)
    {
        $this->validateIsLoggedIn();

        $type_param = 'we';
        $filename = parse_url($site, PHP_URL_HOST) . '-' . date('Ymd-His');
        if ($separated) {
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
                    $url = self::SERVICEURI . 'crawl-errors-dl?hl=%s&siteUrl=%s&security_token=%s&type=%s&sort=%s';
                    $_url = sprintf($url, $this->_language, $site, $token, $typeid, $sortid);
                    $this->saveData($_url,$finalName);
                }
            }
        } else {
            $uri = self::SERVICEURI."crawl-errors?hl=en&siteUrl=$site&tid=$type_param";
            $token = $this->getToken($uri, 'x26');
            $finalName = "$savepath/CRAWL_ERRORS-$filename.csv";
            $url = self::SERVICEURI.'crawl-errors-dl?hl=%s&siteUrl=%s&security_token=%s&type=0';
            $_url = sprintf($url, $this->_language, $site, $token);
            $this->saveData($_url, $finalName);
        }

        return $this;
    }

    /**
     * Saves data to a CSV file based on the given URL.
     *
     * @param string $finalUrl      CSV Download URI.
     * @param string $finalName     Filepointer to save location.
     * @return bool
     */
    private function saveData($finalUrl, $finalName)
    {
        $data = $this->getData($finalUrl);
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
