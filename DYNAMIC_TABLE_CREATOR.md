# Dynamic Table Creator

A utility service for creating database table schemas dynamically from array data in Hyperf framework.

## Features

- ✅ Automatically detect column types from array values
- ✅ Sanitize column names for database compatibility  
- ✅ Support for various data types (string, integer, float, boolean, json, text, date)
- ✅ Configurable options for timestamps and primary keys
- ✅ Insert data after table creation
- ✅ Table existence checking
- ✅ Schema inspection utilities

## Usage

### Basic Usage

```php
use App\Service\DynamicTableCreator;

// Sample data
$data = [
    'temperature' => 25.5,
    'humidity' => 60.2,
    'device_id' => 'SENSOR_001',
    'location' => 'Room A',
    'active' => true,
    'metadata' => ['calibrated' => true, 'version' => '2.1']
];

// Create table and insert data
DynamicTableCreator::createTableAndInsertData('sensor_readings', $data);
```

### Advanced Usage with Options

```php
$options = [
    'timestamps' => true,           // Add created_at and updated_at columns
    'primary_key' => 'bigIncrements', // Primary key type
    'primary_key_name' => 'id'     // Primary key column name
];

DynamicTableCreator::createTableAndInsertData('my_table', $data, $options);
```

### Separate Operations

```php
// Only create table schema
DynamicTableCreator::createTableFromArrayData('my_table', $data);

// Only insert data (table must exist)
DynamicTableCreator::insertDataIntoTable('my_table', $data);
```

### Utility Methods

```php
// Check if table exists
if (DynamicTableCreator::tableExists('my_table')) {
    // Table exists
}

// Get table schema information
$schema = DynamicTableCreator::getTableSchema('my_table');

// Drop table
DynamicTableCreator::dropTable('my_table');
```

## Data Type Detection

The service automatically detects appropriate column types:

| PHP Type | Database Type |
|----------|---------------|
| `bool` | `boolean` |
| `int` (small) | `integer` |
| `int` (large) | `bigInteger` |
| `float` | `float` |
| `array/object` | `json` |
| `string` (< 255 chars) | `string` |
| `string` (> 255 chars) | `text` |
| `string` (> 65535 chars) | `longText` |
| Date strings | `datetime` |

## Column Name Sanitization

Column names are automatically sanitized:
- Converted to lowercase
- Special characters replaced with underscores
- Multiple underscores collapsed to single
- Leading/trailing underscores removed
- Numeric prefixes get 'col_' prefix

Examples:
- `"Temperature (°C)"` → `"temperature_c"`
- `"User ID"` → `"user_id"`
- `"123abc"` → `"col_123abc"`

## Error Handling

The service handles various edge cases:
- Null values default to string type
- Empty column names get unique identifiers
- Existing tables are skipped (not recreated)
- Invalid data types fallback to string

## Testing

Run the test command to see examples:

```bash
php bin/hyperf.php test:dynamic-table
```

## Integration Example

```php
// In your process or service
class DataProcessor
{
    public function processData(string $source, array $data): void
    {
        $tableName = "data_{$source}_" . date('Y_m');
        
        // Create table and insert data
        DynamicTableCreator::createTableAndInsertData($tableName, $data);
        
        // Log the operation
        echo "Data saved to table: {$tableName}\n";
    }
}
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `timestamps` | bool | `true` | Include created_at/updated_at |
| `primary_key` | string | `'bigIncrements'` | Primary key type |
| `primary_key_name` | string | `'id'` | Primary key column name |