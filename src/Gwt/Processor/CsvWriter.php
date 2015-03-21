<?php
/**
 *  PHP class for saving CSV files from Google Webmaster Tools.
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
 * @author Anton Kolenkov <dorantor@gmail.com>
 * @author Stephan Schmitz <eyecatchup@gmail.com>
 * @link   https://github.com/dorantor/php-webmaster-tools-downloads/
 * @link   https://github.com/eyecatchup/php-webmaster-tools-downloads/
 */

/**
 * Class for writing downloaded CSV files from GWT
 */
class Gwt_Processor_CsvWriter
    extends Gwt_Processor_ProcessorAbstract
{
    /**
     * Path for saving files
     *
     * @var string
     */
    private $_savePath = '.';

    /**
     * Date format used in filename
     *
     * @var string
     */
    private $_dateFormat = 'Ymd';

    /**
     * Template used to build filename
     *
     * @var string
     */
    private $_filenameTemplate = '{website}-{tableName}-{dateStart}-{dateEnd}.csv';

    /**
     * Process data
     *
     * @param mixed $data
     * @param string $tableName
     * @return mixed
     */
    public function process($data, $tableName)
    {
        $filename = $this->getSavePath() . DIRECTORY_SEPARATOR . $this->buildFilename($tableName);

        if ($this->saveData($data, $filename)) {
            return $filename;
        }

        return null;
    }

    /**
     * Set path where to save files
     *
     * @param $savePath
     * @return $this
     */
    public function setSavePath($savePath)
    {
        $this->_savePath = $savePath;

        return $this;
    }

    /**
     * Get path where to save files
     *
     * @return string
     */
    public function getSavePath()
    {
        return $this->_savePath;
    }

    /**
     * Get filename template
     *
     * @return string
     */
    public function getFilenameTemplate()
    {
        return $this->_filenameTemplate;
    }

    /**
     * Set template for filename
     *
     * @param $template
     * @return $this
     */
    public function setFilenameTemplate($template)
    {
        $this->_filenameTemplate = $template;

        return $this;
    }

    /**
     * Get date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_dateFormat;
    }

    /**
     * Set date format to be used in file name
     *
     * @param $dateFormat
     * @return $this
     */
    public function setDateFormat($dateFormat)
    {
        $this->_dateFormat = $dateFormat;

        return $this;
    }

    /**
     * Build filename using template
     *
     * @param $tableName
     * @return mixed|string
     */
    protected function buildFilename($tableName)
    {
        $filename = $this->getFilenameTemplate();
        $filename = str_replace(
            '{website}',
            parse_url($this->getClient()->getWebsite(), PHP_URL_HOST),
            $filename
        );
        $filename = str_replace('{tableName}', $tableName, $filename);
        $filename = str_replace(
            '{dateStart}',
            $this->getClient()->getDateStart()->format($this->getDateFormat()),
            $filename
        );
        $filename = str_replace(
            '{dateEnd}',
            $this->getClient()->getDateEnd()->format($this->getDateFormat()),
            $filename
        );

        if (preg_match('#{[^}]*}#', $filename, $matches)) {
            throw new Exception('Unknown placeholder is found: ' . var_export($matches[0], true));
        }

        return $filename;
    }

    /**
     * Saves data to a CSV file
     * Tries to create directories if possible. Use umask() to change default 0777 permissions.
     *
     * @see umask()
     * @param string $data      Downloaded CSV data
     * @param string $finalName path with target filename
     * @return bool
     * @todo should throw specific exceptions
     */
    private function saveData($data, $finalName)
    {
        $dir = dirname($finalName);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                return false;
            }
        }

        if (strlen($data) > 1 && file_put_contents($finalName, $data)) {
            return true;
        } else {
            return false;
        }
    }
}