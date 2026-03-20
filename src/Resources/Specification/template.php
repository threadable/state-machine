<?php

return [
    'version' => __VERSION_NUMBER__,
    'stateable_class' => '__STATEABLE_CLASS__',
    'states' => [
        [
            'name' => 'Initial',
            'initial' => true,
        ],
        [
            'name' => 'Queued',
            'final' => true,
        ],
    ],
    'transitions' => [
        [
            'name' => 'Queue',
            'from' => 'Initial',
            'to' => 'Queued',
            'trigger' => 'AUTO',
            'condition_type' => 'ACTION',
            'conditions' => [
                [
                    'name' => 'Ready to queue',
                    'ref' => 'ReadyToQueue',
                ],
            ],
            'actions' => [
                [
                    'name' => 'Queue order',
                    'ref' => 'QueueOrder',
                ],
            ],
            'validation' => [
                'rules' => [],
                'messages' => [],
            ],
        ],
    ],
];
