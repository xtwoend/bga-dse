# Auto Column Addition Feature

This document describes the new auto column addition functionality in the `DynamicTableCreator` service.

## Overview

The `DynamicTableCreator` now supports automatically adding missing columns to existing tables when inserting new data. This is useful when your data structure evolves over time and you need to add new fields without manually altering the database schema.

## Usage

### Basic Usage with Auto Column Addition

```php
use App\Service\DynamicTableCreator;

// Sample data with new columns
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'new_field' => 'This field will be auto-added if not exists'
];

// Enable auto column addition
$options = [
    'auto_add_columns' => true,
    'timestamps' => true
];

// This will create table if not exists, or add missing columns if table exists
DynamicTableCreator::ensureTableWithColumns('users', $data, $options);
```

### Insert Data with Auto Column Creation

```php
// This method will automatically ensure all columns exist before inserting
DynamicTableCreator::insertDataWithAutoColumns('users', $data, $options);
```

### Create Table and Insert Data with Auto Columns

```php
// This will create table if not exists, add missing columns if needed, then insert data
$options['auto_add_columns'] = true;
DynamicTableCreator::createTableAndInsertData('users', $data, $options);
```

### Adding Individual Columns

```php
// Add a single column to existing table
DynamicTableCreator::addColumnToExistingTable('users', 'phone_number', '123-456-7890');

// Or specify the column type explicitly
$options = ['type' => 'string'];
DynamicTableCreator::addColumnToExistingTable('users', 'status', 'active', $options);
```

### Checking for Missing Columns

```php
// Check if a column exists
$hasColumn = DynamicTableCreator::hasColumn('users', 'phone_number');

// Get all existing column names
$columns = DynamicTableCreator::getExistingColumnNames('users');

// Add missing columns manually
$newData = ['new_field1' => 'value1', 'new_field2' => 'value2'];
DynamicTableCreator::addMissingColumns('users', $newData, $options);
```

## Options

The following options can be passed to control the behavior:

- `auto_add_columns` (bool): Enable automatic column addition. Default: `false`
- `timestamps` (bool): Include created_at and updated_at columns. Default: `true`
- `primary_key` (string): Type of primary key ('increments' or 'bigIncrements'). Default: `'bigIncrements'`
- `primary_key_name` (string): Name of primary key column. Default: `'id'`
- `type` (string): Explicit column type when adding individual columns

## Column Type Detection

The system automatically detects column types based on the data:

- **Integer**: `integer` or `bigInteger` (for large numbers)
- **Float/Double**: `float` or `double`
- **Boolean**: `boolean`
- **Array/Object**: `json` (automatically serialized)
- **Date strings**: `datetime`
- **Short strings** (≤255 chars): `string`
- **Medium text** (≤65535 chars): `text`
- **Long text** (>65535 chars): `longText`
- **Null values**: Default to `string`

## Safety Features

1. **Column Name Sanitization**: All column names are automatically sanitized to be database-safe (snake_case, no special characters)
2. **Nullable Columns**: All automatically added columns are nullable to prevent data insertion errors
3. **Existing Column Protection**: The system never modifies existing columns, only adds new ones
4. **Data Filtering**: When inserting data, only columns that exist in the table are included

## Examples

### Example 1: Evolving Data Structure

```php
// Initial data structure
$initialData = [
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

// Create table
DynamicTableCreator::createTableFromArrayData('users', $initialData);

// Later, evolved data structure with new fields
$evolvedData = [
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'phone' => '123-456-7890',
    'address' => '123 Main St',
    'age' => 25
];

// Insert with auto column addition
$options = ['auto_add_columns' => true];
DynamicTableCreator::insertDataWithAutoColumns('users', $evolvedData, $options);
```

### Example 2: Batch Data Processing

```php
$batchData = [
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com', 'phone' => '123-456-7890'],
    ['name' => 'User 3', 'email' => 'user3@example.com', 'age' => 30, 'city' => 'New York']
];

$options = ['auto_add_columns' => true];

foreach ($batchData as $data) {
    DynamicTableCreator::insertDataWithAutoColumns('users', $data, $options);
}
// Table will automatically get 'phone', 'age', and 'city' columns added as needed
```

## Notes

- Auto-added columns are always nullable to prevent insertion errors
- Column names are automatically sanitized and converted to snake_case
- The feature is disabled by default for backward compatibility
- Large text content (>65535 characters) automatically uses `longText` column type
- JSON data (arrays/objects) is automatically serialized