<?php
/**
 * @file
 * Contains \Drupal\menu_trail_by_path\MenuTrailByPathLinkTree.
 */

namespace Drupal\menu_trail_by_path;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeElement;

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
    $active_trail = ['' => ''];

    // Retrieve the menu tree
    $menu_parameters = new MenuTreeParameters();
    $tree            = \Drupal::menuTree()->load($menu_name, $menu_parameters);

    // Complete the active trail.
    if ($parents = $this->getMenuLinkPluginIdsByMenuLinkTreeElements($tree)) {
      $active_trail = $parents + $active_trail;
    }

    return $active_trail;
  }

  /**
   * @param array MenuLinkTreeElement[]
   * @return array
   */
  private function getMenuLinkPluginIdsByMenuLinkTreeElements(array $menuLinkTreeElements) {
    $menuLinkPluginIds = [];
    if ($requestUrlPath = $this->getRequestUrlPath()) {
      foreach ($menuLinkTreeElements as $menuLinkRoute => $menuLinkTreeElement) {
        $menuPath = $menuLinkTreeElement->link->getUrlObject()->toString();

        if (strpos($requestUrlPath, $menuPath) === 0 && ($menuLinkRoute != 'standard.front_page' || ($menuLinkRoute == 'standard.front_page' && ($menuLinkTreeElement->depth > 1 || $menuPath == $requestUrlPath)))) {
          $menuLinkPluginIds[] = $menuLinkRoute;
          $menuLinkPluginIds = array_merge($this->getMenuLinkPluginIdsByMenuLinkTreeElements($menuLinkTreeElement->subtree), $menuLinkPluginIds);
        }
      }
    }

    return $menuLinkPluginIds;
  }

  /**
   * @return mixed
   */
  private function getRequestUrlPath() {
    $uri = \Drupal::request()->getRequestUri();
    return parse_url($uri, PHP_URL_PATH);
  }
}
