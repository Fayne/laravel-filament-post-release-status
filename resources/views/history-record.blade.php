<article><a href="{{ request()->fullUrlWithQuery(['release'=>$manifest->id]) }}">{{ $manifest->release_id }}</a><p>状态：{{ ['succeeded'=>'成功','failed'=>'失败','recovered'=>'人工恢复','frozen'=>'进行中'][$manifest->status] ?? $manifest->status }} · 操作人：{{ $manifest->deploymentRecord?->admin_id ?? '—' }} · {{ $manifest->created_at }}</p><pre>{{ json_encode($manifest->deploymentRecord?->deployment_contents['failure'] ?? null, JSON_UNESCAPED_UNICODE) }}</pre>@php($baseline = $manifest->baseline_manifest_id ? \Fayne\CMS\Models\ReleaseManifest::query()->where('tenant_id', $manifest->tenant_id)->where('scope_key', $manifest->scope_key)->where('status', 'succeeded')->find($manifest->baseline_manifest_id) : null)
<p>基线版本：{{ $baseline?->release_id ?? (($manifest->context['baseline_unavailable'] ?? false) || $manifest->baseline_manifest_id ? '不可用' : '首次发布') }}</p>
@if ($baseline)<p>基线摘要：{{ $baseline->digest }}</p>@endif
<p>清单摘要：{{ $manifest->digest }}</p>
@php($recovery = $manifest->deploymentRecord?->deployment_contents['recovery'] ?? null)
@if ($recovery)<p>恢复操作人：{{ $recovery['operator'] ?? '—' }} · 恢复时间：{{ $recovery['at'] ?? '—' }} · 原因：{{ $recovery['reason'] ?? '—' }}</p>@endif
</article>
