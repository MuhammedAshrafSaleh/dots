@php
    $ini = fn ($n) => collect(explode(' ', $n))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<div class="a-page" x-data="{ add: $wire.entangle('showAdd'), inviteChecked: true }">
    <x-admin.head :title="__('admin.users.title')" :sub="__('admin.users.subtitle')">
        <x-slot:actions>
            @can('users.create')
                <x-button icon="user-plus" x-on:click="add = true; inviteChecked = true">{{ __('admin.users.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.users.search')"
                wire:model.live.debounce.300ms="search" :aria-label="__('admin.users.search')" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.users.col_name') }}</th>
                <th>{{ __('admin.users.col_email') }}</th>
                <th class="is-shrink">{{ __('admin.users.col_role') }}</th>
                <th class="is-shrink">{{ __('admin.users.col_last') }}</th>
                <th class="is-shrink">{{ __('admin.users.col_status') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $user)
                @php $isSelf = $user->id === $me; @endphp
                <tr wire:key="user-{{ $user->id }}">
                    <td>
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <span class="a-avatar @if ($user->is_active) a-avatar--node @endif"
                                  style="{{ !$user->is_active ? 'background: var(--ink-150); color: var(--fg-2);' : '' }}">
                                {{ $ini($user->name) }}
                            </span>
                            <span>
                                <b style="font-weight: 700;">{{ $user->name }}</b>
                                @if ($isSelf)<span class="a-sub">{{ __('admin.users.you') }}</span>@endif
                            </span>
                        </span>
                    </td>
                    <td><span dir="ltr">{{ $user->email }}</span></td>
                    <td class="is-shrink">
                        <x-badge tone="dark">{{ __('admin.users.role_admin') }}</x-badge>
                    </td>
                    <td class="is-shrink" style="white-space: nowrap;">
                        <span class="a-sub">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : __('admin.users.never') }}
                        </span>
                    </td>
                    <td class="is-shrink">
                        @if ($user->is_active && !$user->last_login_at)
                            <x-badge tone="warning" :dot="true">{{ __('admin.users.status_invited') }}</x-badge>
                        @elseif ($user->is_active)
                            <x-badge tone="success" :dot="true">{{ __('admin.users.status_on') }}</x-badge>
                        @else
                            <x-badge tone="neutral" :dot="false">{{ __('admin.users.status_off') }}</x-badge>
                        @endif
                    </td>
                    <td class="is-shrink">
                        @if (! $isSelf)
                            <x-admin.menu>
                                @if ($user->is_active && !$user->last_login_at)
                                    @can('users.update')
                                        <button type="button" wire:click="resetPassword({{ $user->id }})">
                                            <x-icon name="send" size="16" />{{ __('admin.users.action_resend') }}
                                        </button>
                                    @endcan
                                @else
                                    @can('users.update')
                                        <button type="button" wire:click="resetPassword({{ $user->id }})">
                                            <x-icon name="key-round" size="16" />{{ __('admin.users.action_reset') }}
                                        </button>
                                    @endcan
                                @endif
                                @can('users.update')
                                    @if ($user->is_active)
                                        <button type="button" wire:click="deactivate({{ $user->id }})">
                                            <x-icon name="user-x" size="16" />{{ __('admin.users.action_deactivate') }}
                                        </button>
                                    @else
                                        <button type="button" wire:click="reactivate({{ $user->id }})">
                                            <x-icon name="user-check" size="16" />{{ __('admin.users.action_reactivate') }}
                                        </button>
                                    @endif
                                @endcan
                                @can('users.delete')
                                    <hr>
                                    <button type="button" class="is-danger"
                                            wire:click="remove({{ $user->id }})"
                                            wire:confirm="{{ __('admin.users.action_remove_confirm') }}">
                                        <x-icon name="trash-2" size="16" />{{ __('admin.users.action_remove') }}
                                    </button>
                                @endcan
                            </x-admin.menu>
                        @endif
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="users" :title="__('admin.users.empty_title')" :body="__('admin.users.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>
    </section>

    {{-- Add admin drawer --}}
    <x-admin.drawer show="add" :title="__('admin.users.drawer_add_title')">
        <x-slot:foot>
            <x-button variant="outline" x-on:click="add = false">{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="send" wire:click="addAdmin" x-show="inviteChecked">{{ __('admin.users.send_invite') }}</x-button>
            <x-button icon="check" wire:click="addAdmin" x-show="!inviteChecked" x-cloak>{{ __('admin.users.add') }}</x-button>
        </x-slot:foot>

        <x-input :label="__('admin.users.full_name')"
            :placeholder="__('admin.users.full_name_placeholder')"
            wire:model="name"
            :error="$errors->first('name')" />

        <x-input :label="__('admin.users.work_email')"
            type="email"
            placeholder="name@dots.eg"
            dir="ltr"
            wire:model="addEmail"
            :error="$errors->first('addEmail')" />

        <x-input :label="__('admin.users.role_field')"
            :value="__('admin.users.role_admin_full')"
            disabled
            readonly
            :help="__('admin.users.role_field_help')" />

        <x-checkbox :label="__('admin.users.invite_label')" wire:model="invite"
            x-model="inviteChecked" />

        <div x-show="!inviteChecked" x-cloak>
            <x-input :label="__('admin.users.temp_password')"
                type="password"
                dir="ltr"
                wire:model="tempPassword"
                :help="__('admin.users.temp_password_help')"
                :error="$errors->first('tempPassword')" />
        </div>
    </x-admin.drawer>
</div>
