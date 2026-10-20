<?php

/*
|--------------------------------------------------------------------------
| Admin sidebar navigation
|--------------------------------------------------------------------------
|
| Mirrors ASidebar (admin-kit.jsx A_NAV). Each group has a label key
| (admin.php nav.* in lang) and a list of items:
|   [key, icon, label key, route name, view permission]
|
| The sidebar links an item only when its route exists (built in a later
| task) and the current admin holds the permission; otherwise it renders a
| disabled "soon" placeholder so the shell is complete from day one.
|
*/

return [
    'groups' => [
        [
            'label' => null,
            'items' => [
                ['dashboard', 'layout-dashboard', 'dashboard', 'admin.dashboard', 'dashboard.view'],
            ],
        ],
        [
            'label' => 'catalog',
            'items' => [
                ['products', 'package', 'products', 'admin.products', 'products.view'],
                ['import', 'upload', 'import_products', 'admin.products.import', 'products.import'],
                ['categories', 'folder-tree', 'categories', 'admin.categories', 'categories.view'],
                ['brands', 'award', 'brands', 'admin.brands', 'brands.view'],
                ['attributes', 'sliders-horizontal', 'attributes', 'admin.attributes', 'attributes.view'],
            ],
        ],
        [
            'label' => 'content',
            'items' => [
                ['pagecontent', 'layout-template', 'page_content', 'admin.page-content', 'page_content.view'],
                ['services', 'wrench', 'services', 'admin.services', 'services.view'],
                ['projects', 'building-2', 'projects', 'admin.projects', 'projects.view'],
                ['blog', 'newspaper', 'blog', 'admin.blog', 'posts.view'],
                ['contentpages', 'file-text', 'content_pages', 'admin.content-pages', 'content_pages.view'],
            ],
        ],
        [
            'label' => 'sales',
            'items' => [
                ['orders', 'shopping-bag', 'orders', 'admin.orders', 'orders.view'],
                ['customers', 'users-round', 'customers', 'admin.customers', 'customers.view'],
                ['coupons', 'ticket-percent', 'coupons', 'admin.coupons', 'coupons.view'],
            ],
        ],
        [
            'label' => 'leads_group',
            'items' => [
                ['leads', 'message-circle', 'whatsapp_leads', 'admin.leads', 'leads.view'],
                ['consultations', 'calendar-check', 'consultations', 'admin.consultations', 'consultations.view'],
                ['newsletter', 'mail', 'newsletter', 'admin.newsletter', 'newsletter.view'],
            ],
        ],
        [
            'label' => 'settings_group',
            'items' => [
                ['delivery', 'truck', 'delivery', 'admin.delivery', 'delivery_methods.view'],
                ['payments', 'credit-card', 'payments', 'admin.payments', 'payment_methods.view'],
                ['settings', 'settings', 'settings', 'admin.settings', 'settings.view'],
                ['users', 'users', 'users', 'admin.users', 'users.view'],
            ],
        ],
    ],
];
