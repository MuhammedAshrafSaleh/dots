<?php

namespace App\Services;

/**
 * Builds WhatsApp messages and wa.me links from admin-editable templates in
 * the `settings` table (group `whatsapp`, keys `tpl_*`). Message text is always
 * rendered from those settings by replacing `{placeholder}` tokens — never from
 * hardcoded strings — so the admin can change the wording per locale.
 */
class WhatsApp
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * Render a template (e.g. `product` → setting `whatsapp.tpl_product`) for a
     * locale, substituting `{key}` tokens from $replacements.
     *
     * @param  array<string, string|int|float>  $replacements
     */
    public function message(string $template, array $replacements = [], ?string $locale = null): string
    {
        $text = $this->settings->translated("whatsapp.tpl_{$template}", $locale);
        $text = is_string($text) ? $text : '';

        foreach ($replacements as $key => $value) {
            $text = str_replace('{'.$key.'}', (string) $value, $text);
        }

        return trim($text);
    }

    /**
     * A wa.me link for a template, to the configured business number.
     *
     * @param  array<string, string|int|float>  $replacements
     */
    public function link(string $template, array $replacements = [], ?string $locale = null): string
    {
        $number = preg_replace('/\D+/', '', (string) $this->settings->get('contact.whatsapp_number')) ?? '';

        return "https://wa.me/{$number}?text=".rawurlencode($this->message($template, $replacements, $locale));
    }
}
