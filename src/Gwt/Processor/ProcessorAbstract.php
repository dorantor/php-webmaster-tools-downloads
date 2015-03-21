<?php
/**
 *  PHP class for downloading CSV files from Google Webmaster Tools.
 *
 *  This class does NOT require the Zend gdata package be installed
 *  in order to run.
 *
 *  Copyright 2015 Anton Kolenkov. All Rights Reserved.
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
 * @link   https://github.com/dorantor/php-webmaster-tools-downloads/
 */

/**
 * Class with common logic for processors
 */
abstract class Gwt_Processor_ProcessorAbstract
    implements Gwt_Processor_ProcessorInterface
{
    /**
     * Set GWT client
     *
     * @param Gwt_Client $client
     * @return void
     */
    public function setClient(Gwt_Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Get GWT client
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Create processor object and set params
     *
     * @throws Exception
     * @param array $params
     * @return static
     */
    public static function factory(array $params = array())
    {
        $processor = new static();
        foreach ($params as $param => $value) {
            $method = 'set' . $param;
            if (!method_exists($processor, $method)) {
                throw new Exception('Not supported param: ' . var_export($param, true));
            }
            $processor->$method($value);
        }

        return $processor;
    }
}