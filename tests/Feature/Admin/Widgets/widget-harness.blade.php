<div>
    <x-admin.field-bilingual label="Name" model="name" :counter="60" />

    <x-admin.slugs model="slug" path="/products" generate="gen" />

    <x-admin.seo slug-model="slug" path="/products" generate="gen" />

    <x-admin.switch wire:model="flag" label="Published" help="Visible on the store" />

    <x-admin.chips
        label="Tags"
        model="tags"
        :options="[['value' => 'lighting', 'label' => 'Lighting'], ['value' => 'smart', 'label' => 'Smart']]"
        :selected="$tags"
    />

    <x-admin.icon-pick label="Icon" model="icon" :value="$icon" />

    <x-admin.rich label="Body" model="body" />

    <x-admin.image label="Logo" model="logo" :url="null" remove="$set('logo', null)" />
    <x-admin.file label="Catalogue" model="catalogue" :name="null" />
    <x-admin.gallery label="Gallery" model="photos" :items="[]" />

    <x-admin.collapse title="Advanced" :open="true">
        <p>Inside</p>
    </x-admin.collapse>

    <x-admin.menu label="Actions">
        <button type="button">Edit</button>
    </x-admin.menu>

    <x-admin.table :empty="false">
        <x-slot:head>
            <x-admin.sort-th column="name" label="Name" :current="$sortCol" :dir="$sortDir" />
            <th>SKU</th>
        </x-slot:head>
        <tr><td>Row</td><td>SKU-1</td></tr>
    </x-admin.table>

    <x-admin.bulk-bar :count="2" clear="clearSelection">
        <button type="button">Publish</button>
    </x-admin.bulk-bar>

    <div x-data="{ open: false }">
        <x-admin.drawer title="Edit" show="open">
            <p>Drawer body</p>
            <x-slot:foot><button type="button">Save</button></x-slot:foot>
        </x-admin.drawer>

        <x-admin.confirm title="Delete?" body="This moves products to the parent." show="open">
            <x-slot:action><button type="button">Delete</button></x-slot:action>
        </x-admin.confirm>
    </div>

    <x-admin.empty title="Nothing here" body="Try another search." />
</div>
