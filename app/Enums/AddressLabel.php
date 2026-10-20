<?php

namespace App\Enums;

enum AddressLabel: string
{
    case Home = 'home';
    case Office = 'office';
    case Other = 'other';

    /** Lucide icon name for this label (mirror of Account.jsx KA_LABEL). */
    public function icon(): string
    {
        return match ($this) {
            self::Home => 'house',
            self::Office => 'building-2',
            self::Other => 'map-pin',
        };
    }

    /** Translated display label. */
    public function label(): string
    {
        return __('account.addresses.label_'.$this->value);
    }
}
