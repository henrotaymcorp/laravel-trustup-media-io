# laravel-trustup-media-io

## 2.1.1

### Patch Changes

- 10b97ce: Require laravel-trustup-media-io-common ^2.0.2, which fixes base64 detection crashing on raw content containing ';base64,'.

## 2.1.0

### Minor Changes

- 4432075: Add MediaEndpoint::search (POST payload lookup) and use it to load external media relations, preventing HTTP 414 failures that silently dropped media on large uuid batches. Requires trustup-io-media with the POST /media/search route.

## 2.0.1

### Patch Changes

- 84e5ffb: Add changeset release infrastructure + bun

## 1.4.0

### Minor Changes

- 91e53fb: Allow to create files with explicit naming easily.
