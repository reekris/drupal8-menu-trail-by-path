<?php
/**
 * @file
 * Contains \Drupal\menu_trail_by_path\MenuTrailByPathServiceProvider.
 */

namespace Drupal\menu_trail_by_path;

use \Drupal\Core\DependencyInjection\ServiceProviderBase;
use \Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Overrides the class for the menu link tree.
 */
class MenuTrailByPathServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.link_tree');
    $definition->setClass('Drupal\menu_trail_by_path\MenuTrailByPathLinkTree');
  }

}
