<?php

/*
|--------------------------------------------------------------------------
| Admin permissions
|--------------------------------------------------------------------------
|
| One permission per admin area + action (PRD §11). A single "Admin" role
| holds all of them, but every route/action checks a named permission so
| more roles can be added later without rewriting. The seeder and the admin
| policies/gates read this flat list.
|
*/

return [
    // Dashboard
    'dashboard.view',

    // Catalog
    'products.view', 'products.create', 'products.update', 'products.delete', 'products.publish',
    'categories.view', 'categories.create', 'categories.update', 'categories.delete',
    'brands.view', 'brands.create', 'brands.update', 'brands.delete',
    'attributes.view', 'attributes.create', 'attributes.update', 'attributes.delete',

    // Content
    'page_content.view', 'page_content.update',
    'services.view', 'services.create', 'services.update', 'services.delete',
    'projects.view', 'projects.create', 'projects.update', 'projects.delete',
    'posts.view', 'posts.create', 'posts.update', 'posts.delete',
    'post_categories.manage',
    'tags.manage',
    'content_pages.view', 'content_pages.create', 'content_pages.update', 'content_pages.delete',

    // Sales
    'orders.view', 'orders.update', 'orders.delete',
    'customers.view', 'customers.update',
    'coupons.view', 'coupons.create', 'coupons.update', 'coupons.delete',

    // Leads
    'leads.view', 'leads.update',
    'consultations.view', 'consultations.update',
    'newsletter.view', 'newsletter.export',

    // Settings
    'delivery_methods.view', 'delivery_methods.create', 'delivery_methods.update', 'delivery_methods.delete',
    'payment_methods.view', 'payment_methods.update',
    'settings.view', 'settings.update',
    'users.view', 'users.create', 'users.update', 'users.delete',
];
