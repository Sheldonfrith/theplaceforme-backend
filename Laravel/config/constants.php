<?php

return [
    'categories' => [
        'allowed_names' => [
            'demographics',
            'geography',
            'violence',
            'religion',
            'government',
            'economics',
            'immigration',
            'culture',
            'health',
            'environment',
            'travel',
            'education',
            'technology',
            'uncategorized',
        ],
    ],
    'countries' => [
        'possible_id_types' => [
            'alpha_three_code','alpha_two_code','numeric_code','primary_name'
        ]
    ],
    'datasets'=>[
        'supported_data_types' => [
            'float','double','integer'
        ]
        ],
];
