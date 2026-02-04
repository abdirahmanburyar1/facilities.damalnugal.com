<?php

namespace App\Console\Commands;

use App\Events\OrderEvent;
use App\Models\Order;
use Illuminate\Console\Command;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Consumer\ConsumerConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KafkaConsume extends Command
{
    protected $signature = 'kafka:consume';
    protected $description = 'Start consuming Kafka messages';

    public function handle()
    {
        $this->info('Starting Kafka consumer...');

        try {
            $config = new ConsumerConfig();
            $config->setBrokers(['warehouse.psivista.com:9092']);
            $config->setTopic('facilities.orders.updated');
            $config->setGroupId('warehouse_group');
            $config->setClientId('warehouse_client_' . Str::random(8));
            $config->setGroupInstanceId('warehouse_instance_' . Str::random(8));
            $config->setAutoCommit(false);

            $consumer = new Consumer($config);
            $this->info('Consumer configured and ready');
            Log::info('Kafka consumer started', [
                'topic' => 'facilities.orders.updated',
                'group' => 'warehouse_group'
            ]);

            while (true) {
                try {
                    $message = $consumer->consume();
                    if ($message) {
                        $rawValue = $message->getValue();
                        $this->info('Raw message received: ' . $rawValue);
                        Log::info('Raw Kafka message received', ['message' => $rawValue]);

                        $data = json_decode($rawValue, true);
                        event(new OrderEvent('Refreshed'));
                        $this->info('Decoded message: ' . json_encode($data));
                        Log::info('Decoded Kafka message', ['data' => $data]);

                        $consumer->ack($message);
                        $this->info('Message processed and acknowledged');
                        Log::info('Kafka message processed and acknowledged');
                    }
                    usleep(100000); // 100ms sleep
                } catch (\Exception $e) {
                    $this->error('Error consuming message: ' . $e->getMessage());
                    Log::error('Error consuming Kafka message: ' . $e->getMessage());
                    sleep(1);
                }
            }
        } catch (\Exception $e) {
            $this->error('Failed to start consumer: ' . $e->getMessage());
            Log::error('Failed to start Kafka consumer: ' . $e->getMessage());
        }
    }
}
