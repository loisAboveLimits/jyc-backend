# Shortcode

## Introduction

Shortcodes are macros you place directly in the text of your content — similar in
spirit to WordPress-style shortcodes, but built the Drupal way. Each macro is expanded
and themed through Drupal's render/theme layer when the content is displayed.

This module provides:

- A text format filter (`Shortcodes`) that parses `[tag attr="value"]...[/tag]` markup
  in filtered text and hands each tag to the plugin that registered it.
- A `Shortcode` plugin type, so any module can define new tags. Plugins are discovered
  via the `#[Shortcode]` PHP attribute (annotation-based plugins from older modules are
  still supported for backward compatibility).
- Nesting support out of the box, including a tag nested inside another instance of
  itself — no special recursion handling required in your plugin.

The base API ships with no bundled tags of its own. Install the **Shortcode Basic
Tags** submodule for a small starter set (`quote`, `img`, `highlight`, `button`,
`dropcap`, `item`, `clear`, `link`, `block`, `random`), or write your own — see
**Creating custom shortcodes** below.

For a full list of projects that provide additional shortcode tags, see the
[Shortcode project page](https://www.drupal.org/project/shortcode).


## Requirements

- Drupal core's **Filter** module (`drupal:filter`).
- PHP 8.3+, Drupal 11.1+ or 12.x (`core_version_requirement: ^11.1 || ^12`).

No external libraries or Composer dependencies beyond Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. See
[Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules)
for further information.

```
composer require drupal/shortcode
drush en shortcode
```

Enable `shortcode_basic_tags` as well if you want the bundled starter tags, and/or
`shortcode_example` if you want a working reference plugin to copy from.


## Configuration

1. Go to **Configuration » Content authoring » Text formats and editors**
   (`admin/config/content/formats`) and edit the text format you want shortcodes
   enabled on.
2. Enable the **Shortcodes** filter.
3. If the format uses a WYSIWYG editor (CKEditor, etc.), also enable **Shortcodes -
   HTML corrector** and make sure it runs *before* **Shortcodes** in the filter
   processing order. WYSIWYG editors tend to wrap shortcode brackets in stray `<p>`/
   `<br>` tags; the corrector undoes that before the parser runs. Skip it for
   plain-text or non-WYSIWYG formats.
4. If you installed `shortcode_basic_tags`, individual tags can be enabled, disabled,
   and reordered per text format on the same filter settings page.


## Creating custom shortcodes

A shortcode plugin is a class extending `Drupal\shortcode\Plugin\ShortcodeBase`,
attributed with `#[Shortcode]`, living in `src/Plugin/Shortcode/`. At minimum,
implement `process()` (renders the tag) and `tips()` (the filter-tips help text).

```php
namespace Drupal\my_module\Plugin\Shortcode;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\shortcode\Attribute\Shortcode;
use Drupal\shortcode\Plugin\ShortcodeBase;

#[Shortcode(
  id: 'col',
  title: new TranslatableMarkup('Bootstrap column'),
  description: new TranslatableMarkup('Builds a div with bootstrap column size classes'),
)]
class BootstrapColumnShortcode extends ShortcodeBase {

  #[\Override]
  public function process(array $attributes, string $text, string $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED): string {
    $attributes = $this->getAttributes(['class' => '', 'md' => ''], $attributes);
    $class = $attributes['md'] ? $this->addClass($attributes['class'], 'col-md-' . $attributes['md']) : $attributes['class'];
    return '<div class="' . $class . '">' . $text . '</div>';
  }

  #[\Override]
  public function tips($long = FALSE): string {
    return (string) $this->t('Wraps your content in a div with Bootstrap column classes.');
  }

}
```

This renders `[col md="4"]content[/col]` as `<div class="col-md-4">content</div>`. See
`shortcode_example/src/Plugin/Shortcode/BootstrapColumnShortcode.php` for the complete,
working version, and `shortcode_basic_tags/src/Plugin/Shortcode/` for further examples
(including one, `ImageShortcode`, that injects the `MediaUrlResolver` service to
resolve media entities and image styles).

If your plugin needs to resolve a media entity, file URL, or image style from a path
or media ID, inject `Drupal\shortcode\MediaUrlResolverInterface` (or use
`Drupal\shortcode\Plugin\MediaUrlResolverTrait`) rather than duplicating that logic.


## Upgrading from 2.x

Version 3.0.0 is a breaking release for anyone maintaining their own shortcode
plugins (constructor signature change on `ShortcodeBase`, a few relocated helper
methods). See `UPGRADING.md` in this module for the full list of changes and how to
convert an existing plugin — including a prompt you can hand to an AI coding
assistant to do the conversion for you.


## Maintainers

Current maintainers:

- See the [Shortcode project page](https://www.drupal.org/project/shortcode) for the
  full, up-to-date maintainer list.
