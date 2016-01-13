<?php
/**
 * @file
 * Contains \Drupal\menu_trail_by_path\MenuTrailByPathLinkTree.
 */

namespace Drupal\menu_trail_by_path;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Url;

/**
 * Overrides the class for the file entity normalizer from HAL.
 */
class MenuTrailByPathActiveTrail extends MenuActiveTrail {

  /**
   * Constructs a Drupal\menu_trail_by_path\MenuTrailByPathLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumb_builder
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, RouteMatchInterface $route_match, CacheBackendInterface $cache, LockBackendInterface $lock, BreadcrumbBuilderInterface $breadcrumb_builder) {
    parent::__construct($menu_link_manager, $route_match, $cache, $lock);
    $this->breadcrumbBuilder = $breadcrumb_builder;
  }

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
      // Try to get active trail from breadcrumbs (which uses path internally)
      $breadcrumbs = $this->breadcrumbBuilder->build($this->routeMatch);
      $links = $breadcrumbs->getLinks();
      if (!empty($links)) {
        $lastLink = end($links);
        $url = $lastLink->getUrl();

        if ($active_link = $this->getActiveLinkByUrl($url, $menu_name)) {
          if ($parents = $this->menuLinkManager->getParentIds($active_link->getPluginId())) {
            $active_trail = $parents + $active_trail;
          }
        }
      }
    }

    return $active_trail;
  }

  /**
   * Fetches a menu link which matches
   * the route name and parameters of the url object and menu name.
   *
   * @param \Drupal\Core\Url $url
   *   The url object to use to find the active link.
   *
   * @param string|NULL $menu_name
   *   (optional) The menu within which to find the active link. If omitted, all
   *   menus will be searched.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|NULL
   *   The menu link for the given route name, parameters and menu, or NULL if
   *   there is no matching menu link or the current user cannot access the
   *   current page (i.e. we have a 403 response).
   */
  public function getActiveLinkByUrl(Url $url, $menu_name = NULL) {
    $found = NULL;
    $links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
    if ($links) {
      $found = reset($links);
    }
    return $found;
  }
}
