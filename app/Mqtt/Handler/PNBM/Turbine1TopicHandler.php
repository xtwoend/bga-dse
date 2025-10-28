<?php

declare(strict_types=1);

namespace App\Mqtt\Handler\PNBM;

use Hyperf\Stringable\Str;
use Hyperf\DbConnection\Db;
use App\Model\LogDataBuffer;
use Psr\Log\LoggerInterface;
use Hyperf\Logger\LoggerFactory;
use App\Mqtt\Contract\MqttHandlerInterface;

class Turbine1TopicHandler implements MqttHandlerInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('mqtt');
    }

    public function handle(string $topic, string $message): void
    {
        // formating & logging the received message
        // var_dump("Received message on topic '{$topic}': {$message}");
        
        [$tag, $value] = $this->topicToTag($topic, $message);
        $table_name = 'dse_pnbm_turbine1';

        LogDataBuffer::updateOrCreate(
            [
                'group' => $table_name,
                'tag' => $tag,
            ],
            [
                'value' => $value,
            ]
        );
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
        $tag = Str::snake(str_replace('-', '_', strtolower(str_replace('data/bga/pnbm/dse/turbine1/', '', $topic))));
        $value = $this->extractValue($message);
    
        return [$tag, $value];
    }
}