# Upgrading your Shortcode plugins for 3.0.0 (Drupal 12 refactor)

Audience: developers maintaining a custom `Plugin\Shortcode` plugin (in this module or
your own) against the `shortcode` module. This guide applies to the 3.0.x branch; it does
not affect the 2.0.x branch, which keeps the pre-refactor API.

## Do I need to change anything?

**If your plugin only uses the `@Shortcode(...)` annotation, declares no `__construct()`
of its own, and never called `getUrlFromPath()`, `getMidFromPath()`, `getMediaFileUrl()`,
`getMediaField()`, or `getImageProperties()` — no changes required.** Annotation discovery
still works side-by-side with the new `#[Shortcode(...)]` attribute, and none of the
breaking changes below apply to a plugin that only overrides `process()`/`tips()`.

## Breaking changes

### 1. `ShortcodeBase::__construct()` no longer takes a file URL generator

Before:
```php
public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, FileUrlGeneratorInterface $file_url_generator) {
  parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer, $file_url_generator);
}
```
After:
```php
public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer) {
  parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
}
```
If your plugin has no constructor of its own, there's nothing to do. If it does and calls
`parent::__construct()` with 5 arguments, drop the 5th (`$file_url_generator`) — and drop
the parameter from your own constructor too, unless you use it for something unrelated to
media resolution, in which case keep injecting it yourself, just stop passing it to the
parent.

### 2. `getMediaFileUrl()`, `getMediaField()`, `getImageProperties()` removed from `ShortcodeBase`

These three methods are gone. Inject `Drupal\shortcode\MediaUrlResolverInterface`
(service ID `shortcode.media_url_resolver`, autowireable by type-hinting the interface)
and call:

| Old | New |
|---|---|
| `$this->getMediaFileUrl($mid)` | `$this->mediaUrlResolver->getFileUrl($mid)` |
| `$this->getImageProperties($mid)` | `$this->mediaUrlResolver->getImageProperties($mid)` |
| (n/a — plugins used to load `image_style` storage themselves) | `$this->mediaUrlResolver->getImageStyleUrl($imageStyleId, $uri)` |

### 3. `getUrlFromPath()` / `getMidFromPath()` moved to `MediaUrlResolverTrait`

These are no longer directly callable on `ShortcodeBase`. If your `process()` calls
`$this->getUrlFromPath(...)` (typically for a `path`/`media_file_url` attribute pattern),
add the trait and inject the resolver yourself:

```php
use Drupal\Core\Render\RendererInterface;
use Drupal\shortcode\MediaUrlResolverInterface;
use Drupal\shortcode\Plugin\MediaUrlResolverTrait;
use Drupal\shortcode\Plugin\ShortcodeBase;

class YourShortcode extends ShortcodeBase {

  use MediaUrlResolverTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    protected MediaUrlResolverInterface $mediaUrlResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
  }

}
```
No `create()` method needed — `RendererInterface` and `MediaUrlResolverInterface` both
resolve automatically via container autowiring (FQCN service aliases already exist for
both). If your plugin needs additional services of its own, add them as further
constructor parameters the same way; PluginBase's autowiring resolves each by its
type-hint.

## Recommended, not required

- Convert `@Shortcode(...)` to `#[Shortcode(...)]` (both are discovered at once; the
  attribute is the forward-looking form and the annotation may eventually be deprecated
  in a future major version).
- Type `process()`'s `$text`/`$langcode` params and add a `: string` return type, matching
  `ShortcodeInterface::process()`. **Do NOT add a `bool` type hint to `tips()`'s `$long`
  parameter** — `ShortcodeInterface::tips()` and `ShortcodeBase::tips()` both leave it
  untyped on purpose; narrowing it in your override is a real LSP violation that phpstan
  will flag as a non-ignorable error (`method.childParameterType`).
- Delete a hand-written `create()` method if every constructor parameter is resolvable via
  autowiring (any service with an FQCN alias — check `core.services.yml` or the owning
  module's `*.services.yml`).
- If your plugin calls `$this->renderer->renderInIsolation($array)` directly instead of
  `$this->render($array)`, switch to `$this->render($array)` — it already casts the
  `Markup` object `renderInIsolation()` returns down to a plain `string`, which matters if
  you type your `process()` return as `string`.

## Using an AI coding assistant to convert your plugin

Paste the block below into your assistant, replacing the file path with your own plugin's:

```
Convert this Drupal Shortcode plugin (web/modules/custom/YOUR_MODULE/src/Plugin/Shortcode/YourShortcode.php)
to be compatible with shortcode 3.0.0's Drupal 12 refactor. Read
web/modules/custom/shortcode/UPGRADING.md first for the full list of breaking changes,
then:

1. If the plugin has its own __construct() that calls parent::__construct() with a
   FileUrlGeneratorInterface as the 5th argument, remove that argument from the
   parent call. If the plugin doesn't use that file URL generator for anything else,
   remove it from the constructor's own parameter list too.
2. If the plugin calls $this->getMediaFileUrl(), $this->getMediaField(), or
   $this->getImageProperties(), inject Drupal\shortcode\MediaUrlResolverInterface via
   the constructor and replace those calls with $this->mediaUrlResolver->getFileUrl(),
   (there is no direct getMediaField() replacement — it's now private inside the
   resolver), and $this->mediaUrlResolver->getImageProperties() respectively.
3. If the plugin calls $this->getUrlFromPath() or $this->getMidFromPath(), add
   `use Drupal\shortcode\Plugin\MediaUrlResolverTrait;` and `use MediaUrlResolverTrait;`
   inside the class, and inject MediaUrlResolverInterface via the constructor if not
   already done in step 2.
4. Delete any hand-written static create(ContainerInterface $container, ...) method if,
   after the above changes, every constructor parameter is a service resolvable by its
   interface type-hint (check web/core/core.services.yml and this module's
   *.services.yml for an FQCN alias matching each parameter's type).
5. Do not add a `bool` type hint to tips()'s $long parameter — leave it untyped. Do not
   change any other runtime behavior. Do not touch the @Shortcode/#[Shortcode]
   attribute or annotation unless asked.
6. After editing, verify with: `ddev exec drush cache:rebuild` (catches DI wiring
   errors), then exercise the shortcode in a real page/text format to confirm rendered
   output is unchanged from before the conversion.

Show me a diff of your proposed changes before applying them.
```
