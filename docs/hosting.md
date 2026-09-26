# Clean URLs on a separate public gallery host

Hetzner Storage Share supports custom subdomains. Its documentation does not establish customer-controlled Apache rewrites inside Storage Share. The `.htaccess` example on the subdomain documentation page is for separate web hosting. Do not assume that adding a CNAME will route `/` to this app.

Official reference: https://docs.hetzner.com/storage/storage-share/configuration/subdomain/

The app never edits Nextcloud's `.htaccess`, core routes, trusted domains or host configuration. Normal routes remain usable without a proxy:

- `/apps/photo_gallery/`
- `/apps/photo_gallery/a/summer-event`
- Existing `/apps/photo_gallery/album/1` links remain valid.

## Supported architecture for clean paths

Run a reverse proxy at `gallery.example.org`, on a server where you control routing. Its upstream is the existing `cloud.example.org` Nextcloud instance. No additional domain needs to be registered inside Storage Share in this configuration; the upstream request uses the existing `cloud.example.org` hostname and TLS SNI. DNS for the gallery must reach the proxy, not directly alias Storage Share.

This example serves only the public gallery and selected static/background resources. It does not proxy Nextcloud login, administration or private files. Keep the app's Nextcloud header disabled for this public-only configuration. Administrative work stays on `cloud.example.org`.

1. Install a valid TLS certificate on your proxy and adapt `nginx-gallery.conf.example`.
2. Validate the config with `nginx -t` before reloading. Validate `/`, a known `/album-slug`, image previews and video Range requests through the proxy.
3. In the app admin settings, set **Öffentliche Basisadresse** to `https://gallery.example.org` and enable **Kurze URLs**.
4. Use the gallery hostname. To roll back, uncheck the setting via the original Nextcloud admin page.

The app-side option generates public page links only; it does not create routing, certificates or DNS records. API/media links are now origin-relative so they stay on the same host through the proxy. There is no external catalogue API or CORS setting in 0.5.0.

Slugs use lowercase names, hyphens and German umlaut transliteration. Duplicate names receive an album-ID suffix; reserved names are prefixed. Renaming an album changes its slug. Older numeric album URLs continue working, but old name-slug aliases are not stored. Query parameters preserve year, location and the index page for the return link; `page` refers to the index page, not a manual page of photos.

## Alternatives

A redirect alone exposes the original app path. Browser history replacement alone would fail on reload and is intentionally not used.

The supplied proxy example has not been deployed against your hosting. If your separate web hosting does not allow proxying, another routing-capable host is required. Do not enable the clean-URL switch until that layer is working.
