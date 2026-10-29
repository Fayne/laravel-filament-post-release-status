<form method="get" class="prs-filters" aria-label="{{ __('post-release-status::ui.viewing_scope') }}">
    <div class="prs-filter-intro">
        <p class="prs-label">{{ __('post-release-status::ui.viewing_scope') }}</p>
        <p class="prs-muted">{{ __('post-release-status::ui.scope_hint') }}</p>
    </div>
    <div class="prs-filter-control">
        <label for="publication-locale" class="prs-label">{{ __('post-release-status::ui.locale') }}</label>
        <x-filament::input.wrapper>
            <x-filament::input.select id="publication-locale" name="locale">
                @if (isset($filterLocale))
                    <option value="" @selected($filterLocale==='')>{{ __('post-release-status::ui.all_languages') }}</option>
                @endif
                @foreach ((\Fayne\CMS\Support\ContentLocale::enabled() ? \Fayne\CMS\Support\ContentLocale::supported() : [\Fayne\CMS\Support\ContentLocale::default()=>\Fayne\CMS\Support\ContentLocale::default()]) as $key=>$label)
                    <option value="{{ $key }}" @selected(($filterLocale ?? $context['locale'])===$key)>{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
    <div class="prs-filter-control">
        <label for="publication-region" class="prs-label">{{ __('post-release-status::ui.region') }}</label>
        <x-filament::input.wrapper>
            <x-filament::input.select id="publication-region" name="region">
                @if (config('filament-cms.regions', []) === [])
                    <option value="">{{ __('post-release-status::ui.default_region') }}</option>
                @endif
                @foreach (\Fayne\CMS\Support\PublicationRegion::options() as $region)
                    <option value="{{ $region['id'] }}" @selected($context['region']===(string)$region['id'])>{{ $region['name'] ?? $region['id'] }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
    <x-filament::button type="submit" color="gray" icon="heroicon-o-funnel">{{ __('post-release-status::ui.apply') }}</x-filament::button>
</form>
