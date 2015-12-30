<?php
/**
 * @file
 * Contains \Drupal\menu_trail_by_path\MenuTrailByPathLinkTree.
 */

namespace Drupal\menu_trail_by_path;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Overrides the class for the file entity normalizer from HAL.
 */
class MenuTrailByPathActiveTrail extends MenuActiveTrail {

  /**
   * Helper method for ::getActiveTrailIds().
   */
  protected function doGetActiveTrailIds($menu_name) {
    // Parent ids; used both as key and value to ensure uniqueness.
    // We always want all the top-level links with parent == ''.
    $active_trail = array('' => '');

    // If a link in the given menu indeed matches the route, then use it to
    // complete the active trail.
    if ($active_link = $this->getActiveLink($menu_name)) {
      if ($parents = $this->menuLinkManager->getParentIds($active_link->getPluginId())) {
        $active_trail = $parents + $active_trail;
      }
    }
    else {
      // No matching link, so check paths against current link.
      $path = $this->getCurrentPathAlias();

      $menu_parameters = new MenuTreeParameters();
      $tree = \Drupal::menuTree()->load($menu_name, $menu_parameters);

      foreach ($tree as $menu_link_route => $menu_link) {
        $menu_url = $menu_link->link->getUrlObject();
        $menu_path = $menu_url->toString();

        // Check if this item's path exists in the current path.
        if (strpos($path, $menu_path) === 0) {

          if ($this->pathIsMoreSimilar($item_path, $found)) {
            $parents = array($menu_link_route => $menu_link_route);
            $active_trail = $parents + $active_trail;
          }
        }
      }
    }

    return $active_trail;
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
