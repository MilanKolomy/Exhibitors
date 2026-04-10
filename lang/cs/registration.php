<?php
return [
    'title'       => 'Registrace vystavovatele',
    'subtitle'    => 'Vyplňte prosím registrační formulář',
    'success_title'   => 'Děkujeme za registraci!',
    'success_text'    => 'Vaše registrace byla úspěšně odeslána. Budeme vás kontaktovat.',
    'fields' => [
        'ico' => [
            'label' => 'IČ',
            'hint'  => 'Po zadání IČ klikněte na tlačítko pro načtení údajů z registru ARES',
        ],
        'company' => [
            'label' => 'Název firmy / obchodní jméno',
            'hint'  => 'Vyplní se automaticky z ARES, lze upravit',
        ],
        'address' => [
            'label' => 'Adresa sídla',
            'hint'  => 'Vyplní se automaticky z ARES, lze upravit',
        ],
        'dic' => [
            'label' => 'DIČ',
            'hint'  => 'Vyplňte pokud jste plátce DPH',
        ],
        'contact_name' => [
            'label' => 'Jméno odpovědné osoby',
            'hint'  => 'Jméno a příjmení kontaktní osoby',
        ],
        'email' => [
            'label' => 'E-mail',
            'hint'  => 'Kontaktní e-mail odpovědné osoby',
        ],
        'phone' => [
            'label' => 'Telefon',
            'hint'  => 'Kontaktní telefon ve formátu +420 xxx xxx xxx',
        ],
        'website' => [
            'label' => 'Webové stránky',
            'hint'  => 'Např. https://www.vase-firma.cz',
        ],
        'social_networks' => [
            'label' => 'Sociální sítě',
            'hint'  => 'Odkazy na Facebook, Instagram apod. (každý odkaz na nový řádek)',
        ],
        'sortiment' => [
            'label' => 'Nabízený sortiment',
            'hint'  => 'Popište zboží nebo služby, které budete na festivalu nabízet',
        ],
        'festivals' => [
            'label' => 'Zúčastním se těchto festivalů',
            'hint'  => 'Vyberte všechny termíny, které vás zajímají',
        ],
        'terms' => [
            'label' => 'Souhlasím s obchodními podmínkami',
            'hint'  => '',
        ],
    ],
    'errors' => [
        'ico'          => 'IČ je povinné pole',
        'company'      => 'Název firmy je povinný',
        'address'      => 'Adresa je povinná',
        'contact_name' => 'Jméno odpovědné osoby je povinné',
        'email'        => 'Zadejte platný e-mail',
        'phone'        => 'Telefon je povinný',
        'sortiment'    => 'Popis sortimentu je povinný',
        'festivals'    => 'Vyberte alespoň jeden festival',
        'terms'        => 'Musíte souhlasit s obchodními podmínkami',
        'captcha'      => 'Ověření reCAPTCHA selhalo, zkuste to prosím znovu',
    ],
];