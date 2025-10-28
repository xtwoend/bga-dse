<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;
use Carbon\Carbon;

class DynamicTableCreator
{
    /**
     * Create table schema from array data and table name
     */
    public static function createTableFromArrayData(string $tableName, array $data, array $options = []): bool
    {
        // Check if table already exists
        if (Schema::hasTable($tableName)) {
            return false;
        }

        $includeTimestamps = $options['timestamps'] ?? true;
        $primaryKeyType = $options['primary_key'] ?? 'bigIncrements';
        $primaryKeyName = $options['primary_key_name'] ?? 'id';

        Schema::create($tableName, function (Blueprint $table) use ($data, $includeTimestamps, $primaryKeyType, $primaryKeyName) {
            // Create primary key
            switch ($primaryKeyType) {
                case 'increments':
                    $table->increments($primaryKeyName);
                    break;
                case 'bigIncrements':
                default:
                    $table->bigIncrements($primaryKeyName);
                    break;
            }
            
            // Analyze data structure and create columns
            foreach ($data as $key => $value) {
                $columnName = self::sanitizeColumnName($key);
                $columnType = self::determineColumnType($value);
                
                self::addColumnToTable($table, $columnName, $columnType, $value);
            }
            
            // Add timestamps if requested
            if ($includeTimestamps) {
                $table->timestamps();
            }
        });

        return true;
    }

    /**
     * Add column to table based on type
     */
    private static function addColumnToTable(Blueprint $table, string $columnName, string $columnType, $value): void
    {
        switch ($columnType) {
            case 'bigInteger':
                $table->bigInteger($columnName)->nullable();
                break;
            case 'integer':
                $table->integer($columnName)->nullable();
                break;
            case 'float':
                $table->float($columnName, 8, 2)->nullable();
                break;
            case 'double':
                $table->double($columnName, 8, 2)->nullable();
                break;
            case 'decimal':
                $table->decimal($columnName, 8, 2)->nullable();
                break;
            case 'boolean':
                $table->boolean($columnName)->nullable();
                break;
            case 'json':
                $table->json($columnName)->nullable();
                break;
            case 'text':
                $table->text($columnName)->nullable();
                break;
            case 'longText':
                $table->longText($columnName)->nullable();
                break;
            case 'date':
                $table->date($columnName)->nullable();
                break;
            case 'datetime':
                $table->dateTime($columnName)->nullable();
                break;
            case 'timestamp':
                $table->timestamp($columnName)->nullable();
                break;
            case 'string':
            default:
                $length = is_string($value) ? min(max(strlen($value), 50), 255) : 255;
                $table->string($columnName, $length)->nullable();
                break;
        }
    }

    /**
     * Sanitize column name to be database-safe
     */
    public static function sanitizeColumnName(string $columnName): string
    {
        // Convert to snake_case and remove special characters
        $columnName = strtolower(trim($columnName));
        $columnName = preg_replace('/[^a-z0-9_]/', '_', $columnName);
        $columnName = preg_replace('/_+/', '_', $columnName);
        $columnName = trim($columnName, '_');
        
        // Ensure it doesn't start with a number
        if (is_numeric(substr($columnName, 0, 1))) {
            $columnName = 'col_' . $columnName;
        }
        
        // Ensure it's not empty
        if (empty($columnName)) {
            $columnName = 'column_' . uniqid();
        }
        
        return $columnName;
    }

    /**
     * Determine column type based on value
     */
    public static function determineColumnType($value): string
    {
        if (is_null($value)) {
            return 'string'; // Default to string for null values
        }
        
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_int($value)) {
            // Check if it's a big integer
            if ($value > 2147483647 || $value < -2147483648) {
                return 'bigInteger';
            }
            return 'integer';
        }
        
        if (is_float($value)) {
            return 'float';
        }
        
        if (is_numeric($value)) {
            $numValue = (float) $value;
            if (floor($numValue) == $numValue) {
                return 'integer';
            }
            return 'float';
        }
        
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        
        if (is_string($value)) {
            // Check for date formats
            if (self::isDateString($value)) {
                return 'datetime';
            }
            
            // Check text length
            $length = strlen($value);
            if ($length > 65535) {
                return 'longText';
            } elseif ($length > 255) {
                return 'text';
            }
            return 'string';
        }
        
        return 'string'; // Default fallback
    }

    /**
     * Check if string is a date
     */
    private static function isDateString(string $value): bool
    {
        try {
            $date = Carbon::parse($value);
            return $date instanceof Carbon;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Insert data into the created table
     */
    public static function insertDataIntoTable(string $tableName, array $data, array $options = []): void
    {
        $includeTimestamps = $options['timestamps'] ?? true;
        
        // Prepare data for insertion
        $insertData = [];
        foreach ($data as $key => $value) {
            $columnName = self::sanitizeColumnName($key);
            
            // Handle different data types for insertion
            if (is_array($value) || is_object($value)) {
                $insertData[$columnName] = json_encode($value);
            } elseif (is_bool($value)) {
                $insertData[$columnName] = $value ? 1 : 0;
            } else {
                $insertData[$columnName] = $value;
            }
        }
        
        // Add timestamps if requested
        if ($includeTimestamps) {
            $insertData['created_at'] = Carbon::now();
            $insertData['updated_at'] = Carbon::now();
        }
        
        // Insert data
        Db::table($tableName)->insert($insertData);
    }

    /**
     * Create table and insert data in one operation
     */
    public static function createTableAndInsertData(string $tableName, array $data, array $options = []): bool
    {
        $tableCreated = self::createTableFromArrayData($tableName, $data, $options);
        self::insertDataIntoTable($tableName, $data, $options);
        
        return $tableCreated;
    }

    /**
     * Get table schema information
     */
    public static function getTableSchema(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $columns = Db::select("DESCRIBE {$tableName}");
        $schema = [];
        
        foreach ($columns as $column) {
            $schema[] = [
                'field' => $column->Field,
                'type' => $column->Type,
                'null' => $column->Null,
                'key' => $column->Key,
                'default' => $column->Default,
                'extra' => $column->Extra,
            ];
        }
        
        return $schema;
    }

    /**
     * Check if table exists
     */
    public static function tableExists(string $tableName): bool
    {
        return Schema::hasTable($tableName);
    }

    /**
     * Drop table if exists
     */
    public static function dropTable(string $tableName): bool
    {
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
            return true;
        }
        
        return false;
    }
}