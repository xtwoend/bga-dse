<?php

declare(strict_types=1);

namespace App\Process;

use App\Mqtt\Service\MqttService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

#[Process(name: "mqtt-receiver")]
class MqttReceiverProcess extends AbstractProcess
{
    public string $name = 'mqtt-receiver';

    public int $nums = 1;

    public bool $redirectStdinStdout = false;

    public int $pipeType = 2;

    public bool $enableCoroutine = true;

    #[Inject]
    private MqttService $mqttService;

    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container, LoggerFactory $loggerFactory)
    {
        parent::__construct($container);
        $this->logger = $loggerFactory->get('mqtt-process');
    }

    public function handle(): void
    {
        $this->logger->info('Starting MQTT receiver process');

        while (true) {
            try {
                $this->mqttService->startReceiver();
            } catch (Throwable $e) {
                $this->logger->error('MQTT receiver process error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Wait before retrying
                sleep(15);
                $this->logger->info('Restarting MQTT receiver process');
            }
        }
    }

    public function isEnable($server): bool
    {
        // You can add logic here to enable/disable the process
        // For example, check configuration or environment
        return true;
    }
}