# Packaging and releases

The Tests workflow runs PHP checks and creates an AppDrop ZIP on pull requests, pushes to `main`, version tags and manual runs. No Nextcloud server or JavaScript rebuild is required; the package uses the committed prebuilt JavaScript.

## Test a package

Open the successful workflow run and download the **appdrop-package** artifact. Extract the GitHub artifact download once to obtain `photo-gallery-VERSION.zip`. Upload that inner ZIP to AppDrop. It contains the required `photo_gallery/` root folder.

Artifacts are kept for seven days. Pull requests and ordinary pushes to `main` do not create a release.

## Create a release in the GitHub website

1. Set the version in `appinfo/info.xml` through a PR and merge it after checks pass. The current version is `0.8.7`.
2. Open **Actions → Tests → Run workflow**, select **main**, and click **Run workflow**.
3. CI runs the checks and packages the ZIP. It creates the matching version tag (for example `v0.8.7`) at the exact commit tested, then creates a **draft release** with the ZIP attached.
4. Open **Releases** and the draft. Download the attached ZIP and test it with AppDrop.
5. Edit the draft, review the release notes and click **Publish release** when ready.

Manual release runs are restricted to `main`. An existing tag is reused only if it points directly to the exact commit being tested; it is never moved. Existing releases are not overwritten. If a run fails after creating the tag but before creating a release, rerun its failed jobs from that same run.

CI does not deploy the app or publish the draft automatically.

## Alternatively: push a version tag

Pushing a tag such as `v0.8.7` also runs the checks and creates the draft release. The tag must match `appinfo/info.xml`. Do not create the release manually first; CI creates it.

The existing **PHP checks** branch-protection check also covers packaging. Only the draft-release job has repository write permission.

## Local packaging

With Python 3 and Git installed, run `python3 scripts/package.py` from a checkout. The ZIP is written to `dist/` using the committed `HEAD`; uncommitted edits are not included.

The package includes runtime files, source modules, documentation and third-party sources/licence notices. Tests and CI configuration are excluded. The existing dependency archive is retained.
