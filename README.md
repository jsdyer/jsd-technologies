# JSD Technologies: two doors, one workshop

A small, framework-free PHP website for two domains. **jsdtechnologies.ca** is the client entrance: web development, IT solutions, security, search visibility, advertising, monetization, indicative CAD pricing, real independent work, and a browser-local project brief builder. **jsd.technology** is the technical workshop: searchable projects, exploratory concept briefs, build notes, and RSS. The personal field journal lives independently in [jsdyer/jeffrey-dyer](https://github.com/jsdyer/jeffrey-dyer).

## Run locally

Requires PHP 8.2+; no Composer, Node, or frontend build step.

```sh
SITE_KEY=studio php -S localhost:8080 -t public
# In another terminal:
SITE_KEY=workshop php -S localhost:8081 -t public
```

Open http://localhost:8080 or http://localhost:8081. Production links in the shared navigation deliberately use the actual domains. For a single-origin preview of all three sites, see `bin/export.php` below. `SITE_KEY` is a process environment override for local use; Apache selects workshop only for `jsd.technology` and `www.jsd.technology`, otherwise studio. No arbitrary Host value enters an absolute URL.

## Deploy on Linux / Apache

1. Clone into `/srv/jsd-technologies` (or adjust paths).
2. Point **both domains' document roots to `public/`**, never the repository root. The content files, scripts, and optional database stay outside the web root.
3. Adapt `deploy/apache.conf.example` to the server's existing PHP handler. Enable HTTPS with your certificate tooling and redirect HTTP to HTTPS before launch. DNS and production hosting are not changed by this repository.
4. Keep the checkout read-only to the web user. Only a CLI content import needs write access to `var/` when using SQLite.
5. Review the suggested pricing, service scope, public copy, and contact email before a public launch. Prices are planning ranges, not market research or binding offers. The email currently used is `jeffdyer@gmail.com`; change it in templates and `public/assets/app.js` if you prefer a business mailbox.

The contact builder creates text locally. It supports copying, a `.txt` download, and opening a `mailto:` URL. It does **not** send email, store inquiries, or imply successful delivery. Long briefs can exceed a mail client's URI limit; copying or downloading is the reliable alternative. There are no third-party fonts, trackers, CDNs, or external scripts.

## Content and database choices

The default is version-controlled JSON in `content/` plus clear PHP templates in `app/`. Content is intentionally usable without a writable database. Project descriptions and introductory notes are an initial editorial draft based on the owner's stated interests; review before wider publication.

SQLite is optional, using PHP PDO SQLite. The CLI imports the JSON and both websites can read the resulting single file:

```sh
php bin/content.php import
CONTENT_STORE=database SITE_KEY=workshop php -S localhost:8081 -t public
```

For MariaDB, create a UTF-8 database and dedicated account, then provide `DB_DSN`, `DB_USER`, and `DB_PASSWORD` through your server's environment, not a tracked file. The PDO DSN form is `mysql:host=localhost;dbname=jsd_technologies;charset=utf8mb4`. Run the same import command under a deployment account, then serve with `CONTENT_STORE=database` and a read-only database account. The portable table uses `VARCHAR(32)` and `TEXT`. No public database editor is exposed.

`php bin/content.php export` exports the database back into the versioned JSON files. It overwrites those files: commit or back up local edits first. `.env.example` documents variables; the application does not parse `.env` files. In PHP-FPM, configure environment passing in the pool; Apache `SetEnv` alone may not reach FPM.

The database is a deliberately small content store, not an inquiry system or CMS. File mode is a sensible choice for these sites. MariaDB support uses portable PDO SQL, but this environment's integration tests cover SQLite, not a running MariaDB server.

## Structure and editing

- `app/core.php`: routing helpers, escaping, PDO content loading, RSS, rendering.
- `app/layout.php`: metadata, shared navigation and footer.
- `app/studio.php`, `app/workshop.php`: presentation and service copy.
- `content/studio.json`: the six service offerings (the cards and inquiry selector use the same data).
- `content/workshop.json`: projects, concept briefs, and notes; use simple paragraphs, not raw HTML.
- `public/assets/`: self-contained CSS, JavaScript, and SVG favicon.
- `bin/content.php`: CLI database import/export.
- `bin/export.php`: render the exact PHP templates as static preview pages.

Keep project statuses honest. The current portfolio links to verified repositories; new experiments are labelled “In development.” CoolCode remains the main runnable-project catalogue.

## Check and preview

```sh
php tests/smoke.php
find app bin public tests -name '*.php' -exec php -l {} \;
php bin/export.php studio /tmp/jsd-preview
php bin/export.php workshop /tmp/jsd-preview/workshop
# From the separate personal checkout:
# php bin/export.php personal /tmp/jsd-preview/personal
```

Static exports expect the combined preview paths `/`, `/workshop/`, and `/personal/`. They are marked `noindex`; canonical and RSS item URLs retain the production domains. They are a review surface, not the PHP production deployment. To serve the static preview locally: `python3 -m http.server 8000 --directory /tmp/jsd-preview`. The hosted preview is maintained separately from the two GitHub source repositories.

Browser QA covers the brief generation/copy flow, project filters/search, navigation, and note rendering. PHP checks cover rendering, escaping, missing-note responses, and SQLite content parity. No account system, analytics service, or scheduled background job is required.

## Ideas shelf

The workshop has four expandable concept briefs: an IBKR portfolio notebook, a Strava ride journal, a sensor dashboard, and a photo contact-sheet tool. These are placeholders for investigation, not working integrations. There are no connected accounts, tokens, live financial data, or trading controls. Official API starting points are linked from the IBKR and Strava briefs. Access and usage rules must be checked again before implementation.

The studio uses “search visibility” and “getting found” language. Security services describe authorized incident triage, hardening, updates, and recovery; they do not claim certifications, guaranteed prevention, or 24/7 emergency coverage. Advertising spend is separate from service fees.
