<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use App\Service\DynamicTableCreator;
use Psr\Container\ContainerInterface;

#[Command]
class TestAutoColumnCommand extends HyperfCommand
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct('test:auto-column');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Test automatic column addition functionality');
    }

    public function handle()
    {
        $this->info('Testing Automatic Column Addition...');

        $tableName = 'auto_column_test';
        
        // Step 1: Create initial table with basic data
        $initialData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ];

        $this->info("\n📝 Step 1: Creating initial table with basic columns");
        $this->info("Initial data: " . json_encode($initialData));
        
        // Drop table if exists to start fresh
        if (DynamicTableCreator::tableExists($tableName)) {
            DynamicTableCreator::dropTable($tableName);
            $this->info("Dropped existing table for fresh start");
        }
        
        $created = DynamicTableCreator::createTableFromArrayData($tableName, $initialData);
        if ($created) {
            $this->info("✅ Table {$tableName} created successfully!");
        }

        // Show initial schema
        $this->showTableSchema($tableName, "Initial Schema");

        // Step 2: Test adding missing columns automatically
        $evolvedData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'age' => 25,
            'phone' => '123-456-7890',          // New column
            'address' => '123 Main St',          // New column
            'is_active' => true,                 // New column
            'salary' => 75000.50,               // New column
            'metadata' => ['role' => 'admin']    // New column (JSON)
        ];

        $this->info("\n📝 Step 2: Adding data with new columns (auto-add enabled)");
        $this->info("Evolved data: " . json_encode($evolvedData));

        $options = ['auto_add_columns' => true];
        DynamicTableCreator::insertDataWithAutoColumns($tableName, $evolvedData, $options);
        $this->info("✅ Data inserted with auto column addition!");

        // Show updated schema
        $this->showTableSchema($tableName, "Schema After Auto Column Addition");

        // Step 3: Test individual column addition
        $this->info("\n📝 Step 3: Adding individual column manually");
        $added = DynamicTableCreator::addColumnToExistingTable($tableName, 'department', 'Engineering');
        if ($added) {
            $this->info("✅ Column 'department' added successfully!");
        } else {
            $this->info("ℹ️ Column 'department' already exists or table not found");
        }

        // Step 4: Test ensureTableWithColumns
        $this->info("\n📝 Step 4: Testing ensureTableWithColumns method");
        $newTableData = [
            'product_id' => 'P001',
            'product_name' => 'Widget A',
            'price' => 29.99,
            'in_stock' => true,
            'category' => 'electronics',
            'specs' => ['weight' => '2kg', 'color' => 'blue']
        ];

        $newTableName = 'products_auto_test';
        $ensured = DynamicTableCreator::ensureTableWithColumns($newTableName, $newTableData);
        if ($ensured) {
            $this->info("✅ Table {$newTableName} created with ensureTableWithColumns!");
        }

        // Add more data to test column addition on existing table
        $moreProductData = [
            'product_id' => 'P002',
            'product_name' => 'Widget B',
            'price' => 39.99,
            'in_stock' => false,
            'category' => 'electronics',
            'specs' => ['weight' => '3kg', 'color' => 'red'],
            'warranty_months' => 24,      // New column
            'supplier' => 'Acme Corp',    // New column
            'rating' => 4.5               // New column
        ];

        $this->info("Adding more product data with new columns...");
        DynamicTableCreator::ensureTableWithColumns($newTableName, $moreProductData);
        DynamicTableCreator::insertDataIntoTable($newTableName, $moreProductData, $options);
        $this->info("✅ Product data inserted with new columns!");

        // Show final schemas
        $this->showTableSchema($tableName, "Final User Table Schema");
        $this->showTableSchema($newTableName, "Final Product Table Schema");

        // Step 5: Test column existence checks
        $this->info("\n📝 Step 5: Testing column existence checks");
        $hasPhone = DynamicTableCreator::hasColumn($tableName, 'phone');
        $this->info("Table '{$tableName}' has 'phone' column: " . ($hasPhone ? 'Yes' : 'No'));

        $hasNonExistent = DynamicTableCreator::hasColumn($tableName, 'non_existent_column');
        $this->info("Table '{$tableName}' has 'non_existent_column': " . ($hasNonExistent ? 'Yes' : 'No'));

        $columns = DynamicTableCreator::getExistingColumnNames($tableName);
        $this->info("All columns in '{$tableName}': " . implode(', ', $columns));

        $this->info("\n🎉 Auto column addition test completed successfully!");
        
        return 0;
    }

    private function showTableSchema(string $tableName, string $title): void
    {
        $this->info("\n📋 {$title}:");
        if (DynamicTableCreator::tableExists($tableName)) {
            $schema = DynamicTableCreator::getTableSchema($tableName);
            foreach ($schema as $column) {
                $nullable = $column['null'] === 'YES' ? 'NULL' : 'NOT NULL';
                $key = $column['key'] ? " [{$column['key']}]" : '';
                $this->info("  - {$column['field']} ({$column['type']}) {$nullable}{$key}");
            }
        } else {
            $this->error("Table {$tableName} does not exist!");
        }
    }
}