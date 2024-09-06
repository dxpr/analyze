<?php

declare(strict_types=1);

namespace Drupal\analyze\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 * Usage example:
 * @code
 * foo.example:
 *   path: '/example/{parameter}'
 *   defaults:
 *     _title: 'Example'
 *     _controller: '\Drupal\analyze\Controller\AnalyzeController'
 *   requirements:
 *     _analyze_access: 'some value'
 * @endcode
 */
final class AnalyzeAccessAccessChecker implements AccessInterface {

  /**
   * Access callback.
   *
   * @DCG
   * Drupal does some magic when resolving arguments for this callback. Make
   * sure the parameter name matches the name of the placeholder defined in the
   * route, and it is of the same type.
   * The following additional parameters are resolved automatically.
   *   - \Drupal\Core\Routing\RouteMatchInterface
   *   - \Drupal\Core\Session\AccountInterface
   *   - \Symfony\Component\HttpFoundation\Request
   *   - \Symfony\Component\Routing\Route
   */
  public function access(Route $route, string|null $plugin, string|null $entity_type): AccessResult {
    return AccessResult::allowed();
  }

}
