<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use longlang\phpkafka\Producer\Producer;
use longlang\phpkafka\Producer\ProducerConfig;

class KafkaServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register producer service
        $this->app->singleton('kafka.producer', function ($app) {
            $config = new ProducerConfig();
            $config->setBrokers(['warehouse.psivista.com:9092']);
            $config->setUpdateBrokers(false);
            $config->setAcks(-1);
            $config->setConnectTimeout(3);
            
            return new Producer($config);
        });

        // Register Kafka service facade
        $this->app->singleton('kafka', function ($app) {
            return new class($app) {
                protected $app;
                protected $producer;

                public function __construct($app)
                {
                    $this->app = $app;
                    try {
                        $this->producer = $app->make('kafka.producer');
                    } catch (\Exception $e) {
                        \Log::error('Kafka Producer Error: ' . $e->getMessage());
                    }
                }

                public function publishOrderUpdated($message)
                {
                    try {
                        if (!$this->producer) {
                            return;
                        }
                        $this->producer->send('facilities.orders.updated', $message);
                    } catch (\Exception $e) {
                        \Log::error('Kafka Send Error: ' . $e->getMessage());
                    }
                }
            };
        });
    }

    public function boot()
    {
        //
    }
}
