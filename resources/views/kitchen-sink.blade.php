<x-layouts.app title="Kitchen sink">
    <div style="padding:32px;display:grid;gap:32px;max-width:960px;margin-inline:auto;">
        <x-eyebrow>Kitchen sink — {{ app()->getLocale() }}</x-eyebrow>

        {{-- Buttons --}}
        <section style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <x-button variant="primary" icon-end="arrow-right">Primary</x-button>
            <x-button variant="dark">Dark</x-button>
            <x-button variant="outline">Outline</x-button>
            <x-button variant="ghost">Ghost</x-button>
            <x-button variant="whatsapp">WhatsApp</x-button>
            <x-button variant="quote-outline">Quote</x-button>
            <x-button variant="primary" size="sm">Small</x-button>
            <x-button variant="primary" size="lg">Large</x-button>
            <x-button variant="primary" href="#">Link</x-button>
            <x-icon-button icon="search" label="Search" variant="outline" />
            <x-icon-button icon="shopping-bag" label="Cart" variant="ghost" :count="3" />
        </section>

        {{-- Badges --}}
        <section style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            @foreach (['accent','dark','neutral','outline','success','warning','error','info','sale'] as $tone)
                <x-badge :tone="$tone">{{ ucfirst($tone) }}</x-badge>
            @endforeach
            <x-badge tone="success" dot>In stock</x-badge>
        </section>

        {{-- Chips --}}
        <section style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <x-chip>Chip</x-chip>
            <x-chip :active="true">Active</x-chip>
            <x-chip facet="Brand" :active="true" :removable="true">Philips</x-chip>
        </section>

        {{-- Forms --}}
        <section style="display:grid;gap:16px;max-width:420px;">
            <x-input label="Name" placeholder="Your name" help="As it appears on your ID" optional />
            <x-input label="Email" icon="mail" type="email" placeholder="you@example.com" />
            <x-input label="Password" type="password" error="Too short" />
            <x-input label="Message" :multiline="true" placeholder="Write something" />
            <x-select label="City" placeholder="Choose a city" :options="['Cairo','Giza','Alexandria']" />
            <x-checkbox label="Subscribe to the newsletter" :checked="true" />
            <x-checkbox label="In stock" :count="42" row />
            <x-radio-card title="Cash on delivery" desc="Pay when it arrives" icon="banknote" name="pay" value="cod" :checked="true" meta="Free" />
            <x-radio-card title="Card" desc="Pay now with Paymob" icon="credit-card" name="pay" value="card" />
            <x-quantity-stepper :value="2" name="qty" />
        </section>

        {{-- Tabs --}}
        <x-tabs :tabs="[['id'=>'desc','label'=>'Description'],['id'=>'specs','label'=>'Specs','count'=>8],['id'=>'reviews','label'=>'Reviews','disabled'=>true]]" value="desc" />
        <x-tabs variant="pill" :tabs="[['id'=>'all','label'=>'All'],['id'=>'new','label'=>'New']]" />

        {{-- Accordion --}}
        <x-accordion
            :items="[
                ['id'=>'ship','title'=>'Shipping','content'=>'We deliver across Egypt within 2–5 days.'],
                ['id'=>'ret','title'=>'Returns','content'=>'14-day returns on unused items.'],
            ]"
            :default-open="['ship']"
        />

        {{-- Breadcrumbs --}}
        <x-breadcrumbs :items="[['label'=>'Home','url'=>'#'],['label'=>'Lighting','url'=>'#'],['label'=>'Pendant lamp']]" />

        {{-- Pagination --}}
        <x-pagination :page="3" :total="12" :count="240" :per-page="20" />

        {{-- Image frame --}}
        <div style="max-width:360px;">
            <x-image-frame ratio="16 / 9" label="Product hero shot" />
        </div>

        {{-- Toasts --}}
        <div class="d-toasts" style="position:static;">
            <x-toast tone="success" title="Saved" message="Your changes were saved." />
            <x-toast tone="cart" title="Added to cart" message="Pendant lamp × 2" :actions="[['label'=>'View cart','url'=>'#']]" />
            <x-toast tone="error" title="Something went wrong" message="Please try again." />
        </div>
    </div>
</x-layouts.app>
