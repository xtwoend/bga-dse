<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use App\Service\DynamicTableCreator;
use Psr\Container\ContainerInterface;

#[Command]
class TestDynamicTableCommand extends HyperfCommand
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct('test:dynamic-table');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Test dynamic table creation from array data');
    }

    public function handle()
    {
        $this->info('Testing Dynamic Table Creator...');

        // Example 1: Simple sensor data
        $sensorData = [
            'temperature' => 25.5,
            'humidity' => 60.2,
            'pressure' => 1013.25,
            'device_id' => 'SENSOR_001',
            'location' => 'Room A',
            'active' => true,
            'readings_count' => 1500,
            'metadata' => ['calibrated' => true, 'version' => '2.1']
        ];

        $tableName1 = 'sensor_readings_test';
        $this->info("Creating table: {$tableName1}");
        
        $created = DynamicTableCreator::createTableAndInsertData($tableName1, $sensorData);
        if ($created) {
            $this->info("✅ Table {$tableName1} created successfully!");
        } else {
            $this->info("ℹ️ Table {$tableName1} already exists, data inserted.");
        }

        // Example 2: Complex nested data
        $complexData = [
            'user_id' => 12345,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'profile' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
                'preferences' => ['theme' => 'dark', 'notifications' => true]
            ],
            'last_login' => '2025-10-28 10:30:00',
            'is_active' => true,
            'login_count' => 150,
            'account_balance' => 1250.75
        ];

        $tableName2 = 'user_activity_test';
        $this->info("Creating table: {$tableName2}");
        
        $created2 = DynamicTableCreator::createTableAndInsertData($tableName2, $complexData);
        if ($created2) {
            $this->info("✅ Table {$tableName2} created successfully!");
        } else {
            $this->info("ℹ️ Table {$tableName2} already exists, data inserted.");
        }

        // Example 3: Using custom options
        $customData = [
            'machine_id' => 'M001',
            'status' => 'operational',
            'rpm' => 1500,
            'temperature' => 85.5,
            'vibration' => 0.05
        ];

        $tableName3 = 'machine_status_test';
        $options = [
            'timestamps' => true,
            'primary_key' => 'bigIncrements',
            'primary_key_name' => 'id'
        ];

        $this->info("Creating table with custom options: {$tableName3}");
        $created3 = DynamicTableCreator::createTableAndInsertData($tableName3, $customData, $options);
        if ($created3) {
            $this->info("✅ Table {$tableName3} created successfully with custom options!");
        } else {
            $this->info("ℹ️ Table {$tableName3} already exists, data inserted.");
        }

        // Show table schemas
        $this->info("\n📋 Table Schemas:");
        
        foreach ([$tableName1, $tableName2, $tableName3] as $table) {
            if (DynamicTableCreator::tableExists($table)) {
                $this->info("\nTable: {$table}");
                $schema = DynamicTableCreator::getTableSchema($table);
                foreach ($schema as $column) {
                    $this->info("  - {$column['field']} ({$column['type']}) {$column['null']} {$column['key']}");
                }
            }
        }

        $this->info("\n🎉 Dynamic table creation test completed!");
        
        return 0;
    }
}