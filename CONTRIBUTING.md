# A shared technical notebook

Questions, corrections and practical tips are welcome through this repository's issue templates. A GitHub account is needed to post; reading is public. This is a small independent workshop, so replies and publication are not guaranteed.

Keep examples concrete. Include the software version, prerequisites, a reproducible procedure and a way to check the outcome. Prefer official documentation as a reference. Work only on systems you own or are authorized to administer. Remove credentials, tokens, customer data and private logs. Be considerate and credit other people's work.

Contributions are reviewed before inclusion. Do not add material you lack permission to publish. A submitted issue is not automatically copied onto the site. Report sensitive security details privately by email to jeffdyer@gmail.com rather than opening a public issue.

## Adding content

Edit `content/workshop.json`. Guides live in `guides`, tips in `tips`; all content fields are plain text and escaped by the templates. A guide has a unique lowercase hyphenated slug, title, summary, category, prerequisites (`needs`), ISO date, reading minutes, sections, sources and related guide slugs. Sections accept `heading`, `text`, optional `code`, and optional `check`. Sources use `title` and an HTTPS `url`. Tips link to an existing guide slug.

Use one of the existing categories or update the category buttons in `app/guides.php`. New guides automatically appear in the index, feed, sitemap and static export. Keep slugs stable so shared links continue to work. Check references and commands before publishing. State which environment was tested when you have actually tested it; do not claim testing based only on reading documentation.

Run `php tests/smoke.php`, then lint the PHP files and export both sites as described in README.md. Check long code blocks on mobile, keyboard navigation, search, filter combinations and missing pages. No frontend dependencies or build pipeline are required.
