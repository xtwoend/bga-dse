<?php

declare(strict_types=1);

namespace App\Process;

use Hyperf\DbConnection\Db;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use App\Service\DynamicTableCreator;

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
                $data = Db::table('log_data_buffer')->where('group', $tableName)->get()->pluck('value', 'tag')->toArray();
                $this->handleLogData($tableName, $data);
                sleep(1);
            }
        }
    }

    public function handleLogData($tableName, $data): void
    {
        if(empty($data)) return;
        $date = date('Ym');

        $tableName = "{$tableName}_{$date}";
        
        // Create table and insert data using the DynamicTableCreator service
        DynamicTableCreator::createTableAndInsertData($tableName, $data);
    }
}
