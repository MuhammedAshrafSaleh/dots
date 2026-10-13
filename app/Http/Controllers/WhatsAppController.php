<?php

namespace App\Http\Controllers;

use App\Enums\QuoteSource;
use App\Models\QuoteRequest;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function redirect(Request $request, string $locale, string $source): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $src = QuoteSource::tryFrom($source);
        if ($src === null) {
            abort(404);
        }

        QuoteRequest::create([
            'source' => $src,
            'sourceable_type' => $request->query('model_type'),
            'sourceable_id' => $request->query('model_id'),
            'product_variant_id' => $request->query('variant_id'),
            'qty' => $request->query('qty'),
            'page_url' => $request->query('ref_url'),
            'locale' => $locale,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ]);

        $settings = app(Settings::class);
        $number = $settings->get('contact.whatsapp_number', '');
        $text = $this->buildText($src, $locale, $request, $settings);

        return redirect('https://wa.me/'.$number.'?text='.rawurlencode($text));
    }

    private function buildText(
        QuoteSource $src,
        string $locale,
        Request $request,
        Settings $settings,
    ): string {
        $tplKey = match ($src) {
            QuoteSource::Product => 'whatsapp.tpl_product',
            QuoteSource::Cart => 'whatsapp.tpl_cart',
            QuoteSource::InstapayOrder => 'whatsapp.tpl_instapay_order',
            QuoteSource::Boq => 'whatsapp.tpl_boq',
            default => 'whatsapp.tpl_general',
        };

        $tpl = $settings->translated($tplKey, $locale, '');

        $vars = [
            '{url}' => $request->query('ref_url', ''),
            '{product_name}' => $request->query('product_name', ''),
            '{sku}' => $request->query('sku', ''),
            '{customer_name}' => '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $tpl);
    }
}
