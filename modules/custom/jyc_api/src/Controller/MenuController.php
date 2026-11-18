<?php

declare(strict_types=1);

namespace Drupal\jyc_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns a Drupal menu as JSON for the Next.js frontend.
 *
 * GET /api/menu/main            -> English (default language)
 * GET /api/menu/main?lang=ar    -> Arabic titles
 * GET /api/menu/main?depth=1    -> top level only
 *
 * Each item's "url" is the path WITHOUT a language prefix ("/about"), so the
 * frontend adds "/ar" itself. External links are returned as-is.
 */
final class MenuController extends ControllerBase {

  public function __construct(
    private readonly MenuLinkTreeInterface $menuTree,
    private readonly AliasManagerInterface $aliasManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('menu.link_tree'),
      $container->get('path_alias.manager'),
    );
  }

  public function menu(string $menu, Request $request): CacheableJsonResponse {
    $menu_entity = $this->entityTypeManager()->getStorage('menu')->load($menu);
    if (!$menu_entity) {
      throw new NotFoundHttpException();
    }

    $languages = $this->languageManager()->getLanguages();
    $langcode = (string) $request->query->get('lang', '');
    if (!isset($languages[$langcode])) {
      $langcode = $this->languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $depth = $request->query->get('depth');
    if (is_numeric($depth) && (int) $depth > 0) {
      $parameters->setMaxDepth((int) $depth);
    }

    $tree = $this->menuTree->load($menu, $parameters);
    $tree = $this->menuTree->transform($tree, [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ]);

    $cache = (new CacheableMetadata())
      ->addCacheableDependency($menu_entity)
      ->addCacheTags(['config:system.menu.' . $menu])
      ->addCacheContexts([
        'url.query_args:lang',
        'url.query_args:depth',
        'languages:' . LanguageInterface::TYPE_CONTENT,
      ]);

    $response = new CacheableJsonResponse([
      'menu' => $menu,
      'langcode' => $langcode,
      'items' => $this->buildItems($tree, $langcode, $cache),
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

  /**
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   */
  private function buildItems(array $tree, string $langcode, CacheableMetadata $cache): array {
    $items = [];
    foreach ($tree as $element) {
      assert($element instanceof MenuLinkTreeElement);
      if ($element->access !== NULL) {
        $cache->addCacheableDependency($element->access);
        if (!$element->access->isAllowed()) {
          continue;
        }
      }

      $link = $element->link;
      [$title, $description] = $this->translatedText($link, $langcode, $cache);
      [$url, $external] = $this->path($link->getUrlObject(), $langcode);

      $items[] = [
        'id' => $link->getPluginId(),
        'title' => $title,
        'description' => $description,
        'url' => $url,
        'external' => $external,
        'weight' => (int) $link->getWeight(),
        'expanded' => $link->isExpanded(),
        'children' => $element->subtree
          ? $this->buildItems($element->subtree, $langcode, $cache)
          : [],
      ];
    }
    return $items;
  }

  /**
   * Title and description in the requested language.
   */
  private function translatedText(MenuLinkInterface $link, string $langcode, CacheableMetadata $cache): array {
    $entity_id = $link->getMetaData()['entity_id'] ?? NULL;
    if ($link->getProvider() === 'menu_link_content' && $entity_id) {
      $entity = $this->entityTypeManager()->getStorage('menu_link_content')->load($entity_id);
      if ($entity) {
        $cache->addCacheableDependency($entity);
        if ($entity->hasTranslation($langcode)) {
          $entity = $entity->getTranslation($langcode);
        }
        return [
          (string) $entity->label(),
          (string) ($entity->get('description')->value ?? ''),
        ];
      }
    }
    // Static links (e.g. "Home" from a .links.menu.yml file): translate the
    // title with interface translations in the requested language.
    $definition = $link->getPluginDefinition();
    return [
      $this->translateString($definition['title'] ?? $link->getTitle(), $langcode),
      $this->translateString($definition['description'] ?? $link->getDescription(), $langcode),
    ];
  }

  private function translateString(mixed $text, string $langcode): string {
    if ($text instanceof TranslatableMarkup) {
      return (string) new TranslatableMarkup(
        $text->getUntranslatedString(),
        $text->getArguments(),
        ['langcode' => $langcode] + $text->getOptions(),
      );
    }
    return (string) ($text ?? '');
  }

  /**
   * Path without base path or language prefix, e.g. "/about".
   *
   * @return array{0: string|null, 1: bool}
   */
  private function path(Url $url, string $langcode): array {
    if ($url->isExternal()) {
      return [$url->toString(), TRUE];
    }

    if ($url->isRouted()) {
      $route = $url->getRouteName();
      if ($route === '<front>') {
        $path = '/';
      }
      elseif (in_array($route, ['<nolink>', '<none>', '<button>'], TRUE)) {
        return [NULL, FALSE];
      }
      else {
        $path = $this->aliasManager->getAliasByPath('/' . $url->getInternalPath(), $langcode);
      }
    }
    else {
      // Unrouted "base:about" -> "/about".
      $path = '/' . ltrim(substr($url->getUri(), strlen('base:')), '/');
    }

    $options = $url->getOptions();
    if (!empty($options['query'])) {
      $path .= '?' . http_build_query($options['query']);
    }
    if (!empty($options['fragment'])) {
      $path .= '#' . $options['fragment'];
    }
    return [$path, FALSE];
  }

}
