# Slice 1: Consuming applications get the fixed media-common through this package

## Covered ACs
- **AC-1**: a raw file containing ";base64," after an earlier ";" is sent as raw content, without error
- **AC-2**: bare base64 and "data:<type>;base64," files are still sent as base64
- **AC-3**: only media-common 2.0.2 or a later 2.x version is accepted when installing this release

## Dependencies
None.

## Objective
This package delegates "link, base64 or raw content" to `henrotaym/laravel-trustup-media-io-common`'s `StorableMediaTransformer`. Up to v2.0.1 its `isBase64()` split the resource on ";" and "," whenever it contained ";base64,", which crashed with "Undefined array key 1" on raw binary (a PDF) containing that text. v2.0.2 fixes it. This slice raises the requirement to `^2.0.2`, re-locks to v2.0.2 and adds a patch changeset so the release workflow publishes v2.1.1.

Status: implemented in commit 10b97ce.

## Files
- `composer.json` (modified): require `"henrotaym/laravel-trustup-media-io-common": "^2.0.2"` (was `"^2.0"`)
- `composer.lock` (modified): `composer update henrotaym/laravel-trustup-media-io-common`; only that entry (v2.0.0 to v2.0.2) and `content-hash` may change
- `.changeset/tdd-7784.md` (new): `"@henrotaym/laravel-trustup-media-io": patch`, one-line description of the requirement change
- `src/Models/Traits/HasTrustupMedia.php` (read only): `addTrustupMediaFromAttributesCollection()` passes every attribute set through `StorableMediaTransformerContract::fromArray()`
- `vendor/henrotaym/laravel-trustup-media-io-common/src/Transformers/Models/StorableMediaTransformer.php` (read only): v2.0.2 `isBase64String()`

## Expected tests
No new test in this repo: the detection logic lives in media-common, which carries the tests for these scenarios under the same ticket. Verify against the locked v2.0.2 instead:
- Nominal (AC-1): given raw content `"%PDF-1.4;x\n...;base64,..."` (neither link nor valid base64), when `fromResource()` runs, then the media is marked as raw string, no exception
- AC-2: given bare valid base64, or `"data:application/pdf;base64,<valid payload>"`, when `fromResource()` runs, then the media is marked as base64
- AC-3: given `composer.json`, when resolving, then `^2.0.2` excludes 2.0.0 and 2.0.1 (`composer show henrotaym/laravel-trustup-media-io-common` reports v2.0.2)
- Edge: ";base64," followed by an invalid payload is raw content; an empty payload after the header is not base64

## Verification
- [ ] `./cli test`: all tests pass
- [ ] `./cli analyse`: no new errors (main already reports 4 in `src/Endpoints/MediaEndpoint.php` and `src/Models/Traits/HasTrustupMedia.php`, unrelated)
- [ ] `./cli composer validate --no-check-publish`: valid
- [ ] `git diff origin/main -- composer.lock` touches only the media-common entry and `content-hash`
