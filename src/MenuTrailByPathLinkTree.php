<?php
/**
 * @file
 * Contains \Drupal\menu_trail_by_path\MenuTrailByPathLinkTree.
 */

namespace Drupal\menu_trail_by_path;

use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Overrides the class for the file entity normalizer from HAL.
 */
class MenuTrailByPathLinkTree extends MenuLinkTree {

  /**
   * Checks all the items if anyone starts with the same path and thereby should
   * have set an active trail.
   *
   * If more than one item is matched, the deepest is used.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   A data structure representing the tree, as returned from
   *   MenuLinkTreeInterface::load().
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_access_cacheability
   *   Internal use only. The aggregated cacheability metadata for the access
   *   results across the entire tree. Used when rendering the root level.
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_link_cacheability
   *   Internal use only. The aggregated cacheability metadata for the menu
   *   links across the entire tree. Used when rendering the root level.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   *
   * @throws \DomainException
   *
   * @inheritdoc
   */
  protected function buildItems(array $tree, CacheableMetadata &$tree_access_cacheability, CacheableMetadata &$tree_link_cacheability) {
    $items = parent::buildItems($tree, $tree_access_cacheability, $tree_link_cacheability);
    $alias = $this->getCurrentPathAlias();
    $active = FALSE;
    $active_key = '';

    foreach ($items as $key => $item) {
      $item_path = $item['url']->toString();

      // If this items path exists in the current path.
      if (strpos($alias, $item_path) === 0) {
        if ($this->pathIsMoreSimilar($item_path, $active)) {
          $active_key = $key;
          $active = $item;
        }
      }
    }

    // If an item was found, set the active trail class.
    if ($active) {
      $items[$active_key]['attributes']->addClass('menu-item--active-trail');
    }

    return $items;
  }

  /**
   * Get the path alias for the current path.
   *
   * @return string
   *   The path alias for the current path.
   */
  private function getCurrentPathAlias() {
    $path = \Drupal::service('path.current')->getPath();
    return \Drupal::service('path.alias_manager')->getAliasByPath('/' . trim($path, '/'));
  }

  /**
   * Compare the similarity of the current path alias with a path provided.
   *
   * @param string $path
   *   The path to compare with.
   *
   * @return int
   *   The number of characters in common from the beginning.
   */
  private function getSimilarityWithCurrentPath($path) {
    $alias = $this->getCurrentPathAlias();
    $key = 0;
    while (isset($alias[$key]) && isset($path[$key]) && $alias[$key] === $path[$key]) {
      $key++;
    }
    return $key;
  }

  /**
   * Check whether a path is more similar than the current active item.
   *
   * @param string $item_path
   *   The path to compare.
   * @param array $active
   *   The currently active menu link element.
   *
   * @return bool
   *   TRUE if the path being compared is more similar.
   */
  private function pathIsMoreSimilar($item_path, $active) {

    // There is no currently active path, so
    if (empty($active)) {
      return TRUE;
    }

    if ($this->getSimilarityWithCurrentPath($item_path) > $this->getSimilarityWithCurrentPath($active['url']->toString())) {
      return TRUE;
    }

    return FALSE;
  }
}
