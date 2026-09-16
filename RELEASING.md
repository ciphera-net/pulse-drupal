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

1. Sign in to drupal.org and accept the **Git Terms of Service**. Since a
   February-2024 policy change these are accepted on **`git.drupalcode.org`**
   (GitLab), not on drupal.org — the announcement's own words: *"When you next
   use git.drupalcode.org, you will be asked to accept terms of service"*, and
   contributors *"will need to accept these terms before pushing code to
   git.drupalcode.org or continuing while logged in"*.

   🔴 **THE ORDER MATTERS AND GETTING IT WRONG 403s TWICE. Hit 16-09-2026.**
   Going straight to `git.drupalcode.org` does NOT work: it bounces to drupal.org
   and returns **`403 - Access denied`**, and so does `drupal.org/project/add`.
   **Both 403s are the same one.** `git.drupalcode.org/users/sign_in` is a
   580-byte stub that auto-POSTs to `/users/auth/jwt`, which hands straight off
   to drupal.org — GitLab is a passthrough and is not the thing refusing you.

   ✅ **The documented order, verbatim from `drupal.org/docs/develop/git/
   setting-up-git-for-drupal/obtaining-git-access`:**

   1. *"Navigate to your user profile and click the **DrupalCode access** tab"* —
      this is on **drupal.org**, and it is where *"your drupal.org username is
      assigned as your git.drupalcode.org username by default"*. **Do this
      first.** Until an identity is assigned, the JWT hand-off has nothing to map
      and refuses. ⚠️ Navigate by the **tab on your profile** — the direct path
      could not be verified from outside, because drupal.org redirects every
      unknown `/user/N/edit/*` to the login page instead of 404ing, so probing
      cannot tell a real tab from a typo.
   2. Then, logged in, visit `git.drupalcode.org`: *"You should be redirected to
      a personalized form that includes the Terms of Service… click 'Accept
      terms' at the bottom"*, and *"You should be redirected to your personal
      Projects page"*. That is the success signal — **a 403 here means step 1 is
      not done.**
   3. Only then does `drupal.org/project/add` load.

   🔑 **The discriminator, measured:** an ANONYMOUS request to
   `drupal.org/project/add` **redirects to `/user/login?destination=project/add`**
   — it does not 403. So a 403 proves you are logged in and lack the permission,
   never that you are signed out.

   ⚠️ **If step 1's tab is missing or step 2 still 403s**, the docs' only
   escalation is: *"post an explanation… to a new issue in the Drupal.org site
   moderators project"*. Note the doc's wording — *"if you think Git access was
   wrongly **removed** from your account"* — so the permission is revocable, and
   a suspension flag presents the same way.

   ⚠️ The canonical how-to (`drupal.org/node/1011196`, updated 8 August 2026)
   mentions **none** of this; its step 1 is only *"Set up Git on your local
   computer"*, which is where the whole gate hides.
2. `drupal.org/project/add` → **Module** → set **Project type: Full project** →
   Save. The project page exists immediately. ✅ **Re-confirmed 16-09-2026**
   against the current doc, which says verbatim: *"Some project types will have a
   Project type field with sandbox, and full options. **If available you should
   choose the Full project option.**"* Sandbox is now explicitly **deprecated**.
   ⚠️ Stale third-party blog posts still describe a "one-time approval process"
   to promote sandbox → full; that is the obsolete folklore, not the current flow.
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

## ⏳ A dated constraint that resolves itself — measured 16-09-2026

A fresh `./scripts/check.sh --fresh` on Drupal 11.4.6 is green (`ALL CHECKS
PASSED`, 21 tests / 49 assertions, phpcs clean, phpstan `[OK] No errors`) but
reports **one real deprecation** alongside the ten expected doc-comment ones:

> *"Kernel test classes must specify the `#[RunTestsInSeparateProcesses]`
> attribute, not doing so is deprecated in drupal:11.3.0 and **will throw an
> exception in drupal:12.0.0**."*

🔑 **This is not cosmetic and it cannot be fixed today**, because the fix is a
PHPUnit **attribute** — the exact construct that reds the Drupal 10 job, since
PHPUnit 9.6 has no attribute classes and phpstan reports them as non-existent.
So the module is pinned between two core versions pulling opposite ways:

| | wants | because |
|---|---|---|
| Drupal 10.6 (PHPUnit 9.6) | **no** attributes | the classes do not exist; phpstan goes red |
| Drupal 12 | **requires** `#[RunTestsInSeparateProcesses]` | doc-comment era is over; this throws |

**Nothing to do now** — Drupal 12 is not out, and 11.4 only warns. It resolves on
the trigger this file already names: **Drupal 10 end of life, December 2026.**
At that point drop the `@group` doc-comments, add `#[Group]`, and add
`#[RunTestsInSeparateProcesses]` to `TagRenderTest` — one change, not two.
⚠️ Do not add the attribute early to silence the notice; it trades a warning on a
core version that is not released for a **red build on one that is supported**.

## Local checks

```bash
./scripts/check.sh            # reuses a harness if one exists
./scripts/check.sh --fresh    # rebuilds it
DRUPAL_CORE='^10' ./scripts/check.sh --fresh   # the other supported branch
```

Needs `php` and `composer` on PATH. The first run builds a throwaway Drupal site
and takes a few minutes.
