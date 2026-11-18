# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/). Older releases predate
this file and are not documented here.

## 3.0.0 - 2026-09-03

Drupal 12 coding standards refactor. **Breaking changes for third-party plugin
developers — see `UPGRADING.md` for what changed and how to convert your plugins.**

### Added
- PHP attribute-based plugin discovery (`#[Shortcode(...)]`, `#[Filter(...)]`), alongside
  the existing annotations, which stay fully supported.
- `MediaUrlResolver` service (`Drupal\shortcode\MediaUrlResolverInterface`) and
  `MediaUrlResolverTrait`, consolidating media file URL, image property, and image style
  lookups behind one injectable service.
- Unit test coverage for `ShortcodeService::process()`
  (`tests/src/Unit/ShortcodeServiceProcessTest.php`).
- `#[\Override]` attributes on every method that overrides a parent class method or
  implements an interface method.

### Changed
- Constructor property promotion and container autowiring throughout; hand-written
  `create()` methods removed wherever autowiring already covers every dependency.
- `declare(strict_types=1)` and full parameter/return type hints added across the module.
- `ShortcodeService`'s internal caching switched from `drupal_static()` to instance
  properties (the service is already a container singleton).
- `ShortcodeService::process()` decomposed into smaller named private methods
  (behavior-preserving; guarded by the new unit test).
- `core_version_requirement` raised to `^11.1 || ^12`.

### Removed
- Legacy procedural `#[LegacyHook]` shims in `shortcode.module` and
  `shortcode_basic_tags.module` — superseded by the OOP `#[Hook]` methods, which already
  fire without them.
- Dead code: `ShortcodeService::isBool()`, stray commented-out debug lines.
- Deprecated `Drupal\Core\Language\Language` usage, replaced with `LanguageInterface`.

### Fixed
- A few pre-existing latent bugs surfaced and fixed while adding strict types: a
  `Markup`-vs-`string` return type mismatch in `ShortcodeBase::render()`, a `NULL` passed
  as a shortcode's text for self-closing tags, and an unguarded `TypeError` risk when
  resolving a deleted media entity's file URL.

See `UPGRADING.md` for the full plugin-conversion guide, including an AI-assistant prompt
you can use to convert your own plugin automatically.

## 2.0.4 - 2026-09-03

Patch release on the 2.0.x branch, before the 3.0.0 fork — no breaking changes.

### Fixed
- `RandomShortcode` generated unescaped `<`/`>` characters, which got parsed as markup
  instead of counted as text (Issue #3514294 follow-up).
- `ShortcodeTest`'s `setUp()` failed on Drupal 10.3+/11, since the `standard` install
  profile no longer creates the `page` content type itself (moved to the
  `core/recipes/page_content_type` recipe) and `BrowserTestBase` never applies profile
  recipes; the test now creates the content type directly.
- `testRandomShortcode` assertion messages incorrectly asserted a false outcome.
- Various deprecation notices in `ShortcodeService`.
- Various PHPCS- and PHPStan-reported issues across `BlockShortcode`, `ImageShortcode`,
  the Filter plugins, `ShortcodeBase`, and `ShortcodeService`, plus Twig template fixes
  (Issue #3514294).

### Changed
- `cspell-project-words.txt` renamed to `.cspell-project-words.txt` (leading dot), so
  drupal.org's real CI cspell job actually picks it up — it was previously invisible to
  CI under the un-dotted filename.
