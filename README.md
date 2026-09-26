# Photo Gallery

[![Tests](https://github.com/ikigaido-org/nextcloud-photo-gallery/actions/workflows/tests.yml/badge.svg?branch=main&event=push)](https://github.com/ikigaido-org/nextcloud-photo-gallery/actions/workflows/tests.yml)
[![Latest release](https://img.shields.io/github/v/release/ikigaido-org/nextcloud-photo-gallery)](https://github.com/ikigaido-org/nextcloud-photo-gallery/releases/latest)
[![License: AGPL-3.0-only](https://img.shields.io/badge/license-AGPL--3.0--only-blue)](LICENSE)
[![Non-profit: volunteer maintained](https://img.shields.io/badge/non--profit-volunteer%20maintained-blue)](#project-scope-and-contributions)

A public photo album overview for Nextcloud, built on Nextcloud Photos.

Photo Gallery brings publicly shared Photos albums together on one page, with year and location filters and individual album pages for browsing photos and videos.

**[See it in use: Ikigaido’s public gallery](https://nx.ikigaido.ch/apps/photo_gallery/)**

This is our association’s production gallery. It may run a different version from the code on `main`.

## Why this app exists

We built Photo Gallery for [Ikigaido](https://ikigaido.ch), our martial arts association. We needed a public overview of our event albums and a practical way for several people to maintain them.

After exploring options in the Nextcloud ecosystem, we chose to build directly on Photos. Photos remains responsible for albums and sharing; Photo Gallery adds the public overview, its own album viewer and tools for our editorial workflow. Memories is not required.

## Features

- Public album overview with covers, year and location filters, and configurable sorting.
- Responsive album pages with a [lightGallery](https://www.lightgalleryjs.com/) photo and video viewer and selectable information fields.
- Create a Photos album from a folder through the Files menu, with optional subfolders.
- Optional switching from a personal account to a shared gallery account.
- Configurable titles, colours, backgrounds, footer and heading font.
- RSS and Atom feeds, link preview metadata and an `?embed=1` view that hides the header and visible page heading.

Video playback depends on browser support for the original format; the app deliberately does not transcode videos.

## Requirements and setup

The current app targets **Nextcloud 33** and **PHP 8.2 or newer**. Enable the Nextcloud **Photos** app and public album sharing. The interface currently uses German labels.

1. Install Photo Gallery using AppDrop with an app ZIP containing a top-level `photo_gallery/` folder. When packaging this repository, put the app files directly inside that folder. Prebuilt JavaScript is included.
2. In Nextcloud’s administration settings, open **Public gallery** (German UI: **Öffentliche Galerie**).
3. Enter the album owners’ account IDs, configure the title and appearance, and enable **Publish the overview** (**Übersicht öffentlich aktivieren**). An empty owner list includes public albums from all active accounts.
4. Create albums and share them publicly through Photos. The overview is available at `/apps/photo_gallery/`.

The overview makes the selected accounts’ existing public albums discoverable together. Private albums are excluded. Creating an album from a folder does not automatically publish it or keep its membership synchronized with later folder changes.

For an existing installation, update the same `photo_gallery` app without uninstalling it.

## Working together

Photos’ album ownership and collaboration model did not meet our need for a group of editors to maintain the same public gallery. The shared gallery account is a workaround for that limitation: it provides a common owner for the albums, while the account switcher lets selected people work as that owner from their personal login.

Editors switch through the Nextcloud profile menu, manage albums and return to their personal account. The gallery’s global configuration remains in the administration settings.

To enable this, configure **Gallery account for account switching** (**Galeriekonto für den Kontowechsel**), activate **Restrict to selected accounts or groups** (**Nur für ausgewählte Konten oder Gruppen**), and list the permitted users or groups. Use an active account without administration rights, log into it directly once, and enable both Photos and Photo Gallery for it.

The switch applies to the entire Nextcloud browser session, including other tabs and apps. Server-side file encryption is not supported for this feature. The app stores no shared-account password.

## Appearance and hosting

Work Sans is included as the default font. You can optionally configure a direct HTTPS WOFF2 URL for gallery and album headings. The visitor’s browser loads that font from the configured host, which must permit cross-origin loading. Use a font you have permission to serve; an empty or unavailable URL falls back to Work Sans.

The regular Nextcloud URL works without an additional proxy. For a separate gallery hostname and shorter URLs, see [hosting](docs/hosting.md). See also [sharing and link previews](docs/sharing.md).

## Project scope and contributions

[Ikigaido](https://ikigaido.ch) is a non-profit association. We develop and maintain Photo Gallery in our free time. Contributions of code, documentation, testing and security research are welcome.

To support development, you can [sponsor Stephan Rickauer on GitHub](https://github.com/sponsors/star26bsd).

This app primarily serves our association’s needs, and we intend to keep developing the features we use. Bug reports and feature requests are welcome. Small, focused contributions that also help our workflow are especially welcome.

The scope is deliberately limited: we do not aim to cover every gallery use case. Please open an issue before starting a substantial change so we can discuss whether it fits.

JavaScript build instructions are in [src/README.md](src/README.md).

## Security

Please [report vulnerabilities privately](https://github.com/ikigaido-org/nextcloud-photo-gallery/security/advisories/new). See our [security policy](SECURITY.md) for scope, safe testing and coordinated disclosure.

## License

Copyright (C) 2026 Stephan Rickauer.

The app’s own code is licensed under **GNU AGPL version 3 only** (`AGPL-3.0-only`); later versions are not included. See [LICENSE](LICENSE) and [COPYRIGHT](COPYRIGHT).

Third-party components retain their own licenses and notices under [vendor](vendor/). Work Sans is distributed under the [SIL Open Font License](fonts/OFL.txt).
