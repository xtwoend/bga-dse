<?php

declare(strict_types=1);

namespace App\Command;

use App\Mqtt\Service\MqttService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use PhpMqtt\Client\ConnectionSettings;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class MqttPublishCommand extends HyperfCommand
{
    #[Inject]
    private MqttService $mqttService;

    #[Inject]
    private ConfigInterface $config;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('mqtt:publish');
        $this->setDescription('Publish a test message to MQTT broker');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('topic', InputArgument::OPTIONAL, 'Topic to publish to', 'test/topic');
        $this->addArgument('message', InputArgument::OPTIONAL, 'Message to publish', 'Hello from Hyperf!');
        $this->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'MQTT connection to use', 'default');
        $this->addOption('qos', null, InputOption::VALUE_OPTIONAL, 'Quality of Service level (0, 1, or 2)', 0);
    }

    public function handle(): void
    {
        $connection = $this->input->getOption('connection');
        $topic = $this->input->getArgument('topic');
        $message = $this->input->getArgument('message');
        $qos = (int) $this->input->getOption('qos');
        
        $this->line("Publishing message to topic: {$topic}");
        $this->line("Message: {$message}");
        $this->line("QoS: {$qos}");
        
        try {
            $client = $this->mqttService->createClient($connection);
            
            // Get connection settings
            $config = $this->config->get("mqtt.{$connection}");
            $connectionSettings = (new ConnectionSettings())
                ->setConnectTimeout($config['connection_timeout'])
                ->setKeepAliveInterval($config['keep_alive']);
                // ->setCleanSession($config['clean_session']);

            if (!empty($config['username'])) {
                $connectionSettings->setUsername($config['username']);
            }

            if (!empty($config['password'])) {
                $connectionSettings->setPassword($config['password']);
            }

            $client->connect($connectionSettings);
            $client->publish($topic, $message, $qos);
            $client->disconnect();
            
            $this->info("Message published successfully!");
            
        } catch (\Throwable $e) {
            $this->error("Failed to publish message: " . $e->getMessage());
        }
    }
}