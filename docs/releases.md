# Packaging and releases

The Tests workflow runs PHP checks and creates an AppDrop ZIP on pull requests, pushes to `main`, and version tags. No Nextcloud server or JavaScript rebuild is required; the package uses the committed prebuilt JavaScript.

## Test a package

Open the successful workflow run and download the **appdrop-package** artifact. Extract the GitHub artifact download once to obtain `photo-gallery-VERSION.zip`. Upload that inner ZIP to AppDrop. It contains the required `photo_gallery/` root folder.

Artifacts are kept for seven days. They do not create or publish a release.

## Create a release

1. Set the version in `appinfo/info.xml` through a PR and merge it after checks pass. The current version is `0.8.7`.
2. Tag the intended merged commit as `v0.8.7` and push that tag. Do not publish a GitHub release manually first; CI creates the draft.
3. CI reruns the checks, confirms that the tag matches the app version, packages the ZIP and attaches it to a **draft release**.
4. Download the ZIP from the draft release and test it with AppDrop. Review the release notes, then publish the draft when ready.

For later releases, use the corresponding version and tag. CI does not deploy the app or publish the draft automatically. If a release already exists for the tag, creation fails rather than replacing its assets.

The existing **PHP checks** branch-protection check also covers packaging. Only the tag-triggered draft-release job has repository write permission.

## Local packaging

With Python 3 and Git installed, run `python3 scripts/package.py` from a checkout. The ZIP is written to `dist/` using the committed `HEAD`; uncommitted edits are not included.

The package includes runtime files, source modules, documentation and third-party sources/licence notices. Tests and CI configuration are excluded. The existing dependency archive is retained.
