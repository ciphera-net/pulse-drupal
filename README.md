# Pulse Analytics for Drupal

Privacy-first analytics for a Drupal site, in one module. No cookies, no
personal data, and the script it adds to your pages is under 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in Europe.

## Requirements

Drupal 10 or 11. No Composer dependencies, no external libraries, no database
tables. You need a Pulse Analytics account with a site registered for the same
domain; the free plan is enough to start.

## Install

```
composer require drupal/pulse_analytics
drush en pulse_analytics
```

Then go to **Configuration → Web services → Pulse Analytics**
(`/admin/config/services/pulse-analytics`) and enter the domain your site is
registered under in Pulse.

You can leave the domain empty. The tracker falls back to the browser's own
hostname, which is correct for a site served on one domain — set it explicitly
when the site answers on several.

## Settings

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Record clicks, copies and form submits | off | Loads the companion script. A second, separate request — see below. |
| Also track administration pages | off | Administration pages are not the site's audience, and counting them inflates every number on the dashboard. |
| Custom API origin | empty | Route events through your own proxy origin. A bare origin, not a full URL. |

## Three things worth knowing

**What lands in your pages is the literal tag**, not a wrapper or a bootstrap:

```html
<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>
```

No inline JavaScript at all, which means **a strict Content-Security-Policy
needs no nonce and no `unsafe-inline` for this module** — only
`script-src https://js.ciphera.net` and `connect-src https://pulse-api.ciphera.net`.
That is pinned by a kernel test, so it cannot quietly stop being true.

**The interaction capture is a second request on purpose.** The core script's
size is a published claim, and nothing is folded into it to make a feature look
free.

**A mistyped domain is rejected at the settings form**, rather than saved and
quietly dropped. An analytics install that reports nothing while looking fine is
the worst outcome this module can produce, so it is worth an error message.

## Why not a library

The tag is attached with `hook_page_attachments()` and
`#attached['html_head']`, not a `*.libraries.yml` entry. A library is a static
declaration and `data-domain` varies per site, so the tag has to be built from
configuration at request time.

The attachment carries the `config:pulse_analytics.settings` cache tag. Without
it, changing the domain would leave every already-rendered page serving the old
one — and because the page still works, nothing would look broken.

## What Pulse Analytics measures

Pageviews, referrers, countries, devices, time on page and scroll depth, plus
goals, funnels and campaigns. It sets no cookies and stores no personal data.
Visitors whose browser sends Do Not Track or Global Privacy Control are not
counted at all — so if you test in Brave or Firefox and see nothing, that is the
tracker behaving correctly. Safari is the easiest browser to verify an install
in.

## Licence

**GPL-2.0-or-later**, and that is not a choice we made.

Every other public Pulse integration — the Astro, Docusaurus, Framer and Google
Tag Manager ones — is Apache-2.0. This module is not, because drupal.org
requires GPL-2.0-or-later of everything it hosts: Drupal core and the modules
installed alongside it are treated as one combined program, so a module inherits
core's licence. Apache-2.0 is compatible with GPLv3 but **not** with GPLv2, so
it cannot satisfy that requirement.

So the divergence is deliberate and unavoidable rather than drift. The Joomla
and TYPO3 integrations are GPL for the same reason; the rest stay Apache-2.0.

Per drupal.org convention there is no `LICENSE.txt` in this repository —
drupal.org's packaging script adds it to every generated release tarball.

Drupal is a registered trademark of Dries Buytaert. This is an independent
module and is not affiliated with or endorsed by the Drupal project or the
Drupal Association.
