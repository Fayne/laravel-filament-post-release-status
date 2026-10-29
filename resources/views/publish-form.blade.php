<form method="post" action="{{ route('post-release-status.start', ['publicationTenant'=>$tenantId]) }}" class="prs-publish-form">@csrf
    <input type="hidden" name="panel" value="{{ \Filament\Facades\Filament::getCurrentPanel()->getId() }}">
    <input type="hidden" name="locale" value="{{ $context['locale'] }}">
    <input type="hidden" name="region" value="{{ $context['region'] }}">
    <x-filament::button type="submit" icon="heroicon-o-cloud-arrow-up">{{ __($changes['baseline_available'] ? 'post-release-status::ui.start' : 'post-release-status::ui.rebuild') }}</x-filament::button>
    @if (! ($wholeSite ?? false) && ($filterLocale ?? null)==='' && \Fayne\CMS\Support\ContentLocale::enabled())
        <p class="prs-recent">{{ __('post-release-status::ui.publish_language', ['locale'=>\Fayne\CMS\Support\ContentLocale::supported()[$context['locale']] ?? $context['locale']]) }}</p>
    @endif
</form>
