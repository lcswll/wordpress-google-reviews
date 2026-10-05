# Wille Reviews – Review Widgets for Google

WordPress-Plugin: Google-Bewertungen eines Unternehmensprofils in **6 Layouts × 5 Styles** anzeigen – ausgewählt auf einer Design-Seite mit Live-Vorschau und Shortcode-Generator, als Shortcode, als Block oder als schwebendes Badge. Die Daten holt der Server über die Google Places API (New) und cacht sie; Besucher stellen keine Anfrage an Google, Profilbilder werden lokal gespiegelt.

Technische Umsetzung nach dem Vorbild des PlayersHUB Trust Badge (serverseitiger Abruf, Transient + Backup-Option, Cron-Refresh, lokale Avatare), aber als eigenständiges, wordpress.org-taugliches Plugin mit der Werkzeugkette aus wordpress-geo / wordpress-mails.

## Struktur

```
wille-reviews/                          Version 1.0.0
├── wille-reviews.php                   Bootstrap + Plugin-Header + WP-CLI-Registrierung
├── readme.txt                          WordPress.org-Readme (inkl. „External services“)
├── uninstall.php                       Datenlöschung (nur bei Opt-in, multisite-fähig)
├── includes/
│   ├── class-willerev-plugin.php       Orchestrator, Einstellungen
│   ├── class-willerev-install.php      Defaults, Aktivierung/Deaktivierung
│   ├── class-willerev-places.php       Places API (New): Abruf, Normalisierung, Cache, Backup (max. 30 Tage), Cron, Fehler-Backoff
│   ├── class-willerev-avatars.php      Profilbilder nach uploads/wille-reviews/ spiegeln (Magic-Bytes-Prüfung)
│   ├── class-willerev-render.php       HTML für alle Layouts × Styles, Sterne, Badge, Social Proof, Demo-Daten
│   ├── class-willerev-frontend.php     Shortcode [wille_reviews], schwebendes Badge
│   ├── class-willerev-block.php        Block „Google Reviews“ (wille-reviews/reviews, Server-Side-Render)
│   ├── class-willerev-selftest.php     Eingebauter Selbsttest (wp willerev selftest)
│   ├── class-willerev-cli.php          wp willerev status/refresh/flush/selftest
│   └── admin/                          Menü, Einstellungen, Design-Seite, Dashboard-Widget, Bewertungs-Bitte, Views
├── assets/
│   ├── css/willerev.css                Frontend: Layouts + Styles über CSS Custom Properties, Container Queries
│   ├── css/willerev-admin.css          Admin (Brand-Bar wie Wille GEO)
│   └── js/willerev.js                  „Mehr lesen“, Slider-Pfeile, Badge schließen (progressive enhancement)
│       ├── willerev-admin.js           Live-Vorschau + Shortcode-Generator
│       └── willerev-block.js           Block-Editor-UI (ohne Build-Schritt)
└── languages/                          nur die POT (Übersetzungen kommen von translate.wordpress.org)
```

Alles außerhalb von `wille-reviews/` ist Entwicklungswerkzeug und wird nie mit ausgeliefert. Verzeichnis-Grafiken (Icon, Banner, Screenshots) liegen in [`.wordpress-org/`](.wordpress-org/).

## Kernkonzepte

**Layouts × Styles:** Layouts (`grid`, `carousel`, `list`, `masonry`, `badge`, `social`) bestimmen das Markup, Styles (`light`, `dark`, `minimal`, `bubble`, `accent`) tauschen nur CSS Custom Properties (`.willerev--style-*`). Deshalb kann die Design-Seite jedes Layout einmal rendern und Style, Farbe, Spalten, Anzahl, Mindeststerne und Schalter rein im Browser umschalten. Akzentfarbe, Eckenradius, Spalten und Zeilenkürzung kommen als Inline-Variablen; die Textfarbe auf der Akzentfarbe wird per Kontrast berechnet (`WILLEREV_Render::ink_on()`).

**Standards & Überschreiben:** Die Design-Seite speichert die Standard-Optionen; Shortcode-Attribute und Block-Einstellungen überschreiben sie einzeln. Der Shortcode-Generator schreibt nur Optionen, die vom aktuellen Standard abweichen.

**Datenfluss:** Cron (Intervall = Cache-Dauer, Standard 12 h) → `WILLEREV_Places::refresh()` → Transient (`cache_hours + 1 h`) + Backup-Option. Cache-Miss beim Seitenaufruf: genau ein Request (Lock-Transient), parallele Aufrufe bekommen das Backup; nach einem Fehler 10 Minuten Backoff. Das Backup wird nie länger als 30 Tage genutzt (Caching-Grenze der Google Maps Platform Terms). Bei Wechsel der Place ID wird das Backup verworfen.

**Datenschutz:** Kein Request vom Browser zu Google (auch keine Bilder). API-Key nur im Header (`X-Goog-Api-Key`), Field Mask begrenzt die abgerechneten Felder. Profilbilder abschaltbar.

**Bewusst nicht enthalten:** Schema.org-Markup für die Bewertungen (Google wertet selbst eingebundene Drittanbieter-Bewertungen als Spam-Risiko).

## Lokale Entwicklung

