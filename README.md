# Pulse Analytics for Drupal

This module adds privacy-first analytics to a Drupal site. Pulse Analytics
sets no cookies and never stores IP addresses, and the script the module adds
to your pages is under 3 KB.

[Pulse Analytics](https://pulse.ciphera.net) is built by
[Ciphera](https://ciphera.net), a company in Belgium, and hosted in Switzerland.

## Requirements

The module needs Drupal 10 or 11. It has no Composer dependencies or external
libraries, and it adds no database tables. You need a Pulse Analytics account
with a site registered for the same domain; the free plan is enough to start.

## Install

```
composer require drupal/pulse_analytics
drush en pulse_analytics
```

Then go to **Configuration → Web services → Pulse Analytics**
(`/admin/config/services/pulse-analytics`) and enter the domain your site is
registered under in Pulse.

You can leave the domain empty. The tracker falls back to the browser's own
hostname, which is correct for a site served on one domain. Set it explicitly
when the site answers on several.

## Settings

| Setting | Default | What it does |
|---|---|---|
| Domain | empty | The domain this site is registered under in Pulse. Empty means auto-detect. |
| Record clicks, copies and form submits | off | Loads the companion script as a second, separate request (see below). |
| Also track administration pages | off | Administration pages are not the site's audience, and counting them inflates every number on the dashboard. |
| Custom API origin | empty | Route events through your own proxy origin. Enter the origin only, without a path. |

The settings page needs the **Administer Pulse Analytics** permission. Updating from 1.0.x grants it to every role that
had **Administer site configuration**, which the page used before, so nobody loses access.

## Three things worth knowing

**What lands in your pages is the literal tag**, without a wrapper or a bootstrap:

```html
<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>
```

The module adds no inline JavaScript, so **a strict Content-Security-Policy
needs neither a nonce nor `unsafe-inline` for this module**. The policy needs
only `script-src https://js.ciphera.net` and `connect-src https://pulse-api.ciphera.net`.
A kernel test pins this, so it cannot quietly stop being true.

**The interaction capture is a second request on purpose.** The core script's
size is a published claim, and nothing is folded into it to make a feature look
free.

**A mistyped domain is rejected at the settings form.** An analytics install
that reports nothing while looking fine is the worst outcome this module can
produce, so it is worth an error message.

## Why not a library

The tag is attached with `hook_page_attachments()` and
`#attached['html_head']` instead of a `*.libraries.yml` entry. A library is a
static declaration and `data-domain` varies per site, so the tag has to be built
from configuration at request time.

The attachment carries the `config:pulse_analytics.settings` cache tag. Without
it, changing the domain would leave every already-rendered page serving the old
one. Because the page still works, nothing would look broken.

## What Pulse Analytics measures

Pulse Analytics measures pageviews, referrers, countries, devices, time on page and scroll depth, plus goals, funnels and campaigns.
It sets no cookies and never stores IP addresses.
Visitors whose browser sends Do Not Track or Global Privacy Control are not counted, so if you test in Brave or Firefox and see nothing, the tracker is behaving correctly.
Safari is the easiest browser to verify an install in.

## Licence

The module is licensed **GPL-2.0-or-later**, which was not our choice.

Every other public Pulse integration (the Astro, Docusaurus, Framer and Google
Tag Manager ones) is Apache-2.0. This module is not, because drupal.org
requires GPL-2.0-or-later of everything it hosts: Drupal core and the modules
installed alongside it are treated as one combined program, so a module inherits
core's licence. Apache-2.0 is compatible with GPLv3 and incompatible with GPLv2,
so it cannot satisfy that requirement.

So the divergence is deliberate and unavoidable. The Joomla and TYPO3
integrations are GPL for the same reason; the rest stay Apache-2.0.

Per drupal.org convention there is no `LICENSE.txt` in this repository.
drupal.org's packaging script adds it to every generated release tarball.

Drupal is a registered trademark of Dries Buytaert. This is an independent
module and is not affiliated with or endorsed by the Drupal project or the
Drupal Association.
