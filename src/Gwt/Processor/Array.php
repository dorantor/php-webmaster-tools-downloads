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
 * Class for converting downloaded CSV files from GWT to arrays
 */
class Gwt_Processor_Array
    extends Gwt_Processor_ProcessorAbstract
{
    /**
     * Process data
     *
     * @param mixed $data
     * @param string $tableName
     * @return mixed
     */
    public function process($data, $tableName)
    {
        return null;
    }
}