<?php

return [
    'admin_app_title_suffix' => 'Charlot Crea',
    // Keep the old CMS routes isolated while the Laravel-native admin replaces them.
    'admin_app_path' => 'legacy-cms',
    'enabled' => ['users-image' => false],
    'dashboard' => [
        'modules' => [
            ['name' => 'customers', 'label' => 'Müşteriler', 'label_singular' => 'Müşteri', 'count' => true, 'create' => true],
            ['name' => 'inquiries', 'label' => 'İletişim talepleri', 'label_singular' => 'Talep', 'count' => true, 'create' => false],
            ['name' => 'quotes', 'label' => 'Teklifler', 'label_singular' => 'Teklif', 'count' => true, 'create' => true],
            ['name' => 'works', 'label' => 'İşler', 'label_singular' => 'İş', 'count' => true, 'create' => true],
            ['name' => 'projects', 'label' => 'Portföy projeleri', 'label_singular' => 'Portföy projesi', 'count' => true, 'create' => true],
            ['name' => 'films', 'label' => 'Filmler', 'label_singular' => 'Film', 'count' => true, 'create' => true],
            ['name' => 'destinations', 'label' => 'Destinasyonlar', 'label_singular' => 'Destinasyon', 'count' => true, 'create' => true],
            ['name' => 'posts', 'label' => 'Blog', 'label_singular' => 'Yazı', 'count' => true, 'create' => true],
            ['name' => 'references', 'label' => 'Referanslar', 'label_singular' => 'Referans', 'count' => true, 'create' => true],
        ],
        'analytics' => ['enabled' => false],
        'search_endpoint' => 'twill.search',
        'auth_activity_log' => ['login' => false, 'logout' => false],
        'auth_activity_causer' => 'users',
    ],
];
