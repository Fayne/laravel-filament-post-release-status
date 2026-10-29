<?php

namespace Fayne\PostReleaseStatus\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class ChangePresentation
{
    public static function groups(array $changes): array
    {
        $groups = Arr::only($changes, ['added', 'modified', 'deleted']);
        $identity = fn ($change) => Arr::only($change['after'] ?? $change['before'], ['scope_id', 'entity_type', 'entity_id']);
        $shown = array_map($identity, array_merge(...array_values($groups)));
        $groups['global'] = array_values(array_filter($changes['global'], fn ($change) => ! in_array($identity($change), $shown, true)));

        return $groups;
    }

    public static function type(array $row): string
    {
        $key = 'post-release-status::ui.types.'.($row['entity_type'] ?? '');

        return __($key) === $key ? Str::headline($row['entity_type'] ?? '') : __($key);
    }

    public static function title(array $row): string
    {
        return (string) ($row['name'] ?? $row['title'] ?? data_get($row, 'projection.name') ?? data_get($row, 'projection.title') ?? data_get($row, 'menu.name') ?? self::type($row));
    }

    public static function fields(array $change): array
    {
        $project = function (?array $row): array {
            if ($row === null) {
                return [];
            }
            $data = $row['comparison_projection'] ?? $row['projection'] ?? [];
            foreach (['path', 'url', 'slug'] as $key) {
                if (isset($row[$key]) && ! array_key_exists($key, $data)) {
                    $data[$key] = $row[$key];
                }
            }

            return Arr::dot($data);
        };
        $before = $project($change['before']);
        $after = $project($change['after']);
        $fields = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $path) {
            $had = array_key_exists($path, $before);
            $has = array_key_exists($path, $after);
            if ($had && $has && $before[$path] === $after[$path]) {
                continue;
            }
            $parts = explode('.', $path);
            if ($parts[0] === 'values') {
                array_shift($parts);
            }
            $label = implode(' › ', array_map(function ($part) {
                if (ctype_digit((string) $part)) {
                    return __('post-release-status::ui.item_number', ['number' => (int) $part + 1]);
                }
                $key = 'post-release-status::ui.fields.'.$part;

                return __($key) === $key ? Str::headline($part) : __($key);
            }, $parts));
            $fields[] = ['label' => $label, 'before' => $had ? self::value($before[$path]) : '—', 'after' => $has ? self::value($after[$path]) : '—'];
        }

        return $fields;
    }

    private static function value(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => __('post-release-status::ui.unset'),
            is_bool($value) => __('post-release-status::ui.'.($value ? 'yes' : 'no')),
            is_array($value) => __('post-release-status::ui.empty_list'),
            default => (string) $value,
        };
    }
}
