<?php
declare(strict_types=1);

return [
    'spaces' => [
        'oc' => [
            ['id' => '2x2', 'label' => 'Prostor 2 × 2 m', 'price' => 3200],
            ['id' => '4x2', 'label' => 'Prostor 4 × 2 m', 'price' => 4800],
            ['id' => '6x2', 'label' => 'Prostor 6 × 2 m', 'price' => 7500],
            ['id' => '8x2', 'label' => 'Prostor 8 × 2 m', 'price' => 8700],
        ],
        'hala' => [
            ['id' => '2x2', 'label' => 'Prostor 2 × 2 m', 'price' => 4800],
            ['id' => '4x2', 'label' => 'Prostor 4 × 2 m', 'price' => 6000],
            ['id' => '6x2', 'label' => 'Prostor 6 × 2 m', 'price' => 8400],
            ['id' => '8x2', 'label' => 'Prostor 8 × 2 m', 'price' => 10400],
        ],
    ],
    'electricity' => [
        'oc' => [
            ['id' => 'none',  'label' => 'Elektriku nepotřebuji',                   'price' => 0],
            ['id' => '2.5kw', 'label' => 'Přípojka do 2,5 kW · 10A 230V',          'price' => 0],
            ['id' => '9kw',   'label' => 'Přípojka 9 kW · 3×13A 400V/16A',         'price' => 0],
            ['id' => '18kw',  'label' => 'Přípojka 18 kW · 3×26A 400V/32A',        'price' => 0],
        ],
        'hala' => [
            ['id' => 'none',  'label' => 'Elektriku nepotřebuji',                   'price' => 0],
            ['id' => '2.5kw', 'label' => 'Přípojka do 2,5 kW · 10A 230V',          'price' => 0],
            ['id' => '9kw',   'label' => 'Přípojka 9 kW · 3×13A 400V/16A',         'price' => 500],
            ['id' => '18kw',  'label' => 'Přípojka 18 kW · 3×26A 400V/32A',        'price' => 1000],
        ],
    ],
];