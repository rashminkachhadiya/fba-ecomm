<?php

namespace App\Services;

class BaseService 
{
    public string $hour, $date, $cronType, $cronName, $fetchReportLogId, $reportSource, $reportFreq, $reportType = '';
    public int $storeId, $cronLogId;
    
    /**
     * @param string $name
     */
    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }
}