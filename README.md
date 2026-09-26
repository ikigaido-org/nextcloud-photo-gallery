# Photo Gallery

A public photo album overview for Nextcloud, built on Nextcloud Photos.

Photo Gallery brings publicly shared Photos albums together on one page, with year and location filters and individual album pages for browsing photos and videos.

**[See it in use: Ikigaido’s public gallery](https://nx.ikigaido.ch/apps/photo_gallery/)**

This is our association’s production gallery. It may run a different version from the code on `main`.

## Why this app exists

We built Photo Gallery for [Ikigaido](https://ikigaido.ch), our martial arts association. We needed a public overview of our event albums and a practical way for several people to maintain them.

After exploring options in the Nextcloud ecosystem, we chose to build directly on Photos. Photos remains responsible for albums and sharing; Photo Gallery adds the public overview, its own album viewer and tools for our editorial workflow. Memories is not required.

## Features

- Public album overview with covers, year and location filters, and configurable sorting.
- Responsive album pages with a lightGallery photo and video viewer and selectable information fields.
- Create a Photos album from a folder through the Files menu, with optional subfolders.
- Optional switching from a personal account to a shared gallery account.
- Configurable titles, colours, backgrounds, footer and heading font.
- RSS and Atom feeds, link preview metadata and an `?embed=1` view that hides the header and visible page heading.

Video playback depends on browser support for the original format; the app does not transcode videos.

## Requirements and setup

The current app targets **Nextcloud 33** and **PHP 8.2 or newer**. Enable the Nextcloud **Photos** app and public album sharing. The interface currently uses German labels.

1. Install Photo Gallery using AppDrop with an app ZIP containing a top-level `photo_gallery/` folder. When packaging this repository, put the app files directly inside that folder. Prebuilt JavaScript is included.
2. In Nextcloud’s administration settings, open **Öffentliche Galerie**.
3. Enter the album owners’ account IDs, configure the title and appearance, and enable **Übersicht öffentlich aktivieren**. An empty owner list includes public albums from all active accounts.
4. Create albums and share them publicly through Photos. The overview is available at `/apps/photo_gallery/`.

The overview makes the selected accounts’ existing public albums discoverable together. Private albums are excluded. Creating an album from a folder does not automatically publish it or keep its membership synchronized with later folder changes.

For an existing installation, update the same `photo_gallery` app without uninstalling it. See the [0.8.7 update notes](docs/changes-0.8.7.md), including how to retain texts previously supplied by built-in defaults.

## Working together

Our workflow uses one shared account as the album owner. Selected people can switch to that account from their own login through the Nextcloud profile menu, edit albums and return to their personal account. The gallery’s global configuration remains in the administration settings.

To enable this, configure **Galeriekonto für den Kontowechsel**, activate **Nur für ausgewählte Konten oder Gruppen**, and list the permitted users or groups. Use an active account without administration rights, log into it directly once, and enable both Photos and Photo Gallery for it.

The switch applies to the entire Nextcloud browser session, including other tabs and apps. Server-side file encryption is not supported for this feature. The app stores no shared-account password.

## Appearance and hosting

Work Sans is included as the default font. You can optionally configure a direct HTTPS WOFF2 URL for gallery and album headings. The visitor’s browser loads that font from the configured host, which must permit cross-origin loading. Use a font you have permission to serve; an empty or unavailable URL falls back to Work Sans.

The regular Nextcloud URL works without an additional proxy. For a separate gallery hostname and shorter URLs, see [hosting](docs/hosting.md). See also [sharing and link previews](docs/sharing.md).

## Project scope and contributions

This app primarily serves our association’s needs, and we intend to keep developing the features we use. Bug reports and feature requests are welcome. Small, focused contributions that also help our workflow are especially welcome.

The scope is deliberately limited: we do not aim to cover every gallery use case. Please open an issue before starting a substantial change so we can discuss whether it fits.

JavaScript build instructions are in [src/README.md](src/README.md). An automated test suite is not yet included in this repository.

## License

Copyright (C) 2026 Stephan Rickauer.

The app’s own code is licensed under **GNU AGPL version 3 only** (`AGPL-3.0-only`); later versions are not included. See [LICENSE](LICENSE) and [COPYRIGHT](COPYRIGHT).

Third-party components retain their own licenses and notices under [vendor](vendor/). Work Sans is distributed under the [SIL Open Font License](fonts/OFL.txt).
