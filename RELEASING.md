# Releasing pulse_analytics (Drupal)

## 🔴 This repository is the working copy. drupal.org is canonical.

Unlike every other Pulse integration, the home of a contributed Drupal module is
**`git.drupalcode.org`**, not GitHub. That is where the project lives, where the
issue queue lives, where `composer require drupal/pulse_analytics` resolves from,
and where CI actually runs. The GitHub repository is a mirror for the estate's
convenience.

Consequences worth stating before anything else:

- **There is no Woodpecker pipeline here, on purpose.** `.gitlab-ci.yml` runs
  the full supported-core matrix against real databases on drupal.org. A second
  Woodpecker copy could not do that, so it would be a green check guarding very
  little. `./scripts/check.sh` is the local equivalent.
- **A release is two steps, and a tag alone does nothing.** See below.

## Creating the project — first time only

Measured 15-09-2026. The old folklore of "sandbox first, then apply for
promotion to a full project" is **obsolete**; there is no project application
and no reviewer queue.

1. Sign in to drupal.org and make sure the **DrupalCode access** tab on your
   profile is complete. Since a February-2024 policy change the Git terms are
   accepted on **`git.drupalcode.org`** (GitLab), not on drupal.org.
2. `drupal.org/project/add` → **Module** → set **Project type: Full project** →
   Save. The project page exists immediately.
3. Machine name: **`pulse_analytics`** — verified free on 15-09-2026.
   ⚠️ **The short name is permanent and cannot be changed.** `pulse` alone is
   already taken by an unrelated "Pulse Site Template".
4. Push this repository to the project's GitLab remote as branch **`1.x`**.

⚠️ **Trademark.** drupal.org's own guidance is not to use a trademark you do not
own in a project name, "and especially not in the short name, which cannot be
changed". `Pulse Analytics` is Ciphera's own mark, so this is fine — but the
module must not imply a Drupal endorsement, which is why README.md carries an
independence disclaimer. Keep it there.

## A release is a tag AND a release node

Both, in this order. The tag alone publishes nothing.

1. Bump nothing in code — Drupal modules carry no version string in
   `*.info.yml`; drupal.org's packaging script writes it.
2. Tag with **plain semver**: `1.0.0`, `1.1.0`, `2.0.0`. The old
   `8.x-1.0` core-prefixed form is not used for new projects; core compatibility
   is declared by `core_version_requirement` in `pulse_analytics.info.yml`
   instead.
   ```bash
   git tag -a 1.0.0 -m "1.0.0"
   git push origin 1.0.0
   ```
3. On the project page, **Add new release**, choose that tag, write the notes,
   save. drupal.org builds and publishes the tarball within about five minutes.
   **This step is what actually releases.**

## No LICENSE file, and the licence is not our choice

**GPL-2.0-or-later**, required by drupal.org of everything it hosts. Apache-2.0
— which every other public Pulse package uses — is compatible with GPLv3 but not
GPLv2, so it cannot satisfy this. The divergence is recorded in README.md so it
reads as the constraint it is rather than as drift. Joomla and TYPO3 force the
same thing, for the same kind of reason; Astro, Docusaurus, Framer and GTM stay
Apache-2.0.

**Do not commit a `LICENSE.txt`.** drupal.org's packaging script adds one to
every generated tarball. Confirmed against two projects already in the
directory: neither `google_analytics` nor `matomo` carries one.

## What the CI runs

`.gitlab-ci.yml` includes drupal.org's shared template verbatim — which is what
`matomo` does, and the reason to keep it verbatim is that upstream changes then
reach this project automatically. It runs phpcs (Drupal + DrupalPractice),
phpstan, lint passes, and PHPUnit across the supported core matrix.

🔑 **The core matrix is why that file matters more than any local run.** Measured
15-09-2026: **Drupal 10.6 pins PHPUnit `^9.6.34`**, which cannot read attribute
test metadata; **Drupal 11.4 pins `^11.5.50`**, which deprecates doc-comment
metadata and where core itself has migrated fully to attributes (472 attribute
`#[Group]` uses, zero doc-comment `@group`). The tests here therefore carry
**both** `@group` and `#[Group]`:

- attributes only → the tests are **silently not discovered** on Drupal 10;
- doc-comments only → they run everywhere and emit deprecation notices on 11.

A deprecation notice is noise. A test that quietly does not run is a lie about
coverage, so both forms stay until the core floor moves past Drupal 10 (which
reaches end of life in December 2026 — revisit then, and drop the doc-comments).
`@covers` annotations were removed for the same reason in reverse: they carry no
execution meaning and were 13 of the 14 deprecation notices.

## Verified locally, 15-09-2026

Against a real `drupal/recommended-project:^11` install — core **11.4.6**,
PHP 8.5.10, PHPUnit 11.5.56:

| Check | Result |
|---|---|
| `phpcs --standard=Drupal,DrupalPractice` | clean, zero violations |
| `phpstan` (level 1, phpstan-drupal) | `[OK] No errors` |
| `phpunit` unit + kernel | **21 tests, 49 assertions, OK**, zero deprecations |

🔑 **The kernel test is the one that matters.** The unit tests assert the render
*array*; `TagRenderTest` renders it through Drupal and asserts the *HTML*. That
gap is where a defect hides: whether `'defer' => TRUE` becomes `defer`,
`defer="defer"` or `defer=""` is decided by Drupal's renderer, not by us.
Measured: Drupal emits a **bare `defer`**, so the module ships exactly

```html
<script defer data-domain="example.com" src="https://js.ciphera.net/script.js"></script>
```

⚠️ Worth contrasting with the Docusaurus integration, where the same intent
ships as `defer="defer"` because Docusaurus pipes its pages through an HTML
minifier that re-serialises boolean attributes. Two platforms, same input,
different bytes — which is the whole argument for asserting against rendered
output rather than against what the code emits.

## Local checks

```bash
./scripts/check.sh            # reuses a harness if one exists
./scripts/check.sh --fresh    # rebuilds it
DRUPAL_CORE='^10' ./scripts/check.sh --fresh   # the other supported branch
```

Needs `php` and `composer` on PATH. The first run builds a throwaway Drupal site
and takes a few minutes.
