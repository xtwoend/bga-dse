# MQTT Receiver for Hyperf

This project includes an MQTT receiver implementation for Hyperf framework that can subscribe to MQTT topics and handle incoming messages.

## Features

- Asynchronous MQTT message receiving using Hyperf processes
- Configurable topic handlers
- Automatic reconnection on connection failures
- Command-line tools for testing
- Logging and error handling

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```bash
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_CLIENT_ID=hyperf_client
MQTT_CLEAN_SESSION=true
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_KEEP_ALIVE=60
MQTT_CONNECTION_TIMEOUT=5
```

### MQTT Configuration

The MQTT configuration is located in `config/autoload/mqtt.php`. You can configure multiple connections and topics:

```php
'default' => [
    'host' => env('MQTT_HOST', 'localhost'),
    'port' => (int) env('MQTT_PORT', 1883),
    // ... other settings
    'topics' => [
        'sensor/temperature' => [
            'qos' => 0,
            'handler' => \App\Mqtt\Handler\TemperatureHandler::class,
        ],
        'device/status' => [
            'qos' => 1,
            'handler' => \App\Mqtt\Handler\DeviceStatusHandler::class,
        ],
    ],
],
```

## Creating Message Handlers

Create handlers that implement the `MqttHandlerInterface`:

```php
<?php

namespace App\Mqtt\Handler;

use App\Mqtt\Contract\MqttHandlerInterface;

class TemperatureHandler implements MqttHandlerInterface
{
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);
        
        // Process temperature data
        // Store to database, trigger events, etc.
    }
}
```

## Usage

### Automatic Start (Recommended)

The MQTT receiver will start automatically when you start the Hyperf server:

```bash
php bin/hyperf.php start
```

### Manual Commands

#### Start MQTT Receiver

```bash
php bin/hyperf.php mqtt:receiver
```

#### Publish Test Messages

```bash
# Publish to default topic
php bin/hyperf.php mqtt:publish

# Publish to specific topic with custom message
php bin/hyperf.php mqtt:publish "sensor/temperature" '{"value": 25.5, "unit": "C"}'

# Publish with specific QoS level
php bin/hyperf.php mqtt:publish "device/alert" "High temperature!" --qos=1
```

## Process Management

The MQTT receiver runs as a Hyperf process (`MqttReceiverProcess`) and will:

- Automatically restart on failures
- Reconnect to MQTT broker if connection is lost
- Log all activities and errors
- Handle multiple topics concurrently

## Testing with Mosquitto

Install Mosquitto for testing:

```bash
# macOS
brew install mosquitto

# Start Mosquitto broker
mosquitto -v

# In another terminal, subscribe to test messages
mosquitto_sub -h localhost -t "test/topic"

# Publish test message
mosquitto_pub -h localhost -t "test/topic" -m "Hello MQTT!"
```

## Troubleshooting

1. **Connection Issues**: Check MQTT broker is running and accessible
2. **Handler Errors**: Check logs in `runtime/logs/` directory
3. **Process Not Starting**: Verify configuration and dependencies

## File Structure

```
app/
├── Command/
│   ├── MqttReceiverCommand.php     # Manual receiver command
│   └── MqttPublishCommand.php      # Test publisher command
├── Mqtt/
│   ├── Contract/
│   │   └── MqttHandlerInterface.php # Handler interface
│   ├── Handler/
│   │   └── TestTopicHandler.php     # Example handler
│   └── Service/
│       └── MqttService.php          # Main MQTT service
└── Process/
    └── MqttReceiverProcess.php      # Background process

config/autoload/
├── mqtt.php                         # MQTT configuration
├── processes.php                    # Process registration
└── commands.php                     # Command registration
```