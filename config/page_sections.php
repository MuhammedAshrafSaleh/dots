<?php

/*
|--------------------------------------------------------------------------
| Page-section schemas
|--------------------------------------------------------------------------
|
| One entry per `page.key` (see ERD "page_sections schemas"). Each field is
| either a scalar type or a repeatable list. The admin form, validation and
| the PageSection accessor are all generated from this config.
|
| Field types:
|   text       translatable {ar, en} string
|   link       plain string (URL / route)
|   icon       plain string (Lucide icon name)
|   media      single image media item on the `section` collection (JPEG/PNG/WebP/GIF)
|   video      single video media item on the `section` collection (MP4/WebM).
|              Declared as ['type' => 'video', 'poster' => '<field>'] so the admin
|              can warn when the video is set but its poster image is empty — a
|              hero in that state shows visitors a black box while the video loads.
|   media_many multiple image media items (uses `count`)
|   list       fixed-length repeatable group (uses `count` + `fields`)
|
| Media lives on the row's media-library `section` collection (distinguished
| by a `field` custom property), not in the `content` JSON. It is declared
| here so the admin knows to render an uploader.
|
*/

return [

    'home' => [
        'hero' => [
            'headline' => 'text',
            'subheadline' => 'text',
            'eyebrow' => 'text',
            'button1_label' => 'text',
            'button1_link' => 'link',
            'button2_label' => 'text',
            'button2_link' => 'link',
            'video' => ['type' => 'video', 'poster' => 'poster'],
            'poster' => 'media',
        ],
        'features' => [
            'items' => ['type' => 'list', 'count' => 4, 'fields' => [
                'icon' => 'icon',
                'text' => 'text',
            ]],
        ],
        'categories_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'button_label' => 'text',
        ],
        'featured_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'tab_new_label' => 'text',
            'tab_best_label' => 'text',
            'tab_smart_label' => 'text',
        ],
        'services_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'button_label' => 'text',
        ],
        'smart_banner' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'text' => 'text',
            'button1_label' => 'text',
            'button1_link' => 'link',
            'button2_label' => 'text',
            'button2_link' => 'link',
            'image' => 'media',
        ],
        'brands_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'button_label' => 'text',
        ],
    ],

    'about' => [
        'hero' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'text' => 'text',
            'image' => 'media',
            'video' => ['type' => 'video', 'poster' => 'image'],
            'button1_label' => 'text',
            'button1_link' => 'link',
            'button2_label' => 'text',
            'button2_link' => 'link',
        ],
        'story' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'text' => 'text',
            'images' => ['type' => 'media_many', 'count' => 3],
        ],
        'stats' => [
            'items' => ['type' => 'list', 'count' => 4, 'fields' => [
                'value' => 'text',
                'suffix' => 'text',
                'label' => 'text',
            ]],
        ],
        'mission_vision' => [
            'mission_text' => 'text',
            'mission_image' => 'media',
            'vision_text' => 'text',
            'vision_image' => 'media',
        ],
        'disciplines' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'items' => ['type' => 'list', 'count' => 3, 'fields' => [
                'title' => 'text',
                'text' => 'text',
                'icon' => 'icon',
                'image' => 'media',
                'link' => 'link',
            ]],
        ],
        'why' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'items' => ['type' => 'list', 'count' => 6, 'fields' => [
                'icon' => 'icon',
                'title' => 'text',
                'text' => 'text',
            ]],
        ],
        'partners_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'button_label' => 'text',
        ],
        'cta' => [
            'title' => 'text',
            'text' => 'text',
            'background_image' => 'media',
        ],
    ],

    'services' => [
        'head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'intro' => 'text',
            'button_label' => 'text',
        ],
        'process' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'items' => ['type' => 'list', 'count' => 4, 'fields' => [
                'title' => 'text',
                'text' => 'text',
            ]],
        ],
        'faq_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
        ],
    ],

    'brands' => [
        'head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'lede' => 'text',
        ],
    ],

    'projects' => [
        'current_hero' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'lede' => 'text',
            'image' => 'media',
            'button_label' => 'text',
        ],
        'previous_head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'lede' => 'text',
            'image' => 'media',
        ],
    ],

    'blog' => [
        'head' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'lede' => 'text',
        ],
    ],

    'global' => [
        'quote_band' => [
            'eyebrow' => 'text',
            'title' => 'text',
            'bullets' => ['type' => 'list', 'count' => 4, 'fields' => [
                'text' => 'text',
            ]],
            'hours_text' => 'text',
            'button_label' => 'text',
        ],
        'mega_promo' => [
            'image' => 'media',
            'title' => 'text',
            'button_label' => 'text',
            'button_link' => 'link',
        ],
        'newsletter' => [
            'eyebrow' => 'text',
            'title' => 'text',
        ],
    ],

];
