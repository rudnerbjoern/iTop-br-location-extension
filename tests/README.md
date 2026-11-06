# PHP tests

Run from the repository root with PHP 8.0 or later, SimpleXML, and DOM enabled.
First download the two standalone schemas used by the project's XML files:

```sh
mkdir -p .tmp/schemas/3.2
curl --fail --location https://rudnerbjoern.github.io/iTop-schema/extension.xsd -o .tmp/schemas/extension.xsd
curl --fail --location https://rudnerbjoern.github.io/iTop-schema/3.2/itop_design.xsd -o .tmp/schemas/3.2/itop_design.xsd
```

PowerShell alternative:

```powershell
New-Item -ItemType Directory -Force .tmp/schemas/3.2 | Out-Null
Invoke-WebRequest https://rudnerbjoern.github.io/iTop-schema/extension.xsd -OutFile .tmp/schemas/extension.xsd
Invoke-WebRequest https://rudnerbjoern.github.io/iTop-schema/3.2/itop_design.xsd -OutFile .tmp/schemas/3.2/itop_design.xsd
```

Alternatively, set `ITOP_SCHEMA_DIR` to an existing iTop-schema checkout containing
`extension.xsd` and `3.2/itop_design.xsd`. Then run:

```sh
php tests/run.php
```

On Windows, use the same command with `php.exe` if needed.
No Composer packages, database, or iTop installation are required.
The runner prints each result and exits with `0` on success or `1` on failure.

## CI

On pushes and pull requests targeting `main`, the central `CI` workflow sets up
PHP 8.3 with DOM and SimpleXML, downloads both schemas into `.tmp/schemas`, checks
PHP syntax, and runs `php tests/run.php`. All module, schema, and release-preparation
tests must succeed for the `PR checks` job to pass; a schema download failure fails
the job.
For `release/*` PRs, CI also validates the `release/X.Y.Z` branch name against
the manifest, both module versions, and bridge dependencies.

## Schema and release-preparation tests

The release-preparation tests cover synchronized manifest, module, and bridge
versions, preservation of unrelated content, and rejection of invalid versions,
dates, mismatched registrations, and bridge dependencies. They operate on strings
and do not write release metadata to the working tree.

Three schema checks validate the manifest and both data models against the local
XSDs. Missing schemas fail with setup instructions. Tests run offline after setup;
refresh the cached schemas explicitly when needed. Results apply to the schema
versions present locally.

## Module tests

`tests/modules.php` returns its test cases to the shared runner;
`php tests/run.php` executes all suites and reports their combined result.

The tests extract and execute the six real PHP methods from the Location XML.
Small substitutes provide attribute access, parent lookup, child collections,
attribute flags, and the post-write callback. Cases cover root and nested paths,
creation, renaming, reparenting, removing a parent, descendant and sibling updates,
unrelated edits, and hidden/read-only flags. Bridge checks verify removal of the
alternate location type from the model, views, and typology dashboard.

The substitutes do not validate iTop event dispatch, database persistence, OQL
parent filtering, setup, or coexistence with an installed Molkobain module.
Only run against trusted project code: the extracted methods execute using `eval`.
