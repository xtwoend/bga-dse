<?php

declare(strict_types=1);

namespace App\Mqtt\Service;

use Hyperf\Config\Annotation\Value;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class MqttService
{
    private LoggerInterface $logger;

    #[Inject]
    private ConfigInterface $config;

    #[Inject]
    private ContainerInterface $container;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('mqtt');
    }

    public function createClient(string $connection = 'default'): MqttClient
    {
        $config = $this->config->get("mqtt.{$connection}");
        
        if (!$config) {
            throw new \InvalidArgumentException("MQTT connection '{$connection}' not found in configuration");
        }

        $config['client_id'] = $config['client_id'] . uniqid();

        return new MqttClient($config['host'], $config['port'], $config['client_id']);
    }

    public function startReceiver(string $connection = 'default'): void
    {
        $config = $this->config->get("mqtt.{$connection}");
        $client = $this->createClient($connection);
        
        try {
            $connectionSettings = $this->getConnectionSettings($config);
            $client->connect($connectionSettings, true);
            
            $this->logger->info('Connected to MQTT broker', [
                'host' => $config['host'],
                'port' => $config['port'],
                'client_id' => $config['client_id'],
            ]);

            // Subscribe to configured topics
            foreach ($config['topics'] as $topic => $topicConfig) {
                $client->subscribe($topic, function (string $topic, string $message) use ($topicConfig) {
                    $this->handleMessage($topic, $message, $topicConfig);
                }, $topicConfig['qos'] ?? 0);

                $this->logger->info('Subscribed to topic', [
                    'topic' => $topic,
                    'qos' => $topicConfig['qos'] ?? 0,
                ]);
            }

            // Keep the connection alive and listen for messages
            $client->loop(true);
            
        } catch (Throwable $e) {
            $this->logger->error('MQTT receiver error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            if ($client->isConnected()) {
                $client->disconnect();
                $this->logger->info('Disconnected from MQTT broker');
            }
        }
    }

    private function getConnectionSettings(array $config): ConnectionSettings
    {
        $connectionSettings = (new ConnectionSettings())
            ->setConnectTimeout($config['connection_timeout'])
            ->setKeepAliveInterval($config['keep_alive']);
            // ->setCleanSession($config['clean_session']);
    
        if (!empty($config['username']) && !empty($config['password'])) {
            $connectionSettings = $connectionSettings->setUsername($config['username'])->setPassword($config['password']);
        }

        return $connectionSettings;
    }

    private function handleMessage(string $topic, string $message, array $topicConfig): void
    {
        try {
            if (isset($topicConfig['handler'])) {
                $handler = $this->container->get($topicConfig['handler']);
                $handler->handle($topic, $message);
            } else {
                $this->logger->warning('No handler configured for topic', ['topic' => $topic]);
            }
        } catch (Throwable $e) {
            $this->logger->error('Error handling MQTT message', [
                'topic' => $topic,
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}