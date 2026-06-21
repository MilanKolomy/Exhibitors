<?php
declare(strict_types=1);

return [
    'ico' => [
        'type'         => 'text',
        'required'     => true,
        'maxlength'    => 8,
        'ares_trigger' => true,      // spustí AJAX načtení z ARES
        'autocomplete' => 'off',
    ],
    'company' => [
        'type'      => 'text',
        'required'  => true,
        'maxlength' => 255,
    ],
    'address' => [
        'type'      => 'text',
        'required'  => true,
        'maxlength' => 500,
    ],
    'dic' => [
        'type'      => 'text',
        'required'  => false,
        'maxlength' => 20,
    ],
    'contact_name' => [
        'type'      => 'text',
        'required'  => true,
        'maxlength' => 150,
    ],
    'email' => [
        'type'      => 'email',
        'required'  => true,
        'maxlength' => 150,
    ],
    'phone' => [
        'type'      => 'text',
        'required'  => true,
        'maxlength' => 50,
    ],
    'website' => [
        'type'      => 'url',
        'required'  => false,
        'maxlength' => 255,
    ],
    'social_networks' => [
        'type'     => 'textarea',
        'required' => false,
        'rows'     => 3,
    ],
    'sortiment' => [
        'type'     => 'textarea',
        'required' => true,
        'rows'     => 5,
    ],
    'festivals' => [
        'type'     => 'checkbox_group',
        'required' => true,
        'source'   => 'config',      // načte z config/festivals.php
    ],
    'terms' => [
        'type'     => 'checkbox',
        'required' => true,
    ],
];