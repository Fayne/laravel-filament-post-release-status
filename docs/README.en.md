# Publication status and history

Register `Fayne\PostReleaseStatus\PostReleaseStatusPlugin::make()` in a tenant panel or a single-site admin panel. Viewing status, viewing history and publishing require `tenant.publication.view`, `tenant.publication.history` and `tenant.content.publish` respectively. Core supplies the persistent tenant write barrier and frozen release data.

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
