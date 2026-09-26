# Sharing and basic SEO

Public album HTML includes canonical URL, descriptive title/description, Open Graph title/type/url/site name/locale, and an absolute album-cover image URL with alt text. Tags are generated on the server, without JavaScript. Numeric and named album pages point to the same canonical named URL. Index pagination/filter pages retain their content selection; embed is excluded from canonical URLs. Normal public pages request index/follow; embedded and unavailable pages request noindex.

These tags do not override robots.txt, hosting-level response headers or crawler access controls. If link previews fail, check crawler access with your hosting provider and whether a narrowly scoped robots exception is supported for the public gallery pages, their images and required assets. Do not open unrelated Nextcloud paths globally. The clean gallery hostname could have its own robots policy when actually hosted through a controlled proxy.

After upgrading, check the actual album response and cover response anonymously: HTTP 200, correct head metadata, a publicly readable image, and no conflicting noindex header/meta from the wrapper or host. Then retry Facebook Sharing Debugger. If robots.txt or a provider rule still blocks access, the app cannot fix that rule. No crawler bypass or host configuration change is included in this release.

References: https://ogp.me/ and https://developers.google.com/search/docs/fundamentals/seo-starter-guide
