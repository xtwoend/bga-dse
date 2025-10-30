<?php

declare(strict_types=1);

namespace App\Mqtt\Handler\BBNM;

use Hyperf\Stringable\Str;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;
use Hyperf\Logger\LoggerFactory;
use App\Mqtt\Contract\MqttHandlerInterface;

class Turbine1TopicHandler implements MqttHandlerInterface
{
    private LoggerInterface $logger;
    private Redis $redis;

    public function __construct(LoggerFactory $loggerFactory, Redis $redis)
    {
        $this->logger = $loggerFactory->get('mqtt');
        $this->redis = $redis;
    }

    public function handle(string $topic, string $message): void
    {
        // formating & logging the received message
        // var_dump("Received message on topic '{$topic}': {$message}");
        
        [$tag, $value] = $this->topicToTag($topic, $message);
        $table_name = 'dse_bbnm_turbine1';

        // Store data in Redis with key format: log_data_buffer:{group}:{tag}
        $redis_key = "log_data_buffer:{$table_name}:{$tag}";
        $data = [
            'group' => $table_name,
            'tag' => $tag,
            'value' => $value,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Store as JSON in Redis with TTL (optional - set to 24 hours)
        $this->redis->setex($redis_key, 86400, json_encode($data));
    }

    // Extract value from JSON message
    private function extractValue(string $message): ?float
    {
        try {
            $data = json_decode($message, true);
           
            // Navigate through the JSON structure to find the value
            foreach ($data as $groups) {
                foreach ($groups as $key => $address) {
                    foreach ($address as $addr => $val) {
                        return (float) $val ?? 0;
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse JSON message: " . $e->getMessage());
            return null;
        }
    }

    private function topicToTag($topic, $message): array
    {
        $tag = Str::snake(str_replace('-', '_', strtolower(str_replace('data/bga/bbnm/dse/turbine1/', '', $topic))));
        $value = $this->extractValue($message);
    
        return [$tag, $value];
    }
}