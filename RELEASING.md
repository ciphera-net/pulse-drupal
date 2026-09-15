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

🔑 **The core matrix is why that file matters more than any local run**, and it
decides how the tests are annotated. Measured 15-09-2026:

| | Drupal 10.6 | Drupal 11.4 |
|---|---|---|
| PHPUnit pinned | `^9.6.34` | `^11.5.50` |
| PHPUnit attributes (`#[Group]`) | **class does not exist** | the native form; core uses it 472× and has zero doc-comment `@group` |
| Doc-comment metadata (`@group`) | works | works, emits a deprecation notice |

So the three options are not symmetric:

- **attributes only** → phpstan reports *"Attribute class
  PHPUnit\Framework\Attributes\Group does not exist"* and the **Drupal 10 CI
  job goes red**. Measured, not predicted — this is what the first version of
  these tests did.
- **both forms** → same red, for the same reason. Adding a doc-comment does not
  make the attribute class exist.
- **doc-comments only** → green on both, with cosmetic deprecation notices on 11.

**Doc-comments only, therefore.** A red build on a supported core version beats a
cosmetic notice on another. `@covers` annotations were dropped too: they carry no
execution meaning and were most of the notice count. Revisit when the core floor
moves past Drupal 10 — end of life December 2026 — and switch to attributes then.

## Verified locally, 15-09-2026

Both supported core branches, against real installs, PHP 8.5.10:

| Core | phpcs (Drupal, DrupalPractice) | phpstan | phpunit |
|---|---|---|---|
| **11.4.6** (PHPUnit 11.5.56) | clean | `[OK] No errors` | **OK, 21 tests, 49 assertions** |
| **10.6.16** (PHPUnit 9.6) | clean | `[OK] No errors` | **OK, 21 tests, 49 assertions** ¹ |

¹ ⚠️ **The Drupal 10 leg exits non-zero on this machine, and it is not this
module.** Drupal 10 on PHP 8.5 trips `PDO::sqliteCreateFunction() is deprecated
since 8.5` inside **core's own SQLite driver**, 120 times, and core's
`phpunit.xml` sets `failOnWarning="true"`. Drupal 10.6 declares `php >=8.1.0`
and predates PHP 8.5.

**Proven by control**, the same way the Docusaurus webpack pin was: a Drupal 10
**core** kernel test (`ModuleHandlerTest`) emits the identical deprecations on
this PHP. The module's own result is the `OK (21 tests, 49 assertions)` line.
Run the Drupal 10 leg on PHP 8.3 for a green exit code. drupal.org's CI pins a
supported PHP per core version, so this does not arise there.

🔑 **Prove a failure is yours with a control before you go looking in your own
code.** Twice in one day, on two unrelated ecosystems, a red run was the
fixture's and not the artefact's.

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
