<?php

interface Gwt_Processor_ProcessorInterface
{
    /**
     * Set GWT client
     *
     * @param Gwt_Client $client
     * @return void
     */
    public function setClient(Gwt_Client $client);

    /**
     * Get GWT client
     *
     * @return mixed
     */
    public function getClient();

    /**
     * Process data
     *
     * @param mixed $data
     * @param string $tableName
     * @return mixed
     */
    public function process($data, $tableName);
}