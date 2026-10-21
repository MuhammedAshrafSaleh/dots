<?php

/*
|--------------------------------------------------------------------------
| Bulk product import
|--------------------------------------------------------------------------
|
| Limits for the optional media ZIP uploaded alongside the import xlsx
| (IMPORT_SPEC §6 / §8). Sizes are in bytes. Whole-ZIP violations
| (over max_zip_size / max_uncompressed_size / max_entries, zip-slip,
| duplicate basenames) reject the entire ZIP; a single over-sized or
| fake-MIME file is skipped with a warning.
|
*/

return [
    'media' => [
        'max_zip_size' => 64 * 1024 * 1024,          // 64 MB compressed
        'max_uncompressed_size' => 256 * 1024 * 1024, // 256 MB extracted (zip-bomb guard)
        'max_entries' => 2000,
        'max_file_size' => 16 * 1024 * 1024,          // 16 MB per file
        'image_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'file_extensions' => ['pdf', 'ies', 'ldt'],
    ],
];
