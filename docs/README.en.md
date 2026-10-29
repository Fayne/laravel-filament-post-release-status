<p align="center"><img src="https://res.cloudinary.com/fayne/image/upload/v1787643269/fayne_logo.png" width="300"></p>

# Laravel Filament Post Release Status

[简体中文](../README.md) | English

Publication status and immutable history for **PHP 8.4+, Laravel 13 and Filament 5**. CMS Core owns publication locks, lifecycle and frozen snapshots. The package supports single-site admin panels and tenant workspaces; Page and Menu are optional.

## Features and boundaries

| Capability | Behavior |
| --- | --- |
| Pending content | Added, modified and deleted page content, menu trees/settings and site settings |
| Content languages | All enabled languages in the selected region by default, with an optional language filter |
| On-demand details | Initial names and paths; field differences are fetched when an item is opened |
| Whole-site publication | Core routing publishes every region and language together, with domain or URL-path region routing |
| Write protection | Core blocks CMS writes for the publishing tenant; progress uses GET |
| History | Saved release snapshots and outcomes; only successful publication advances the baseline |
| Interface | Native Filament controls, English/Chinese, dark mode and narrow-screen layouts |

This package provides the administration interface. Frontend builds, deployment and scheduling belong to the host or publisher. Component, widget and field structure definitions are hidden from pending content but retained in frozen snapshots and historical audits.

## 1. Installation

Configure the host, authentication and private package repositories using the [CMS Core guide](../../laravel-filament-cms-core/docs/README.en.md), then run:

```bash
composer require fayne/laravel-filament-post-release-status
php artisan migrate
php artisan cms:init
php artisan filament:assets
```

Configure repositories for private packages and dependencies in the host's root Composer manifest. Dependency repositories are not inherited. This package has no separate tables or configuration file: Core owns publication data and configuration. Run Page migrations on regional content databases as documented by Core/Page.

`cms:init` synchronizes the permission catalog while preserving custom role grants. Administrators must grant new permissions to existing roles as appropriate; installing the package does not grant publishing access. Core's initial schema targets new databases; existing projects need their own data upgrade plan.

## 2. Panel registration and permissions

Append the plugin to the existing CMS PanelProvider, preserving its other plugins, authentication and middleware:

```php
use Fayne\PostReleaseStatus\PostReleaseStatusPlugin;

// Append to the existing ->plugins([...]) list:
PostReleaseStatusPlugin::make(),
```

Use a single-site admin panel or tenant workspace with tenant context, not a context-free platform panel. Follow Core configuration for tenancy, the admin guard and publication write protection.

| Permission | Purpose |
| --- | --- |
| `tenant.publication.view` | Status and on-demand change details |
| `tenant.publication.history` | Publication history |
| `tenant.content.publish` | Start publication |

Platform changes to tenant branding, languages and domains also respect the target tenant's publication lock.

A non-null effective Core `publication_routing` enables whole-site v2: every configured region and enabled language is published together. Locale/region filters only change the displayed list. Comparison identifies rows by scope, entity type and ID; routing changes affect the entire site. History uses saved routing and baselines, and old v1 history remains accessible. A successful v1 release without a v2 baseline requires a full baseline publication.

Core verifies signed builder capabilities before any publication record or persistent lock. Unsupported or unavailable builders produce a visible validation notice. V2 requests carry exact frozen payload bytes, website/release/attempt/generation/digest and all targets; ACK and completion evidence are verified. Only confirmed activation of every target permits success. Uncertain acceptance, mixed activation or unavailable results retain the lock with an actionable pending-confirmation message. Progress is read through authenticated GET requests.

Frozen release APIs require the build Bearer token and the owning tenant domain. Page enumeration supports target/scope filters; ambiguous paths, multi-scope source paths, menus and settings require an explicit scope. Responses use saved public data and URLs, never raw promotion snapshots or connection identifiers. See Core's README for the v2 signing and completion contract.

V2 recovery uses `cms:publication-recover <deployment> --operator=... --reason=...`: signed status first, then reconcile confirmed activation or require persistent signed revocation before unlock. Unknown or unreachable publisher state never unlocks automatically. V1 recovery additionally requires `--external-stopped`. There is no ordinary tenant cancel action.

Package tests use isolated SQLite databases; MySQL concurrency, browser behavior, actual Astro builds, callbacks and production deployment require separate acceptance. No production database migration or deployment is implied by package test results.

## Publication status interface

The page uses native Filament controls, a publication overview, change counts, locale/region filters and collapsed field comparisons. Site-wide impact is a subset of changes and is not listed twice. Details omit raw promotion snapshots and connection metadata. The layout supports dark mode and narrow screens; viewing and publishing retain separate permissions.

Run `php artisan filament:assets` after installing or updating the package to publish its stylesheet. No additional Tailwind source scanning or theme rebuild is required.

Pending lists and added/modified/deleted/site-wide counts show content changes, excluding page and menu component, widget and field structure definitions. Page content, menu trees and settings, and site settings remain visible. Definitions remain in frozen publication snapshots and historical audits; this is a status-page presentation rule only.

## Language overview and on-demand details

Status defaults to all enabled languages within the selected region. An explicit language only filters the view. Legacy publications compare each language against its own baseline and show the actual publishing language beside the action; aggregation does not silently change publication scope. Whole-site mode still publishes all regions and languages together. Missing baselines are reported without hiding other comparable languages.

The initial response contains names, paths, languages and change types. Opening an item retrieves only its field differences through an authenticated, short-lived signed GET request. Tenant membership, viewing permission and account identity are checked again. Expired references or changed content require refreshing the list. Responses omit internal promotion data and connections, and are not cached. History continues to use frozen data.

This defers field-difference formatting and detailed HTML generation/transmission. Detecting changed content still requires snapshot reads and comparison; this is not a claim that all initial comparison work has been removed.

## Build integration

Configure Core's `CMS_FRONTEND_DRIVER=astro`, `CMS_BUILD_WEBHOOK_URL`, `CMS_DEPLOYMENT_WEBHOOK_SECRET` and `filament-cms.deployment.build_api_token`. Fetch the manifest, pages, menus and global settings from `/headless/releases/{release_id}/…` using the build Bearer token and an enabled tenant domain. Never mix frozen release data with live content.

Build webhooks retain `X-Nova-CMS-Timestamp` and `X-Nova-CMS-Signature`; the HMAC input is `timestamp.body`. Completion is posted to `/headless/releases/callback`. Follow the [Core protocol](../../laravel-filament-cms-core/docs/README.en.md) for the full v1/v2 request, signing, acknowledgement and callback contracts.

## Development and verification

```bash
composer install
composer test
composer format
```

Tests use isolated in-memory SQLite. Formatting checks do not modify files; use `composer format:fix` to apply formatting. Exclude `composer.lock`, dependencies and test caches from package source.

## License

This is proprietary software. Use and distribution require authorization; see [LICENSE.md](../LICENSE.md).
