# Prepare a release

Run from the repository root with PHP 8.0 or later and SimpleXML enabled:

```sh
php scripts/prepare-release.php 3.2.2
```

This updates the extension manifest version and release date, both module
registration versions, and the bridge's versioned dependencies on the main
module. The Molkobain dependency and historical migration threshold are unchanged.
The default date uses Europe/Berlin. Supply a date explicitly when needed:

```sh
php scripts/prepare-release.php 3.2.2 2026-11-06
```

The version must be newer than the existing version and use `X.Y.Z` format without
leading zeros. The current manifest, module, and bridge dependency versions must
match. All inputs and all three file contents are checked before writing. A write
failure triggers an attempt to restore all originals; this is not a transaction
across filesystem failures. Formatting outside the metadata elements and version
strings is preserved.

Set up the local schemas as described in [the test instructions](../tests/README.md)
before running the script. After updating metadata, it runs `tests/run.php`.
If tests fail, the script exits unsuccessfully and retains the edits for review.
Resolve failures before publishing.
The script does not stage, commit, push, tag, or publish anything.

Use a release branch named `release/<version>`, for example `release/3.2.2`.
Review the diff, commit on that branch, and open a PR into `main`. PR CI requires
the branch version to match the manifest, both modules, and the bridge dependencies.
CI runs PHP syntax checks, release-preparation tests, and XSD validation for
the manifest and both data models, alongside secret scanning. These checks do not establish runtime
behavior or successful installation in iTop.

Merging a `release/X.Y.Z` PR from this same repository automatically starts the
`Release` workflow. The merge is the publication approval: the workflow verifies
the successful `PR checks` result and publishes the merge commit as tag `vX.Y.Z`,
ZIP package, and GitHub Release. Closing an unmerged PR, merging another branch
prefix, or merging a fork's PR does not publish a release.

The ZIP contains a single top-level `br-location-extension/` folder. Inside it,
`extension.xml`, `LICENSE`, `br-location-extension/`, and
`br-location-extension-bridge-for-molkobain-datacenter-view/` retain their
project-relative structure. Extract into iTop's `extensions/` directory and run
setup. Select **Location enhancements**; the bridge is selected automatically
when Molkobain Datacenter View is also selected.

Each release includes `br-location-extension-vX.Y.Z.zip.sha256` alongside its ZIP.
Download both into the same directory and run:

```sh
sha256sum --check br-location-extension-vX.Y.Z.zip.sha256
```

On Windows, use `Get-FileHash .\br-location-extension-vX.Y.Z.zip -Algorithm SHA256`
and compare the result with the checksum file.
On recovery, already uploaded assets must match the regenerated files before
the workflow adds a missing asset or publishes an interrupted draft.

The `Release` workflow can also be started manually on `main` for recovery.
Retry the original run to retain its exact release commit if `main` has advanced.
Existing tags are reused only when they point to the same commit. Missing or
incomplete uploads and interrupted drafts can be completed by a repeat run.
