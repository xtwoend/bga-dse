<?php

declare(strict_types=1);

namespace Test\Cases;

use PHPUnit\Framework\TestCase;
use App\Service\DynamicTableCreator;

class DynamicTableCreatorTest extends TestCase
{
    public function testSanitizeColumnName()
    {
        // Test basic sanitization
        $this->assertEquals('test_column', DynamicTableCreator::sanitizeColumnName('Test Column'));
        $this->assertEquals('test_column', DynamicTableCreator::sanitizeColumnName('test-column'));
        $this->assertEquals('test_column', DynamicTableCreator::sanitizeColumnName('test@column!'));
        $this->assertEquals('col_123', DynamicTableCreator::sanitizeColumnName('123'));
        $this->assertNotEmpty(DynamicTableCreator::sanitizeColumnName(''));
    }

    public function testDetermineColumnType()
    {
        // Test integer types
        $this->assertEquals('integer', DynamicTableCreator::determineColumnType(123));
        $this->assertEquals('bigInteger', DynamicTableCreator::determineColumnType(2147483648));
        
        // Test float types
        $this->assertEquals('float', DynamicTableCreator::determineColumnType(12.34));
        $this->assertEquals('float', DynamicTableCreator::determineColumnType('12.34'));
        
        // Test boolean
        $this->assertEquals('boolean', DynamicTableCreator::determineColumnType(true));
        $this->assertEquals('boolean', DynamicTableCreator::determineColumnType(false));
        
        // Test string types
        $this->assertEquals('string', DynamicTableCreator::determineColumnType('short text'));
        $this->assertEquals('text', DynamicTableCreator::determineColumnType(str_repeat('a', 300)));
        $this->assertEquals('longText', DynamicTableCreator::determineColumnType(str_repeat('a', 70000)));
        
        // Test JSON
        $this->assertEquals('json', DynamicTableCreator::determineColumnType(['key' => 'value']));
        $this->assertEquals('json', DynamicTableCreator::determineColumnType((object)['key' => 'value']));
        
        // Test date
        $this->assertEquals('datetime', DynamicTableCreator::determineColumnType('2023-12-25 10:30:00'));
        
        // Test null
        $this->assertEquals('string', DynamicTableCreator::determineColumnType(null));
    }

    public function testColumnNameSanitizationExamples()
    {
        $testCases = [
            'User Name' => 'user_name',
            'user-email' => 'user_email',
            'User@Email!' => 'user_email',
            'firstName' => 'firstname',
            'first_name' => 'first_name',
            '123abc' => 'col_123abc',
            'ABC123' => 'abc123',
            '!@#$%' => 'column_' // Will get unique ID appended
        ];

        foreach ($testCases as $input => $expected) {
            $result = DynamicTableCreator::sanitizeColumnName($input);
            if ($expected === 'column_') {
                // For the special case, just check it starts with 'column_'
                $this->assertStringStartsWith('column_', $result, "Failed for input: {$input}");
            } else {
                $this->assertEquals($expected, $result, "Failed for input: {$input}");
            }
        }
    }

    public function testColumnTypeDetectionExamples()
    {
        $testCases = [
            // Integers
            [42, 'integer'],
            [0, 'integer'],
            [-100, 'integer'],
            [2147483647, 'integer'], // Max int32
            [2147483648, 'bigInteger'], // Beyond int32
            
            // Floats
            [3.14, 'float'],
            [0.0, 'float'],
            [-2.5, 'float'],
            ['3.14159', 'float'],
            
            // Booleans
            [true, 'boolean'],
            [false, 'boolean'],
            
            // Strings
            ['hello', 'string'],
            ['', 'string'],
            [str_repeat('x', 100), 'string'], // Short string
            [str_repeat('x', 300), 'text'], // Medium text
            [str_repeat('x', 70000), 'longText'], // Long text
            
            // JSON
            [['name' => 'John'], 'json'],
            [(object)['age' => 30], 'json'],
            [[], 'json'],
            
            // Dates
            ['2023-12-25', 'datetime'],
            ['2023-12-25 15:30:00', 'datetime'],
            ['December 25, 2023', 'datetime'],
            
            // Special cases
            [null, 'string'],
            ['not-a-date', 'string'],
            ['123', 'integer'], // Numeric string treated as integer
        ];

        foreach ($testCases as [$input, $expected]) {
            $result = DynamicTableCreator::determineColumnType($input);
            $inputStr = is_array($input) || is_object($input) ? json_encode($input) : (string)$input;
            $this->assertEquals($expected, $result, "Failed for input: {$inputStr}");
        }
    }
}