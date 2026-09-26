# 0.8.7

- Add one optional WOFF2 URL in Administration → Öffentliche Galerie → Schrift für Überschriften. It applies to the main gallery title and individual album page titles, independently of the colour/background settings. Work Sans is used when the URL is empty or the font cannot load.
- Remove the bundled Skia font. External fonts are loaded by the visitor's browser over HTTPS. The configured origin is permitted for fonts only in both public page modes. Use a direct URL without credentials or a fragment; the font host must allow CORS for the gallery's origin and should serve the file without redirecting to another origin.
- Use neutral default gallery texts and example values in the administration form. Saved titles, descriptions, footer, account selection and appearance settings are preserved. Older settings schemas no longer overwrite saved titles, descriptions or sorting choices with defaults.
- Pin the application's own code to AGPL-3.0-only. Third-party license notices are unchanged. Include the Work Sans OFL text.

## Updating from 0.8.6

Before updating, save the current gallery settings once if you want to retain texts that were only supplied by the previous built-in defaults. Update the app through AppDrop without uninstalling it. Then enter your permitted webfont URL if required; the app does not automatically configure any external font source.

The public-facing README is awaiting a separate review. No automated test suite is included in this package; that work is deferred until the repository exists.
