<x-filament-panels::page>
    @php
        $progress = $record?->getProgress();
        $total = count($changes['added']) + count($changes['modified']) + count($changes['deleted']);
        $publicationErrors = array_unique([...$errors->all(), ...(session('errors')?->all() ?? [])]);
    @endphp
    <div class="prs-ui prs-stack">
        @if ($publicationErrors)
            <div role="alert" class="prs-notice prs-notice-danger">@foreach ($publicationErrors as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <x-filament::section>
            <div class="prs-hero-row">
                <div class="prs-hero-copy">
                    <span class="prs-hero-icon"><x-filament::icon icon="heroicon-o-cloud-arrow-up" class="prs-icon" /></span>
                    <div>
                        <div class="prs-title-line">
                            <h2 class="prs-title">{{ __('post-release-status::ui.overview') }}</h2>
                            <x-filament::badge :color="$locked || ! $changes['baseline_available'] ? 'warning' : ($total ? 'primary' : 'success')">{{ __($locked ? 'post-release-status::ui.publishing' : (! $changes['baseline_available'] ? 'post-release-status::ui.baseline_required' : ($total ? 'post-release-status::ui.ready' : 'post-release-status::ui.up_to_date'))) }}</x-filament::badge>
                        </div>
                        <p class="prs-muted">{{ __($locked ? 'post-release-status::ui.locked_description' : 'post-release-status::ui.ready_description') }}</p>
                        <p class="prs-recent">{{ __('post-release-status::ui.latest') }} · @if ($record){{ __('post-release-status::ui.release_number', ['number'=>$record->id]) }} · {{ $progress['status'] }} · {{ $record->created_at?->diffForHumans() }}@else{{ __('post-release-status::ui.no_release') }}@endif</p>
                    </div>
                </div>
                @if (! $locked)
                    @can('tenant.content.publish')@include('post-release-status::publish-form')@endcan
                @endif
            </div>
            @if ($wholeSite)
                <p class="prs-scope-note"><x-filament::icon icon="heroicon-o-globe-alt" class="prs-small-icon" />{{ __('post-release-status::ui.whole_site_hint') }}</p>
            @endif
            @if ($locked && $record)
                <div class="prs-progress" role="status" aria-live="polite" x-data="{progress: @js($progress['status'])}" x-init="const timer = setInterval(async () => { try { const response = await fetch(@js(route('filament-cms.publication.progress', ['deployment'=>$record->id])), {headers: {'Accept':'application/json'}}); if (!response.ok) { clearInterval(timer); return; } const data = await response.json(); progress = data.status; if ([19,20].includes(data.status_code)) { clearInterval(timer); window.location.reload(); } } catch { clearInterval(timer); } }, 3000)">
                    <span class="prs-progress-dot"></span><span x-text="progress">{{ $progress['status'] }}</span>
                </div>
            @endif
        </x-filament::section>
        <x-filament::section>@include('post-release-status::scope')</x-filament::section>
        <div class="prs-stats" aria-label="{{ __('post-release-status::ui.changes') }}">
            @foreach (['added'=>'plus', 'modified'=>'pencil-square', 'deleted'=>'minus', 'global'=>'globe-alt'] as $kind=>$icon)
                <x-filament::section class="prs-stat">
                    <div class="prs-stat-top"><span class="prs-muted">{{ __('post-release-status::ui.'.$kind) }}</span><span class="prs-stat-icon prs-tone-{{ $kind }}"><x-filament::icon :icon="'heroicon-o-'.$icon" class="prs-small-icon" /></span></div>
                    <p class="prs-stat-value">{{ $changes['baseline_available'] ? count($changes[$kind]) : '—' }}</p>
                    @if ($kind==='global')<p class="prs-stat-hint">{{ __('post-release-status::ui.global_hint') }}</p>@endif
                </x-filament::section>
            @endforeach
        </div>
        @if ($changes['baseline_available'] && ! empty($changes['unavailable_scopes']))
            <p class="prs-notice" role="status">{{ __('post-release-status::ui.partial_baseline', ['locales'=>implode('、', $changes['unavailable_scopes'])]) }}</p>
        @endif
        @include('post-release-status::changes')
    </div>
</x-filament-panels::page>
