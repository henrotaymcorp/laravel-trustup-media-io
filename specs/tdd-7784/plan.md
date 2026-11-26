# Plan : Media uploads survive raw files that contain ";base64,"

## Summary

Raise this package's floor on `henrotaym/laravel-trustup-media-io-common` from `^2.0` to `^2.0.2`, re-lock it to v2.0.2 and ship a patch release (v2.1.1) through the existing changeset flow. The defective base64 detection lives in media-common and was fixed there (v2.0.2, same ticket); this package only has to stop accepting the broken versions.

Written after the fact: the change was implemented in commit 10b97ce before this plan existed. The plan records it in the workflow format; it does not drive new work.

## Approach

No approaches step: the design was settled in the ticket (the lead asked to raise the floor here so consumers can no longer resolve 2.0.0 or 2.0.1). Changing this package's code was never on the table, since it delegates the decision entirely to media-common.

## Existing code reused

-   `src/Models/Traits/HasTrustupMedia.php`: `addTrustupMediaFromAttributesCollection()` resolves `StorableMediaTransformerContract` from the container and passes every attribute set through `fromArray()`; unchanged, it picks up the fixed transformer through the dependency.
-   `vendor/henrotaym/laravel-trustup-media-io-common/src/Transformers/Models/StorableMediaTransformer.php` (v2.0.2): `isBase64String()` holds the corrected detection; unchanged here.
-   `.github/workflows/github-release.yml`: on merge to main runs `bun changeset version`; when a changeset bumped `package.json`, commits, tags `vX.Y.Z` and creates the GitHub release that Packagist picks up.

## File structure

-   `composer.json` (modified): require `"henrotaym/laravel-trustup-media-io-common": "^2.0.2"`
-   `composer.lock` (modified): `henrotaym/laravel-trustup-media-io-common` v2.0.0 to v2.0.2 (reference `5cf05790fe443075671d974d000f61ce3a5783f9`) plus `content-hash`; no other entry changes
-   `.changeset/tdd-7784.md` (new): changeset, patch bump of `@henrotaym/laravel-trustup-media-io`

## Sketch

Flow from trigger to effect:

1. A consuming application calls `addTrustupMediaFromAttributes()` / `addTrustupMediaFromResourceCollection()` on a model using `HasTrustupMedia`; both funnel into `addTrustupMediaFromAttributesCollection()`.
2. Each attribute set goes through `StorableMediaTransformer::fromArray()` then `fromResource()`, which tries stream, file, base64, url, and falls back to raw string.
3. `isBase64()` delegates to `isBase64String()`: only an anchored, well-formed `data:<type>/<subtype>;base64,` prefix is stripped, then strict decode plus identical re-encode. Raw bytes containing ";base64," mid-content fail the round trip and fall through to `setAsString()` (AC-1); bare base64 and valid data URIs return true and go to `setAsBase64()` (AC-2).
4. Composer resolves `^2.0.2`, i.e. `>=2.0.2 <3.0.0`, so 2.0.0 and 2.0.1 can no longer be installed alongside this release (AC-3).

```php
// src/Models/Traits/HasTrustupMedia.php (unchanged)
public function addTrustupMediaFromAttributesCollection(
    iterable $attributesCollection,
    bool $isUsingQueue = false
) {
    $transformer = app()->make(StorableMediaTransformerContract::class);
    $request = app()->make(StoreMediaRequestContract::class);

    foreach ($attributesCollection as $attributes) {
        $request->addMedia($transformer->fromArray($attributes));
    }

    $request->useQueue($isUsingQueue);

    return $this->addTrustupMedia($request);
}

// media-common v2.0.2 StorableMediaTransformer (vendor, unchanged here)
public static function isBase64String(string $value): bool
{
    $content = preg_replace('~^data:[a-zA-Z0-9.+-]+/[a-zA-Z0-9.+-]+;base64,~', '', $value) ?? '';

    if ($content === '') {
        return false;
    }

    $binary = base64_decode($content, true);

    return $binary !== false
        && base64_encode($binary) === $content;
}
```

```json
// composer.json
"henrotaym/laravel-trustup-media-io-common": "^2.0.2",
```

```markdown
<!-- .changeset/tdd-7784.md -->
---
"@henrotaym/laravel-trustup-media-io": patch
---

Require laravel-trustup-media-io-common ^2.0.2, which fixes base64 detection crashing on raw content containing ';base64,'.
```

## Slices overview

| Slice | Use case | ACs | Depends on |
| ----- | -------- | --- | ---------- |
| slice-01 | Consuming applications get the fixed media-common through this package | AC-1, AC-2, AC-3 | None |

## Coverage matrix

| AC   | Slice(s) |
| ---- | -------- |
| AC-1 | slice-01 |
| AC-2 | slice-01 |
| AC-3 | slice-01 |

## Architectural decisions

| Decision | Alternative rejected | Reason |
| -------- | -------------------- | ------ |
| Raise the floor to `^2.0.2` instead of leaving `^2.0` | Keep `^2.0` and rely on consumers running `composer update` | Lead's decision: `^2.0` still lets a consumer resolve 2.0.0 or 2.0.1 and crash; the floor removes that possibility |
| No test for AC-1/AC-2 in this repo | Duplicate media-common's detection tests here | The detection lives in media-common and is tested there under the same ticket; this repo has no base64 logic and no PHP change |
| Pure dependency slice (no observable PHP change in this repo) | None | The behavior change arrives through the dependency; recorded explicitly since a pure infrastructure slice needs agreement, given by the user on 2026-11-26 |
