<?php

declare(strict_types=1);

namespace Drupal\analyze\Access;

use Drupal\analyze\AnalyzeTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access against enabled plugins for Analyze report routes.
 */
final class AnalyzeAccessAccessChecker implements AccessInterface {

  use AnalyzeTrait;

  /**
   * Access callback for Analyze routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged in user.
   * @param string $entity_type
   *   The entity type the route relates to.
   * @param string|null $plugin
   *   The current plugin being views (optional).
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(Route $route, AccountInterface $account, string $entity_type, string|null $plugin = NULL): AccessResult {
    if ($entity = $this->getEntity($entity_type)) {
      if ($account->hasPermission('view analyze reports')) {
        $return = AccessResult::forbidden('Entity not enabled for Analyze reporting.');

        if ($config = $this->analyzeConfig()) {
          if ($settings = $config->get('status')) {
            if (!empty($settings[$entity_type])) {
              if (!empty($settings[$entity_type][$entity->bundle()])) {

                // If we are looking at a specific plugin, check it as been
                // enabled before granting access.
                if ($plugin) {
                  if (!empty($settings[$entity_type][$entity->bundle()][$plugin])) {
                    if ($plugins = $this->getPlugins([$plugin])) {

                      $analyze = reset($plugins);

                      // If the plugin modifies the full report URL, we should
                      // deny access to our default route.
                      if (!$analyze->fullReportUrlOverridden($entity)) {

                        // @todo Allow plugins to add access conditions.
                        $return = AccessResult::allowed();
                      }
                    }
                  }
                }
                else {
                  $return = AccessResult::allowed();
                }
              }
            }
          }
        }
      }
      else {
        $return = AccessResult::forbidden('User does not have the required access level.');
      }
    }
    else {
      $return = AccessResult::forbidden('Entity does not exist.');
    }

    return $return;
  }

}
