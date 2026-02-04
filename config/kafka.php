<?php

return [
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),
    
    'producer' => [
        'topics' => [
            'order_placed' => env('KAFKA_TOPIC_ORDER_PLACED', 'facilities.orders.placed'),
            'order_updated' => env('KAFKA_TOPIC_ORDER_UPDATED', 'facilities.orders.updated'),
            'order_cancelled' => env('KAFKA_TOPIC_ORDER_CANCELLED', 'facilities.orders.cancelled'),
        ],
    ],
    
    'consumer' => [
        'group_id' => env('KAFKA_CONSUMER_GROUP_ID', 'facilities_group'),
        'client_id' => env('KAFKA_CONSUMER_CLIENT_ID', 'facilities_client'),
        'topics' => [
            'order_approved' => env('KAFKA_TOPIC_ORDER_APPROVED', 'warehouse.orders.approved'),
            'order_rejected' => env('KAFKA_TOPIC_ORDER_REJECTED', 'warehouse.orders.rejected'),
            'order_processing' => env('KAFKA_TOPIC_ORDER_PROCESSING', 'warehouse.orders.processing'),
            'order_dispatched' => env('KAFKA_TOPIC_ORDER_DISPATCHED', 'warehouse.orders.dispatched'),
            'order_delivered' => env('KAFKA_TOPIC_ORDER_DELIVERED', 'warehouse.orders.delivered'),
        ],
    ],
];
