<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\Settings;
use App\Support\CacheTags;

class SettingObserver
{
    public function __construct(private readonly Settings $settings) {}

    public function saved(Setting $setting): void
    {
        $this->flush($setting);
    }

    public function deleted(Setting $setting): void
    {
        $this->flush($setting);
    }

    private function flush(Setting $setting): void
    {
        // Bust the per-key entry used by the Settings repository, plus the tag.
        $this->settings->forget("{$setting->group}.{$setting->key}");
        CacheTags::flush(CacheTags::SETTINGS);
    }
}
