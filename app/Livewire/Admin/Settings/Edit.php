<?php

namespace App\Livewire\Admin\Settings;

use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use WithFileUploads;

    public string $activeTab = 'contact';

    // --- contact ---
    public string $whatsapp_number = '';

    public string $hotline = '';

    public string $email = '';

    public string $maps_url = '';

    /** @var array<string, string> */
    public array $showroom_label = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $showroom_address = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $working_hours = ['ar' => '', 'en' => ''];

    public string $social_instagram = '';

    public string $social_facebook = '';

    public string $social_linkedin = '';

    public string $social_youtube = '';

    // --- commerce ---
    public string $instapay_address = '';

    public string $vat_rate = '14';

    /** @var array<string, string> */
    public array $vat_note = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $project_pricing_note = ['ar' => '', 'en' => ''];

    public string $low_stock_threshold = '5';

    public string $instapay_hold_hours = '24';

    public string $order_prefix = 'DT-';

    public string $currency = 'EGP';

    /** @var array<int, array<string, string>> */
    public array $trust_items = [];

    /** @var array<string, string> */
    public array $product_delivery_text = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $product_returns_text = ['ar' => '', 'en' => ''];

    // --- whatsapp templates ---
    /** @var array<string, string> */
    public array $tpl_product = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $tpl_cart = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $tpl_instapay_order = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $tpl_boq = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $tpl_general = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $tpl_order_confirm_admin = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $tpl_order_update = ['ar' => '', 'en' => ''];

    // --- footer ---
    /** @var array<string, string> */
    public array $footer_about_text = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $footer_payments_line = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $footer_copyright = ['ar' => '', 'en' => ''];

    // --- brand ---
    public string $brand_logo_url = '';   // stored URL from settings (persisted)

    public mixed $logoUpload = null;      // temp upload this session

    // --- seo ---
    /** @var array<string, string> */
    public array $seo_default_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $seo_default_description = ['ar' => '', 'en' => ''];

    public string $seo_og_image = '';

    /** @return array<string, array<int, string>> */
    public static function templateVars(): array
    {
        return [
            'tpl_product' => ['product_name', 'sku', 'url'],
            'tpl_cart' => ['items', 'total'],
            'tpl_instapay_order' => ['order_number', 'total'],
            'tpl_boq' => ['customer_name'],
            'tpl_general' => [],
            'tpl_order_confirm_admin' => ['order_number', 'customer_name', 'total'],
            'tpl_order_update' => ['order_number', 'status', 'url'],
        ];
    }

    public function mount(Settings $settings): void
    {
        // Contact
        $this->whatsapp_number = (string) ($settings->get('contact.whatsapp_number') ?? '');
        $this->hotline = (string) ($settings->get('contact.hotline') ?? '');
        $this->email = (string) ($settings->get('contact.email') ?? '');
        $this->maps_url = (string) ($settings->get('contact.maps_url') ?? '');
        $this->showroom_label = $this->bi($settings->get('contact.showroom_label'));
        $this->showroom_address = $this->bi($settings->get('contact.showroom_address'));
        $this->working_hours = $this->bi($settings->get('contact.working_hours'));
        $this->social_instagram = (string) ($settings->get('contact.social_instagram') ?? '');
        $this->social_facebook = (string) ($settings->get('contact.social_facebook') ?? '');
        $this->social_linkedin = (string) ($settings->get('contact.social_linkedin') ?? '');
        $this->social_youtube = (string) ($settings->get('contact.social_youtube') ?? '');

        // Commerce
        $this->instapay_address = (string) ($settings->get('commerce.instapay_address') ?? '');
        $this->vat_rate = (string) ($settings->get('commerce.vat_rate') ?? '14');
        $this->vat_note = $this->bi($settings->get('commerce.vat_note'));
        $this->project_pricing_note = $this->bi($settings->get('commerce.project_pricing_note'));
        $this->low_stock_threshold = (string) ($settings->get('commerce.low_stock_threshold') ?? '5');
        $this->instapay_hold_hours = (string) ($settings->get('commerce.instapay_hold_hours') ?? '24');
        $this->order_prefix = (string) ($settings->get('commerce.order_prefix') ?? 'DT-');
        $this->currency = (string) ($settings->get('commerce.currency') ?? 'EGP');
        $trust = $settings->get('commerce.product_trust_items');
        $this->trust_items = is_array($trust) ? array_map(
            fn ($item) => [
                'icon' => (string) ($item['icon'] ?? 'shield-check'),
                'ar' => (string) ($item['ar'] ?? ''),
                'en' => (string) ($item['en'] ?? ''),
            ],
            $trust
        ) : [
            ['icon' => 'shield-check', 'ar' => '', 'en' => ''],
            ['icon' => 'truck', 'ar' => '', 'en' => ''],
            ['icon' => 'message-circle', 'ar' => '', 'en' => ''],
        ];
        // Ensure exactly 3 items
        while (count($this->trust_items) < 3) {
            $this->trust_items[] = ['icon' => 'shield-check', 'ar' => '', 'en' => ''];
        }
        $this->trust_items = array_slice($this->trust_items, 0, 3);
        $this->product_delivery_text = $this->bi($settings->get('commerce.product_delivery_text'));
        $this->product_returns_text = $this->bi($settings->get('commerce.product_returns_text'));

        // WhatsApp templates
        foreach (array_keys(self::templateVars()) as $key) {
            $this->{$key} = $this->bi($settings->get("whatsapp.{$key}"));
        }

        // Footer
        $this->footer_about_text = $this->bi($settings->get('footer.about_text'));
        $this->footer_payments_line = $this->bi($settings->get('footer.payments_line'));
        $this->footer_copyright = $this->bi($settings->get('footer.copyright'));

        // Brand
        $this->brand_logo_url = (string) ($settings->get('brand.logo_url') ?? '');

        // SEO
        $this->seo_default_title = $this->bi($settings->get('seo.default_title'));
        $this->seo_default_description = $this->bi($settings->get('seo.default_description'));
        $this->seo_og_image = (string) ($settings->get('seo.og_image') ?? '');
    }

    public function save(Settings $settings): void
    {
        $this->authorize('settings.update');

        $this->validate([
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'hotline' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'low_stock_threshold' => ['required', 'integer', 'min:1'],
            'instapay_hold_hours' => ['required', 'integer', 'min:1'],
            'order_prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'currency' => ['required', 'string', 'max:10'],
            'seo_og_image' => ['nullable', 'max:500'],
            'logoUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        // Contact
        $settings->set('contact.whatsapp_number', $this->whatsapp_number);
        $settings->set('contact.hotline', $this->hotline);
        $settings->set('contact.email', $this->email);
        $settings->set('contact.maps_url', $this->maps_url);
        $settings->set('contact.showroom_label', $this->showroom_label);
        $settings->set('contact.showroom_address', $this->showroom_address);
        $settings->set('contact.working_hours', $this->working_hours);
        $settings->set('contact.social_instagram', $this->social_instagram);
        $settings->set('contact.social_facebook', $this->social_facebook);
        $settings->set('contact.social_linkedin', $this->social_linkedin);
        $settings->set('contact.social_youtube', $this->social_youtube);

        // Commerce
        $settings->set('commerce.instapay_address', $this->instapay_address);
        $settings->set('commerce.vat_rate', (float) $this->vat_rate);
        $settings->set('commerce.vat_note', $this->vat_note);
        $settings->set('commerce.project_pricing_note', $this->project_pricing_note);
        $settings->set('commerce.low_stock_threshold', (int) $this->low_stock_threshold);
        $settings->set('commerce.instapay_hold_hours', (int) $this->instapay_hold_hours);
        $settings->set('commerce.order_prefix', $this->order_prefix);
        $settings->set('commerce.currency', $this->currency);
        $settings->set('commerce.product_trust_items', $this->trust_items);
        $settings->set('commerce.product_delivery_text', $this->product_delivery_text);
        $settings->set('commerce.product_returns_text', $this->product_returns_text);

        // WhatsApp templates
        foreach (array_keys(self::templateVars()) as $key) {
            $settings->set("whatsapp.{$key}", $this->{$key});
        }

        // Footer
        $settings->set('footer.about_text', $this->footer_about_text);
        $settings->set('footer.payments_line', $this->footer_payments_line);
        $settings->set('footer.copyright', $this->footer_copyright);

        // Brand logo
        if ($this->logoUpload instanceof TemporaryUploadedFile) {
            $this->deleteStoredLogo();
            $path = $this->logoUpload->storeAs('brand', 'logo.'.$this->logoUpload->getClientOriginalExtension(), 'public');
            $this->brand_logo_url = Storage::disk('public')->url($path);
            $settings->set('brand.logo_url', $this->brand_logo_url);
            $this->logoUpload = null;
        }

        // SEO
        $settings->set('seo.default_title', $this->seo_default_title);
        $settings->set('seo.default_description', $this->seo_default_description);
        $settings->set('seo.og_image', $this->seo_og_image);

        $this->dispatch('toast', tone: 'success', title: __('admin.settings.saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.edit', [
            'templateVars' => self::templateVars(),
        ]);
    }

    public function removeLogo(Settings $settings): void
    {
        $this->authorize('settings.update');
        $this->deleteStoredLogo();
        $this->brand_logo_url = '';
        $this->logoUpload = null;
        $settings->set('brand.logo_url', '');
        $this->dispatch('toast', tone: 'success', title: __('admin.settings.saved'));
    }

    private function deleteStoredLogo(): void
    {
        if ($this->brand_logo_url && str_contains($this->brand_logo_url, '/storage/brand/')) {
            Storage::disk('public')->delete('brand/'.basename($this->brand_logo_url));
        }
    }

    /** @return array<string, string> */
    private function bi(mixed $value): array
    {
        if (is_array($value)) {
            return ['ar' => (string) ($value['ar'] ?? ''), 'en' => (string) ($value['en'] ?? '')];
        }

        return ['ar' => '', 'en' => ''];
    }
}
