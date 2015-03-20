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
     * List of sites on account with options
     *
     * @var array
     */
    protected $_sites = null;

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
     * @return mixed
     */
    public function getTableData($tableName)
    {
        switch ($tableName) {
            case 'CONTENT_ERRORS':
            case 'CONTENT_KEYWORDS':
            case 'INTERNAL_LINKS':
            case 'EXTERNAL_LINKS':
            case 'SOCIAL_ACTIVITY':
            case 'LATEST_BACKLINKS':
                $data = $this->downloadCSV_XTRA(
                    $this->getWebsite(),
                    $tableName,
                    $this->getDateStart(),
                    $this->getDateEnd()
                );
                break;
            default: // TOP_QUERIES || TOP_PAGES
                $downloadUrls = $this->getDownloadUrls($this->getWebsite());
                $finalUrl = $downloadUrls[$tableName] . '&prop=ALL&db=%s&de=%s&more=true';
                $finalUrl = sprintf(
                    $finalUrl,
                    $this->getDateStart()->format('Ymd'), $this->getDateEnd()->format('Ymd')
                );
                $data = $this->getData($finalUrl);
        }

        foreach ($this->_processors as $processor) {
            $data = $processor->process($data, $tableName);
        }

        return $data;
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
     * @throws Exception
     * @param $website
     * @return $this
     */
    public function setWebsite($website)
    {
        $sites = $this->getSites();

        // if wrong name is given, no requests could be made
        if (!array_key_exists($website, $sites)) {
            throw new Exception('Site ' . var_export($website, true) . ' not in current account.');
        }
        // if site is not verified, no requests could be made
        if (!$sites[$website]['verified']) {
            throw new Exception('Site ' . var_export($website, true) . ' is not verified.');
        }

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
     * Get date start value
     *
     * @throws Exception
     * @return DateTime
     */
    public function getDateStart()
    {
        if (null === $this->_dateStart) {
            throw new Exception('You must set a dateStart value.');
        }

        return $this->_dateStart;
    }

    /**
     * Get date end value
     *
     * @throws Exception
     * @return DateTime
     */
    public function getDateEnd()
    {
        if (null === $this->_dateEnd) {
            throw new Exception('You must set a dateEnd value.');
        }

        return $this->_dateEnd;
    }

    /**
     * Add data processor
     *
     * @param Gwt_Processor_ProcessorInterface $processor
     * @return $this
     */
    public function addProcessor(Gwt_Processor_ProcessorInterface $processor)
    {
        $processor->setClient($this);
        $this->_processors[] = $processor;

        return $this;
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
                . ' for url ' . var_export($url, true)
            );
        }

        return $result;
    }

    /**
     * Gets all available sites from Google Webmaster Tools account.
     *
     * @throws Exception
     * @param bool $reload
     * @return array  Array with all site URLs registered in GWT account
     */
    public function getSites($reload = false)
    {
        if (null === $this->_sites || $reload) {
            $feed = $this->getData(self::SERVICEURI . 'feeds/sites/');
            if ($feed !== false) {
                $sites = array();
                $doc = new DOMDocument();
                $doc->loadXML($feed);
                foreach ($doc->getElementsByTagName('entry') as $node) {
                    $verified = $node
                        ->getElementsByTagNameNS(
                            'http://schemas.google.com/webmasters/tools/2007', 'verified'
                        )->item(0)
                        ->nodeValue;
                    $sites[$node->getElementsByTagName('title')->item(0)->nodeValue] = array(
                        'verified' => $verified == 'true',
                    );
                }

                $this->_sites = $sites;
            } else {
                throw new Exception('Got no feed data for sites.');
            }
        }

        return $this->_sites;
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
     *  Regular Expression to find the Security Token for a download file.
     *
     * @throws Exception
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

        if (!isset($matches[1][0])) {
            throw new Exception('Failed to extract token');
        }

        return substr($matches[1][0], 3, -1);
    }
}
