# Files action and account switcher source

The server runs the prebuilt ../js/files-albums.js and ../js/account-switcher.js; no npm or console access is needed to install the app.

For development, in this src directory run `npm ci` followed by `npm run build` with Node 24. The pinned lockfile matches the released bundle. esbuild is a build-time tool; jsdom is used for DOM-based tests, not a browser or runtime dependency. Third-party notices and package sources/source maps are under ../vendor/files-integration/.

The Files action uses the standard Nextcloud 33 Files v4 registry and current authenticated browser session. Album creation uses Photos' existing DAV interface.

The account switcher uses two authenticated, CSRF-protected POST endpoints to start and finish delegation. Authorization is checked server-side; the browser cannot select another target or return identity. Credentials are not stored. Cross-tab notification carries only a random change marker.
