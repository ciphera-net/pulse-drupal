# Releasing pulse_analytics (Drupal)

## 🟢 LIVE since 16-09-2026 — `1.0.0`

- Project: <https://www.drupal.org/project/pulse_analytics> · node id **3623475**
- Repo: `https://git.drupalcode.org/project/pulse_analytics.git` · GitLab project id **244376**
- Release: <https://www.drupal.org/project/pulse_analytics/releases/1.0.0>
- `composer require drupal/pulse_analytics` resolves.
- Maintainer account: **`ciphera`**.

## 🔴 This repository is the working copy. drupal.org is canonical.

Unlike every other Pulse integration, the home of a contributed Drupal module is
**`git.drupalcode.org`**, not GitHub. That is where the project lives, where the
issue queue lives, where `composer require drupal/pulse_analytics` resolves from,
and where CI actually runs. The GitHub repository is a mirror for the estate's
convenience.

Consequences worth stating before anything else:

- **There is no Woodpecker pipeline here, on purpose.** `.gitlab-ci.yml` runs
  the supported-core matrix against real databases on drupal.org. A second
  Woodpecker copy could not do that, so it would be a green check guarding very
  little. `./scripts/check.sh` is the local equivalent.

  🔴 **Corrected 16-09-2026 — this was NOT true of the include alone, and the
  first real pipeline proved it.** Measured on `#963131`: **one** phpunit job,
  Drupal 11 only, no matrix — because every `OPT_IN_TEST_*` in the shared
  template defaults to `'0'`. Meanwhile `pulse_analytics.info.yml` claims
  `^10 || ^11`. So the local harness was the only thing testing Drupal 10, the
  exact reverse of the sentence above. Worse, **phpcs, phpstan, eslint and
  cspell all ran `allow_failure: true`** — four of seven jobs could not fail the
  build, and **cspell was RED while the pipeline reported `success`**.
  `.gitlab-ci.yml` now sets `OPT_IN_TEST_PREVIOUS_MAJOR`,
  `_ALL_VALIDATE_ALLOW_FAILURE` and `_CSPELL_ALLOW_FAILURE` to fix both.
  🔑 **A pipeline's own status is not a statement about its jobs** — read
  `allow_failure` per job before calling a green pipeline a passing one.
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

## ✅ What a fully-green drupal.org pipeline looks like (16-09-2026)

Measured on **#963160**, `32aa7da`, and worth recording because the first two
pipelines looked green and were not:

