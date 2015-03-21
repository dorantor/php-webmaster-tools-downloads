<?php
/**
 *  PHP class for filtering array data from GWT Google Webmaster Tools.
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
 * Class for filtering array data from GWT
 */
class Gwt_Processor_ArrayFilter
    extends Gwt_Processor_ProcessorAbstract
{
    /**
     * Column names that should be removed from data
     *
     * @var array
     */
    private $_columnNamesToRemove = array();

    /**
     * Explicitly set column keys to remove
     *
     * @var array
     */
    private $_columnKeysToRemove = array();

    /**
     * Process data
     *
     * @param mixed $data
     * @param string $tableName
     * @return mixed
     */
    public function process($data, $tableName)
    {
        list($fieldNames, $result) = $data;

        $columnKeys = $this->getColumnsKeysToRemove($fieldNames);

        foreach ($columnKeys as $key) {
            unset($fieldNames[$key]);
        }

        foreach ($result as &$row) {
            foreach ($columnKeys as $key) {
                unset($row[$key]);
            }
        }

        return array($fieldNames, $result);
    }

    /**
     * Get list of column keys to remove
     *
     * @param array $fieldNames
     * @return array
     */
    protected function getColumnsKeysToRemove(array $fieldNames)
    {
        $result = array();
        foreach ($fieldNames as $key => $value) {
            if (in_array($value, $this->getColumnNamesToRemove())) {
                $result[] = $key;
            }
        }

        return array_unique(
            array_merge(
                $result,
                $this->_columnKeysToRemove
            )
        );
    }

    /**
     * Set column keys to remove
     *
     * @param array $keys
     * @return $this
     */
    public function setColumnKeysToRemove(array $keys)
    {
        $this->_columnKeysToRemove = $keys;

        return $this;
    }

    /**
     * Set columns names which should be removed
     *
     * @param array $names
     * @return $this
     */
    public function setColumnNamesToRemove(array $names)
    {
        $this->_columnNamesToRemove = $names;

        return $this;
    }

    /**
     * Get list of column names to remove
     *
     * @return array
     */
    public function getColumnNamesToRemove()
    {
        return $this->_columnNamesToRemove;
    }
}