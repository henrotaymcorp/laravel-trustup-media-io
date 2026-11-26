# Product Spec : Media uploads survive raw files that contain ";base64,"

Ticket : TDD-7784, branch `tdd-7784`

## Problem Statement

Applications that use this media package to attach files to their records hand it either a link, a base64 text (with or without a "data:" prefix) or the raw bytes of the file. Before sending, the media package decides which of the three it received.

Today, a raw file whose bytes happen to contain the text ";base64," somewhere after an earlier ";" makes that decision crash: the upload never happens and the whole operation that triggered it fails. This is not theoretical: two received e-invoices from one supplier (each with 5 copies of the same PDF, each PDF containing ";base64," once) fail every night when the expense is created from them, for two different tenants. The failure only shows up in the failed background work, not in error monitoring.

The cause sits in the shared media-common package, which this package relies on for the decision. Version 2.0.2 of media-common decides correctly. This package still accepts every 2.0.x version, so any application can end up with a broken one and see this crash.

The fix is to have this package require media-common 2.0.2 or later. Leaving it alone means every application that relies on this package, and not only the one that reported it, keeps losing these uploads silently.

## Actors

-   Consuming application : an application that attaches files to its records through this package (for example, the worksite application creating an expense, with its attachments, from a received e-invoice)

## Acceptance Criteria

### AC-1 : A raw file containing ";base64," is uploaded as raw content

-   **Who** : Consuming application
-   **Given** : a file whose raw bytes contain the text ";base64," after an earlier ";", and which is neither a link nor valid base64 text
-   **When** : the consuming application attaches it to a record through this package
-   **Then** : the system shall send the file as raw content, without error, and the attachment shall be stored

### AC-2 : Base64 and data-URI files keep being recognised

-   **Who** : Consuming application
-   **Given** : a file provided as valid base64 text, either bare or prefixed with a "data:<type>;base64," header
-   **When** : the consuming application attaches it to a record through this package
-   **Then** : the system shall send it as base64 content, as it did before this change

### AC-3 : Applications can no longer end up with a media-common version that crashes

-   **Who** : Consuming application
-   **Given** : an application installing or updating this package
-   **When** : its dependencies are resolved
-   **Then** : the system shall only accept media-common 2.0.2 or a later 2.x version
-   **Otherwise** : if the application pins media-common to an earlier version, then the system shall refuse to install this release rather than run with the crashing version

## Edge Cases

-   A text that only looks like a data URI (contains ";base64," but its part after it is not valid base64) : sent as raw content, never trusted as base64 (no looser check than before)
-   An empty file after the "data:...;base64," header : not treated as base64

## Out of Scope

-   Changing how media-common decides between link, base64 and raw content: that work shipped in media-common 2.0.2 under the same ticket
-   Updating the worksite application to pick up the new release of this package: tracked separately on the same ticket, after this release is published
-   Replaying the two failed e-invoice receivings: they succeed on their own on the next nightly run once the worksite application is updated
-   The unrelated static-analysis and formatting findings already present on the main branch
-   The worksite application's own base64 check (TDD-5946)
