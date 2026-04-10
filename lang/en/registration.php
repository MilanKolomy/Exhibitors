<?php
return [
    'title'       => 'Exhibitor Registration',
    'subtitle'    => 'Please fill in the registration form',
    'success_title'   => 'Thank you for registering!',
    'success_text'    => 'Your registration has been successfully submitted. We will contact you.',
    'fields' => [
        'ico' => [
            'label' => 'Company ID (IČ)',
            'hint'  => 'Enter your Czech company ID and click the button to load data from ARES',
        ],
        'company' => [
            'label' => 'Company name',
            'hint'  => 'Auto-filled from ARES, can be edited',
        ],
        'address' => [
            'label' => 'Registered address',
            'hint'  => 'Auto-filled from ARES, can be edited',
        ],
        'dic' => [
            'label' => 'VAT number (DIČ)',
            'hint'  => 'Fill in if you are a VAT payer',
        ],
        'contact_name' => [
            'label' => 'Contact person',
            'hint'  => 'Full name of the responsible person',
        ],
        'email' => [
            'label' => 'E-mail',
            'hint'  => 'Contact e-mail of the responsible person',
        ],
        'phone' => [
            'label' => 'Phone',
            'hint'  => 'Contact phone e.g. +420 xxx xxx xxx',
        ],
        'website' => [
            'label' => 'Website',
            'hint'  => 'E.g. https://www.your-company.com',
        ],
        'social_networks' => [
            'label' => 'Social networks',
            'hint'  => 'Links to Facebook, Instagram etc. (one per line)',
        ],
        'sortiment' => [
            'label' => 'Products / services offered',
            'hint'  => 'Describe what you will be offering at the festival',
        ],
        'festivals' => [
            'label' => 'I will attend these festivals',
            'hint'  => 'Select all dates you are interested in',
        ],
        'terms' => [
            'label' => 'I agree to the Terms & Conditions',
            'hint'  => '',
        ],
    ],
    'errors' => [
        'ico'          => 'Company ID is required',
        'company'      => 'Company name is required',
        'address'      => 'Address is required',
        'contact_name' => 'Contact person name is required',
        'email'        => 'Enter a valid e-mail address',
        'phone'        => 'Phone number is required',
        'sortiment'    => 'Product description is required',
        'festivals'    => 'Please select at least one festival',
        'terms'        => 'You must agree to the Terms & Conditions',
        'captcha'      => 'reCAPTCHA verification failed, please try again',
    ],
];