| | first push (#963131) | after the fix (#963160) |
|---|---|---|
| jobs | 7 | **10** |
| jobs that can fail the build | 3 | **10** |
| core versions tested | 1 (Drupal 11) | **2** — `phpunit (previous major)` runs |
| cspell | **failed**, pipeline still `success` | success |

Check a pipeline like this, not by its badge:

```bash
curl -sS "https://git.drupalcode.org/api/v4/projects/244376/pipelines/<id>/jobs?per_page=100" \
 | python3 -c "import sys,json;[print(f\"{j['status']:9} allow_failure={str(j['allow_failure']):5} {j['name']}\") for j in sorted(json.load(sys.stdin),key=lambda x:x['name'])]"
```

⚠️ **`allow_failure=True` on any job means that job is decoration.** The project
id is **244376**; the job trace endpoint needs the fine-grained permission
**Job: Read**, which the push token deliberately does not have.

## A release is a tag AND a release node

Both, in this order. The tag alone publishes nothing. ✅ **Walked end to end for
`1.0.0` on 16-09-2026**; every step below is as-built, not as-planned.

1. Bump nothing in code — Drupal modules carry no version string in
   `*.info.yml`; drupal.org's packaging script writes it. Confirmed on the
   published tarball, which gained:
   ```yaml
   # Information added by Drupal.org packaging script on 2026-09-16
   version: '1.0.0'
   project: 'pulse_analytics'
   datestamp: 1789549452
   ```
2. Push to the **`1.x`** branch, not `main`. The branch name is the release
   SERIES: drupal.org reads it to decide which series a tag belongs to. Pushing
   to `main` gives the project code and no releasable series, and that fails at
   the release form rather than at push time.
   ```bash
   git fetch origin && git push drupal origin/main:refs/heads/1.x
   ```
   Push `origin/main`, not a local `main`. A local `main` that is behind
   `origin/main` but level with drupal.org's `1.x` pushes nothing, and git
   reports `Everything up-to-date`.
3. Tag with **plain semver**: `1.0.0`, `1.1.0`, `2.0.0`. The old `8.x-1.0`
   core-prefixed form is not used for new projects; core compatibility comes from
   `core_version_requirement` in `pulse_analytics.info.yml` instead.
   ```bash
   git tag -a 1.0.0 -m "1.0.0" && git push drupal 1.0.0
   ```
4. **Create the release node** at
   **`https://www.drupal.org/node/add/project-release/3623475`** (3623475 is this
   project's node id — the "Add new release" link is easy to lose).
   🔴 **The form itself states the rule that matters:** *"Before clicking Next,
   the Git tag can be deleted or moved. It can not be modified after clicking
   Next."* So a tag is **mutable right up to that button and permanent after
   it** — which is the window to fix a tag that points at the wrong commit.
   ⚠️ Tick **"This release will not be covered for security advisories"**; the
   project cannot opt in yet. Release type: **New features** for a first release
   (nothing to fix, no prior baseline) — an untyped release renders as an empty
   row in the downloads table.

### 🔑 How to verify a release, and in what order

⚠️ **`drupal.org` serves its "Page not found" page with HTTP 200.** Measured
against an invented path. A status code proves nothing on this host — compare
the `<title>`. This bit three times in one session: the GitLab sign-in stub, the
`/user/N/edit/*` probe, and the release page.

Three signals, and they do **not** arrive together. Measured on `1.0.0`:

| Signal | What it is | Timing |
|---|---|---|
| `ftp.drupal.org/files/projects/pulse_analytics-1.0.0.tar.gz` | the artefact a human downloads | **200 first** |
| `updates.drupal.org/release-history/pulse_analytics/current` | what every Drupal site's `update` module polls | **~90 s LATER** |
| the release page | a node; anonymous sees "Log in" until it is public | with the feed |

🔴 **The feed is the finish line, not the tarball.** At 09:04 the download
worked and the feed still said *"No release history was found"* — a minute in
which the release looks published and is discoverable by nobody. **Check the
feed.** Same failure class as the Joomla `pulse-update.xml` trap.

```bash
curl -sS "https://ftp.drupal.org/files/projects/pulse_analytics-<v>.tar.gz" -o /tmp/p.tgz -w '%{http_code} %{size_download}\n'
curl -sS "https://updates.drupal.org/release-history/pulse_analytics/current" | grep -c "<version><v></version>"
curl -sS "https://packages.drupal.org/files/packages/8/p2/drupal/pulse_analytics.json" \
  | python3 -c "import sys,json;print([p['version'] for p in json.load(sys.stdin)['packages']['drupal/pulse_analytics']])"
```

✅ `1.0.0`: tarball 25,654 bytes, feed carries it, and
`composer require drupal/pulse_analytics` resolves against
`packages.drupal.org`. The packaging script also adds the `LICENSE.txt` this
repo deliberately does not commit — confirmed present in the tarball.

### Pushing: credentials

SSH is not available — **port 22 to `git.drupalcode.org` times out** from the
workspace. Push over HTTPS with username **`oauth2`** and a personal access
token as the password.

🔴 **Use a fine-grained personal access token with the project permission
Code: Push.** On 19-10-2026 a fine-grained token without that permission was
refused with the message below, and a token with it pushed:

```text
This operation requires a fine-grained personal access token with the following project permissions: [Code: Push]
```

The 16-09-2026 pushes used a legacy `write_repository` token. Whether
drupal.org still lets you create a legacy token was not checked.

The push needs that one permission and nothing else. On git.drupalcode.org:
avatar → **Edit profile** → **Access → Personal access tokens** → **Generate
token** → **Fine-grained token**, then:

| Field | Value |
|---|---|
| Group and project access | the option that starts "Only specific groups or projects" (it ends "…that I'm a member of" in GitLab 19.2 to 19.4 and "…I have access to" from 19.5), then **Add group or project** → `project/pulse_analytics` |
| Resource permissions, Group and project tab | resource **Code**, permission **Push** |
| User and Global tabs | nothing |

drupal.org's GitLab documents that row for the version it runs, in the "Git
operations" table at
<https://git.drupalcode.org/help/auth/tokens/fine_grained_access_tokens_other.md>.
Reading needs no permission, because the project is public.

⏱ **Set the expiry date yourself.** GitLab's form fills in a date 30 days ahead
(`OFFSET_DAYS = 30` in its source). The first token, created 16-09-2026,
expired that way on 16-10-2026. The documented maximum is 365 days.

The token is `DRUPALCODE_TOKEN` in the workspace root `.env`, mirrored at
`kv/shared/drupalcode-token`.

| What git or the API answers | What it means |
|---|---|
| `HTTP Basic: Access denied` | the token is expired, revoked or wrong |
| `…project permissions: [Code: Push]` | the token is fine-grained but lacks Code: Push |
| 403 `[Personal Access Token: Read]` on `/api/v4/personal_access_tokens/self`, 403 `[User: Read]` on `/api/v4/user` | the token holds only Code: Push, as intended, so this is not a dead token |

Never put the token on a command line, and **reset the credential helper list**
with an empty `credential.helper=` before adding the store file. Without the
reset, a global helper such as the macOS keychain also saves the token. On later
pushes git asks that helper first and uses whatever token it saved, even an
expired one.

`--dry-run` tests a new token without changing anything. GitLab runs the push
permission check on the first request of a push
(`GET info/refs?service=git-receive-pack`), and a dry run sends nothing after
that request.

```bash
umask 077; CRED=$(mktemp)
printf 'https://oauth2:%s@git.drupalcode.org\n' "$DRUPALCODE_TOKEN" > "$CRED"
git fetch origin
GIT_TERMINAL_PROMPT=0 git -c credential.helper= -c credential.helper="store --file=$CRED" \
  push --dry-run drupal origin/main:refs/heads/1.x
GIT_TERMINAL_PROMPT=0 git -c credential.helper= -c credential.helper="store --file=$CRED" \
  push drupal origin/main:refs/heads/1.x
rm -f "$CRED"
```

## Project logo

`logo.png` in the repository root is the logo drupal.org shows for this
project: the Pulse mark on the dark ground `#0a0a0a`, 512 x 512 px, 9,026
bytes, copied byte for byte from the brand library's Drupal pack.

The rule is in the Project Browser maintainer guide on drupal.org, section Logo:

<https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/project-browser/module-maintainers-how-to-update-projects-to-be-compatible-with-project-browser#s-logo>

It asks for a PNG named `logo.png`, 512 x 512 px, not animated, suggested size
10 KB or less, with no rounded corners and no module name drawn in it, in the
root of the repository on the default branch. Here that is `1.x`. The file
already meets those requirements, so do not re-render, resize or recompress it.

There is no separate upload. GitLab serves `logo.png` from the default branch as
the project avatar (`https://git.drupalcode.org/project/pulse_analytics/-/avatar`)
and the drupal.org project page reads that URL, so the logo reaches drupal.org
like any other change: merge to `main`, then push `origin/main` to `1.x`
(credentials above). No tag, release node or new release is involved. The guide
says logos are cached and can take up to an hour to show, and lists the Project
Browser card among the places it appears. To check that it landed, the
`avatar_url` field of `https://git.drupalcode.org/api/v4/projects/244376` is set
and that URL returns the same bytes as `logo.png`. The project page then shows
`<img class="repository-logo" src="https://git.drupalcode.org/project/pulse_analytics/-/avatar">`,
the markup every project with a logo uses.

✅ Measured on the first logo push, 19-10-2026: pushed at 22:00:31 UTC, the
avatar was set and byte-identical to `logo.png` within 8 seconds, and the project
page showed it at 22:19:33 UTC, 19 minutes after the push.

drupal.org builds release archives with `git archive`, so an `export-ignore`
line in `.gitattributes` could keep the logo out of them. This repository has no
`.gitattributes`. The guide offers that mechanism for tests and CI settings,
which the 1.0.0 archive already ships, and nothing requires it for a logo. The
1.0.0 archive predates the logo, so the next release will be the first to
contain it.

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

`.gitlab-ci.yml` includes drupal.org's shared template — which is what `matomo`
does, and the reason to keep the include itself untouched is that upstream
changes then reach this project automatically. It runs phpcs (Drupal +
DrupalPractice), phpstan, eslint, cspell, a composer lint pass, and PHPUnit.

⚠️ **The include is kept verbatim; the VARIABLES are not.** Three overrides sit
above it and each one was earned by a measurement, not a preference — see the
comment block in that file. Without them the matrix does not run and most of the
validate stage cannot fail.

### cspell needs a project dictionary

`.cspell-project-words.txt` is that dictionary — 22 words, every one a real
failure on the first pipeline. Most are proper nouns and tool names; the
interesting group is the **British spellings** (`licence`, `analyse`,
`normalised`, `serialises`), which the job's en-US dictionary rejects. They are
deliberate: the house voice is British English and the code uses it too
(`TagBuilder::normalise*`), so "correcting" them would mean renaming identifiers.
Regenerate the list rather than guessing at it:

```bash
npx cspell@8 -c .cspell.json --no-progress --words-only --unique "**/*"
```

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
