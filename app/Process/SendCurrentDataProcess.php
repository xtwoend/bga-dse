<?php

declare(strict_types=1);

namespace App\Process;

use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use App\Mqtt\Service\MqttService;
use Hyperf\Process\AbstractProcess;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\Annotation\Process;
use PhpMqtt\Client\ConnectionSettings;

#[Process(name: 'SendCurrentDataProcess')]
class SendCurrentDataProcess extends AbstractProcess
{
    #[Inject]
    private MqttService $mqttService;

    #[Inject]
    private ConfigInterface $config;

    public function handle(): void
    {
        $groups = [
            'dse_bbnm_genset1', 'dse_bbnm_genset2', 'dse_bbnm_pln', 'dse_bbnm_turbine1', 'dse_bbnm_turbine2',
            'dse_btjm_genset1', 'dse_btjm_genset2', 'dse_btjm_pln', 'dse_btjm_turbine1', 'dse_btjm_turbine2',
            'dse_pnbm_genset1', 'dse_pnbm_genset2', 'dse_pnbm_pln', 'dse_pnbm_turbine1', 'dse_pnbm_turbine2',
        ];

        while (true) {
            // Implement the logic to send current data here
            foreach($groups as $topic) {
                $data = Db::table('log_data_buffer')->where('group', $topic)->get()->pluck('value', 'tag')->toArray();
                $this->send($topic, $data);
            }
            
            sleep(30); // Sleep for 15 seconds before the next send
        }
    }

    private function send(string $topic, array $data): void
    {
        try {
            $client = $this->mqttService->createClient('default');
            $config = $this->config->get("mqtt.default");
            $message = json_encode($data);

            $connectionSettings = (new ConnectionSettings())
                ->setConnectTimeout($config['connection_timeout'])
                ->setKeepAliveInterval($config['keep_alive']);

            if (!empty($config['username']) && !empty($config['password'])) {
                $connectionSettings = $connectionSettings->setUsername($config['username'])->setPassword($config['password']);
            }

            $client->connect($connectionSettings);
            $client->publish('data/bga/dse/'.$topic, $message, 0);
            $client->disconnect();

        } catch (\Throwable $e) {
            // Handle exceptions (e.g., log the error)
        }
    }
}