Voraussetzung: Node ≥ 24. PHP muss nicht installiert sein.

```bash
npm ci
```

```bash
npm run setup:php
```

| Befehl | Was |
| --- | --- |
| `npm run verify` | alles, was die CI prüft (inkl. WordPress-Laufzeit- und Browsertests) |
| `npm run verify -- --fast` | statische Prüfungen + Unit-Tests (auch als pre-push-Hook) |
| `npm run playground` | WordPress mit Plugin, verbunden mit einer Google-Attrappe, auf http://127.0.0.1:9400 |
| `npm run test:e2e -- --php 7.4 --wp 6.5` | Laufzeittests gegen eine bestimmte PHP-/WP-Version |
| `npm run phpcs` / `npm run phpcbf` | Coding Standards prüfen / automatisch korrigieren |
| `npm run i18n` | POT neu erzeugen und `i18n/de_DE.po` aus `i18n/de_DE.json` bauen (Pluralformen als `[singular, plural]`) |
| `npm run build` | Release-ZIP nach `dist/` |
| `node scripts/wporg-assets.mjs` | Icon, Banner und Screenshots für wordpress.org neu erzeugen |

Git-Hooks einmalig aktivieren (pre-commit: Secret-Scan, pre-push: schnelle Checks):

```bash
git config core.hooksPath .githooks
```

**Secrets:** Echte API-Keys gehören nie ins Repo – der Google-Key steht nur in den WordPress-Einstellungen der jeweiligen Seite, Tests und Playground nutzen `TEST-KEY`. Vier Schutzschichten: `.gitignore` (`.env*`, `*.pem`, `*.key`, `auth.json`, …), [`scripts/secret-scan.mjs`](scripts/secret-scan.mjs) als pre-commit-Hook und in `npm run verify` (Google-API-Keys, Tokens, private Schlüssel; Ausgabe geschwärzt), gitleaks über die gesamte History in der CI und GitHub Secret Scanning mit Push Protection. Ist doch ein Key durchgerutscht: zuerst in der Google Cloud Console rotieren, Entfernen aus Git allein reicht nicht.

**Google-Attrappe:** [`tests/e2e/fake-google.php`](tests/e2e/fake-google.php) wird von den Blueprints als mu-plugin installiert und beantwortet `places.googleapis.com` (Key `TEST-KEY` → „Café Sonnenschein“, 4,7 ★, 312 Bewertungen; jeder andere Key → 403 wie bei Google) sowie die Profilbild-Server. So laufen Playground, Tests und Screenshots ohne echten API-Key.

Erweitern: Präfix `willerev_` / `WILLEREV_`, Text-Domain `wille-reviews`. Filter `willerev_html`, `willerev_show_floating_badge`, Action `willerev_refreshed`. Neue Strings brauchen eine Übersetzung in `i18n/de_DE.json` – die CI meldet fehlende.

## Tests

| Ebene | Wo | Was |
| --- | --- | --- |
| Unit (PHPUnit + Brain Monkey) | `tests/unit/` | API-Normalisierung, Sprache, Fehlertexte, Argument-Validierung, Auswahl/Sortierung, Farben/Kontrast, Escaping, alle 30 Layout×Style-Kombinationen, Block-Attribute, Avatar-Dateitypen, Einstellungs-Abschnitte, Bewertungs-Bitte, Selbsttest |
| Integration (echtes WordPress) | `tests/e2e/selftest.php` | Aktivierung, Request-Header/Endpoint/Field Mask, Cache, Backup, Bilder-Spiegelung, Fehler-Fallback + Backoff, 30-Tage-Grenze, Ortswechsel, Shortcode/Block, schwebendes Badge, Deaktivierung, Deinstallation |
| Plugin Check | `tests/e2e/plugin-check.php`, `scripts/plugin-check.mjs` | statische Checks des offiziellen Plugin Check + seine PHPCS-Regeln |
| Browser (Playwright) | `tests/e2e/*.spec.js` | Design-Seite (Vorschau, Generator, Speichern), Einstellungen (Status, Verbindungstest, Fehlerfall), Dashboard-Widget, Block im Editor; Frontend als Besucher: alle Layouts, keine externen Requests, Slider, „Mehr lesen“, Anker, schwebendes Badge, Block, Handy-Breite |

## Pipeline & Release

Identisch zu wordpress-geo: [`.github/workflows/ci.yml`](.github/workflows/ci.yml) (php -l 7.4–8.5, PHPCS, PHPStan Level 8, PHPUnit, ESLint, wordpress.org-Readiness, Plugin Check, E2E-Matrix, gitleaks, actionlint/zizmor, reproduzierbares ZIP) und [`.github/workflows/release.yml`](.github/workflows/release.yml) (Tag `v1.2.3` → CI → GitHub-Release → optional SVN-Deploy).

1. Version in `wille-reviews/wille-reviews.php` (Header **und** `WILLEREV_VERSION`) und `readme.txt` (`Stable tag`) anheben, Changelog-Eintrag ergänzen. `npm run check` meldet jede Abweichung.
2. Tag pushen: `git tag v1.0.0` und `git push origin v1.0.0`.
"# wordpress-google-reviews" 
