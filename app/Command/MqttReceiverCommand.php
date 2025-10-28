<?php

declare(strict_types=1);

namespace App\Command;

use App\Mqtt\Service\MqttService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class MqttReceiverCommand extends HyperfCommand
{
    #[Inject]
    private MqttService $mqttService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('mqtt:receiver');
        $this->setDescription('Start MQTT receiver');
    }

    public function configure(): void
    {
        parent::configure();
        $this->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'MQTT connection to use', 'default');
    }

    public function handle(): void
    {
        $connection = $this->input->getOption('connection');
        
        $this->line("Starting MQTT receiver for connection: {$connection}");
        
        try {
            $this->mqttService->startReceiver($connection);
        } catch (\Throwable $e) {
            $this->error("Failed to start MQTT receiver: " . $e->getMessage());
            return;
        }
    }
}