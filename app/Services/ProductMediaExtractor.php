<?php

namespace App\Services;

use App\Exceptions\MediaImportException;
use ZipArchive;

/**
 * Safely extracts an uploaded media ZIP to a private temp dir (IMPORT_SPEC §6).
 *
 * Whole-ZIP violations throw MediaImportException (over size/entry limits,
 * zip-slip, duplicate basenames). A single disallowed-type / over-sized /
 * fake-MIME file is skipped and recorded as a warning. Returns the valid files
 * keyed by lower-cased basename, plus the temp dir to clean up afterwards.
 */
final class ProductMediaExtractor
{
    private const JUNK_PREFIXES = ['__macosx/', '._'];

    private const JUNK_NAMES = ['.ds_store', 'thumbs.db'];

    private const ALLOWED_MIME = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        // IES/LDT are plain-text photometric files — accept text/octet-stream.
        'ies' => ['text/plain', 'application/octet-stream'],
        'ldt' => ['text/plain', 'application/octet-stream'],
    ];

    /**
     * @param  list<array{sheet: string, row: int, column: string, message: string}>  $warnings
     * @return array{dir: string, files: array<string, array{path: string, ext: string, type: string}>}
     *
     * @throws MediaImportException
     */
    public function extract(string $zipPath, array &$warnings): array
    {
        $cfg = (array) config('import.media');
        $maxZip = (int) $cfg['max_zip_size'];
        $maxEntries = (int) $cfg['max_entries'];
        $maxUncompressed = (int) $cfg['max_uncompressed_size'];
        $maxFile = (int) $cfg['max_file_size'];
        $imageExts = (array) $cfg['image_extensions'];
        $allExts = [...$imageExts, ...(array) $cfg['file_extensions']];

        if (filesize($zipPath) > $maxZip) {
            throw new MediaImportException(__('admin.import_products.media_zip_too_large'));
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new MediaImportException(__('admin.import_products.media_zip_unreadable'));
        }

        if ($zip->numFiles > $maxEntries) {
            $zip->close();
            throw new MediaImportException(__('admin.import_products.media_zip_too_many'));
        }

        // Pass 1 — validate the whole archive (names, totals, duplicates).
        $entries = [];      // lowerBasename → entry index
        $totalUncompressed = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }
            $name = $stat['name'];

            if (str_ends_with($name, '/')) {
                continue; // directory entry
            }
            if ($this->isJunk($name)) {
                continue;
            }
            // Zip-slip: reject any path traversal outright.
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, ':\\')) {
                $zip->close();
                throw new MediaImportException(__('admin.import_products.media_zip_traversal'));
            }

            $lower = mb_strtolower(basename($name));
            $totalUncompressed += (int) $stat['size'];

            if (isset($entries[$lower])) {
                $zip->close();
                throw new MediaImportException(__('admin.import_products.media_zip_duplicate', ['name' => basename($name)]));
            }
            $entries[$lower] = $i;
        }

        if ($totalUncompressed > $maxUncompressed) {
            $zip->close();
            throw new MediaImportException(__('admin.import_products.media_zip_too_large'));
        }

        // Pass 2 — extract + validate each file individually.
        $dir = storage_path('app/private/import-media/'.bin2hex(random_bytes(8)));
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $files = [];

        foreach ($entries as $lower => $index) {
            $base = basename($zip->getNameIndex($index));
            $ext = mb_strtolower(pathinfo($base, PATHINFO_EXTENSION));

            if (! in_array($ext, $allExts, true)) {
                $warnings[] = $this->warn('media', $base, __('admin.import_products.media_disallowed_type', ['name' => $base]));

                continue;
            }

            $stat = $zip->statIndex($index);
            if ($stat !== false && (int) $stat['size'] > $maxFile) {
                $warnings[] = $this->warn('media', $base, __('admin.import_products.media_file_too_large', ['name' => $base]));

                continue;
            }

            $contents = $zip->getFromIndex($index);
            if ($contents === false) {
                $warnings[] = $this->warn('media', $base, __('admin.import_products.media_file_unreadable', ['name' => $base]));

                continue;
            }

            $target = $dir.'/'.$lower;
            file_put_contents($target, $contents);

            if (! $this->mimeMatches($target, $ext)) {
                @unlink($target);
                $warnings[] = $this->warn('media', $base, __('admin.import_products.media_fake_type', ['name' => $base]));

                continue;
            }

            $files[$lower] = [
                'path' => $target,
                'ext' => $ext,
                'type' => in_array($ext, $imageExts, true) ? 'image' : 'file',
            ];
        }

        $zip->close();

        return ['dir' => $dir, 'files' => $files];
    }

    private function isJunk(string $name): bool
    {
        $lower = mb_strtolower($name);
        foreach (self::JUNK_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix) || str_contains($lower, '/'.$prefix)) {
                return true;
            }
        }

        return in_array(mb_strtolower(basename($name)), self::JUNK_NAMES, true);
    }

    private function mimeMatches(string $path, string $ext): bool
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return true;
        }
        $mime = (string) finfo_file($finfo, $path);
        finfo_close($finfo);

        $allowed = self::ALLOWED_MIME[$ext] ?? [];

        return in_array($mime, $allowed, true);
    }

    /** @return array{sheet: string, row: int, column: string, message: string} */
    private function warn(string $column, string $name, string $message): array
    {
        return ['sheet' => __('admin.import_products.media_sheet'), 'row' => 0, 'column' => $name, 'message' => $message];
    }
}
