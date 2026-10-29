@php
    $groups = \Fayne\PostReleaseStatus\Services\ChangePresentation::groups($changes);
@endphp
<div class="prs-ui prs-changes">
    @if (! $changes['baseline_available'])
        <div class="prs-notice" role="status">
            <x-filament::icon icon="heroicon-o-information-circle" class="prs-icon" />
            <div><h3 class="prs-label">{{ __('post-release-status::ui.baseline_unavailable') }}</h3><p>{{ __('post-release-status::ui.baseline_description') }}</p></div>
        </div>
    @elseif (! array_filter($groups))
        <x-filament::section>
            <div class="prs-empty"><span class="prs-empty-icon"><x-filament::icon icon="heroicon-o-check-circle" class="prs-icon" /></span><h2 class="prs-title">{{ __('post-release-status::ui.no_changes') }}</h2><p class="prs-muted">{{ __('post-release-status::ui.empty_description') }}</p></div>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">{{ __('post-release-status::ui.changes') }}</x-slot>
            <x-slot name="description">{{ __('post-release-status::ui.changes_description') }}</x-slot>
            <div class="prs-groups">
                @foreach ($groups as $kind=>$items)
                    @if ($items)
                        <section class="prs-group" aria-label="{{ __('post-release-status::ui.'.$kind) }}">
                            <h3 class="prs-group-title"><span class="prs-kind-dot prs-tone-{{ $kind }}"></span>{{ __('post-release-status::ui.'.$kind) }}<span class="prs-count">{{ count($items) }}</span></h3>
                            <div class="prs-change-list">
                                @foreach ($items as $change)
                                    @php
                                        $item = $change['after'] ?? $change['before'];
                                        $fields = ($deferredDetails ?? false) ? [] : \Fayne\PostReleaseStatus\Services\ChangePresentation::fields($change);
                                        $type = \Fayne\PostReleaseStatus\Services\ChangePresentation::type($item);
                                        $isGlobal = $item['entity_type']==='global' || str_starts_with($item['entity_type'], 'global_');
                                        $pathBefore = $change['before']['url'] ?? $change['before']['path'] ?? $change['before']['slug'] ?? null;
                                        $pathAfter = $change['after']['url'] ?? $change['after']['path'] ?? $change['after']['slug'] ?? null;
                                    @endphp
                                    <details class="prs-change"
                                        @if ($deferredDetails ?? false)
                                            x-data="{fields: [], loaded: false, loading: false, error: '', stale: false, async load() { if (this.loaded || this.loading) return; this.loading = true; this.error = ''; this.stale = false; try { const response = await fetch(@js($change['details_url']), {headers: {'Accept': 'application/json'}, credentials: 'same-origin', cache: 'no-store'}); if (! response.ok) { this.stale = [401,403,409].includes(response.status); this.error = this.stale ? @js(__('post-release-status::ui.details_stale')) : @js(__('post-release-status::ui.details_failed')); return; } const data = await response.json(); this.fields = data.fields; this.loaded = true; } catch { this.error = @js(__('post-release-status::ui.details_failed')); } finally { this.loading = false; } } }"
                                            x-on:toggle="if ($el.open) load()"
                                        @endif
                                    >
                                        <summary class="prs-change-summary">
                                            <span class="prs-entry-icon"><x-filament::icon :icon="$isGlobal ? 'heroicon-o-adjustments-horizontal' : ($item['entity_type']==='menu' ? 'heroicon-o-bars-3' : 'heroicon-o-document-text')" class="prs-icon" /></span>
                                            <span class="prs-entry-copy">
                                                <span class="prs-entry-title">{{ \Fayne\PostReleaseStatus\Services\ChangePresentation::title($item) }}</span>
                                                <span class="prs-entry-meta">{{ $type }}@if (! empty($item['region']))<span>·</span>{{ $item['region'] }}@endif @if (! empty($item['locale']))<span>·</span>{{ \Fayne\CMS\Support\ContentLocale::supported()[$item['locale']] ?? $item['locale'] }}@endif</span>
                                                @if ($pathBefore || $pathAfter)<span class="prs-path">@if ($pathBefore && $pathAfter && $pathBefore!==$pathAfter)<span class="prs-old-path">{{ $pathBefore }}</span><span aria-hidden="true"> → </span>@endif{{ $pathAfter ?? $pathBefore }}</span>@endif
                                            </span>
                                            <span class="prs-entry-end">@if ($isGlobal)<x-filament::badge color="gray">{{ __('post-release-status::ui.global_badge') }}</x-filament::badge>@endif<span class="prs-detail-label">{{ __('post-release-status::ui.details') }}</span><x-filament::icon icon="heroicon-o-chevron-down" class="prs-chevron" /></span>
                                        </summary>
                                        <div class="prs-detail">
                                            @if ($deferredDetails ?? false)
                                                <p class="prs-muted" role="status" x-show="loading" x-cloak>{{ __('post-release-status::ui.details_loading') }}</p>
                                                <div role="alert" x-show="error" x-cloak><p class="prs-muted" x-text="error"></p><x-filament::button color="gray" size="sm" type="button" x-show="! stale" x-on:click="load()">{{ __('post-release-status::ui.retry') }}</x-filament::button><x-filament::button color="gray" size="sm" type="button" x-show="stale" x-on:click="window.location.reload()">{{ __('post-release-status::ui.refresh_list') }}</x-filament::button></div>
                                                <template x-if="loaded && fields.length">
                                                    <div class="prs-diff-scroll" tabindex="0" role="region" aria-label="{{ __('post-release-status::ui.details') }}">
                                                        <table class="prs-diff"><thead><tr><th>{{ __('post-release-status::ui.field') }}</th><th>{{ __('post-release-status::ui.before') }}</th><th>{{ __('post-release-status::ui.after') }}</th></tr></thead><tbody>
                                                            <template x-for="(field, index) in fields" :key="index"><tr><th scope="row" x-text="field.label"></th><td data-label="{{ __('post-release-status::ui.before') }}" data-side="before" x-text="field.before"></td><td data-label="{{ __('post-release-status::ui.after') }}" data-side="after" x-text="field.after"></td></tr></template>
                                                        </tbody></table>
                                                    </div>
                                                </template>
                                                <p class="prs-muted" x-show="loaded && !fields.length" x-cloak>{{ __('post-release-status::ui.no_field_details') }}</p>
                                            @elseif ($fields)
                                                <div class="prs-diff-scroll" tabindex="0" role="region" aria-label="{{ __('post-release-status::ui.details') }}">
                                                    <table class="prs-diff"><thead><tr><th>{{ __('post-release-status::ui.field') }}</th><th>{{ __('post-release-status::ui.before') }}</th><th>{{ __('post-release-status::ui.after') }}</th></tr></thead><tbody>
                                                        @foreach ($fields as $field)<tr><th scope="row">{{ $field['label'] }}</th><td data-label="{{ __('post-release-status::ui.before') }}" data-side="before">{{ $field['before'] }}</td><td data-label="{{ __('post-release-status::ui.after') }}" data-side="after">{{ $field['after'] }}</td></tr>@endforeach
                                                    </tbody></table>
                                                </div>
                                            @else
                                                <p class="prs-muted">{{ __('post-release-status::ui.no_field_details') }}</p>
                                            @endif
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
        </x-filament::section>
    @endif
</div>
