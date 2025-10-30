<?php

declare(strict_types=1);

namespace App\Process;

use Hyperf\Redis\Redis;
use App\Model\LogDataBuffer;
use Hyperf\Process\AbstractProcess;
use App\Service\DynamicTableCreator;
use Hyperf\Process\Annotation\Process;

#[Process(name: 'DataLoggerSaveProcess')]
class DataLoggerSaveProcess extends AbstractProcess
{
    public function handle(): void
    {
        $groups = [
            'dse_bbnm_genset1', 'dse_bbnm_genset2', 'dse_bbnm_pln', 'dse_bbnm_turbine1', 'dse_bbnm_turbine2',
            'dse_btjm_genset1', 'dse_btjm_genset2', 'dse_btjm_pln', 'dse_btjm_turbine1', 'dse_btjm_turbine2',
            'dse_pnbm_genset1', 'dse_pnbm_genset2', 'dse_pnbm_pln', 'dse_pnbm_turbine1', 'dse_pnbm_turbine2',
        ];

        while (true) {
            foreach($groups as $tableName) {
                $redis = $this->container->get(Redis::class);
                
                // Get all keys matching the pattern for this group
                $pattern = "log_data_buffer:{$tableName}:*";
                $keys = $redis->keys($pattern);
                
                $data = [];
                if (!empty($keys)) {
                    // Get all values for these keys
                    $values = $redis->mget($keys);
                    
                    // Process each key-value pair
                    foreach ($keys as $index => $key) {
                        if (!empty($values[$index])) {
                            $jsonData = json_decode($values[$index], true);
                            if ($jsonData && isset($jsonData['tag'], $jsonData['value'])) {
                                $data[$jsonData['tag']] = $jsonData;
                            }
                        }
                    }
                }
                
                echo "Processing {$tableName}: " . count($data) . " records found\n";
                if (!empty($data)) {
                    var_dump(array_keys($data)); // Show which tags we found
                }
                
                $this->handleLogData($tableName, $data);
                sleep(2);
            }
        }
    }

    public function handleLogData($tableName, $data): void
    {
        if(empty($data)) {
            echo "No data found for {$tableName}\n";
            return;
        }
        
        $date = date('Ym');
        $finalTableName = "{$tableName}_{$date}";
        
        echo "Creating table and inserting data for {$finalTableName} with " . count($data) . " records\n";
        
        // Convert the data structure for DynamicTableCreator
        $processedData = [];
        foreach ($data as $tag => $record) {
            $value = $record['value'];
            $processedData[$tag] = $value;
        }
        
        // echo "Sample processed data: ";
        // var_dump(array_slice($processedData, 0, 3, true)); // Show first 3 items
        
        // Create table and insert data using the DynamicTableCreator service
        DynamicTableCreator::createTableAndInsertData($finalTableName, $processedData);

        $this->saveDataBuffer($tableName, $processedData);
    }

    public function saveDataBuffer($tableName, $processedData) {
        foreach($processedData as $key => $val) {
            LogDataBuffer::updateOrCreate([
                'group' => $tableName,
                'tag' => $key
            ], [
                'value' => (float) $val
            ]);
        }
    }
}
