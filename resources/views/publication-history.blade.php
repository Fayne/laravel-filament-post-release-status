<x-filament-panels::page>
<div class="prs-ui prs-stack">
@include('post-release-status::scope')
@foreach ($records as $manifest)
@include('post-release-status::history-record')
@endforeach
@if ($detail)<h2>{{ $detail->release_id }} 的冻结变更</h2>@include('post-release-status::changes')@endif
</div>
</x-filament-panels::page>
