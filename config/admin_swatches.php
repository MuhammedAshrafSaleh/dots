<?php

/*
|--------------------------------------------------------------------------
| Attribute swatches
|--------------------------------------------------------------------------
|
| Preset swatch values for swatch-type attribute values (admin-kit.jsx
| A_SWATCHES). Each entry is [css value => label key suffix]. A "custom"
| option lets an admin paste any CSS colour/gradient.
|
*/

return [
    'var(--ink-900)' => ['ar' => 'أسود', 'en' => 'Black'],
    'var(--white)' => ['ar' => 'أبيض', 'en' => 'White'],
    'var(--ink-400)' => ['ar' => 'رمادي', 'en' => 'Grey'],
    'linear-gradient(135deg, var(--yellow-300), var(--yellow-800))' => ['ar' => 'نحاسي', 'en' => 'Brass'],
    'linear-gradient(135deg, var(--ink-100), var(--ink-400))' => ['ar' => 'كروم', 'en' => 'Chrome'],
    'linear-gradient(135deg, var(--ink-700), var(--ink-900))' => ['ar' => 'جرافيت', 'en' => 'Graphite'],
];
