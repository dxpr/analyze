<?php

declare(strict_types=1);

namespace Drupal\analyze\EventSubscriber;

use Drupal\analyze\AnalyzePluginManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class AnalyzeRouteSubscriber extends RouteSubscriberBase {

  /**
   * Constructs an AnalyzeRouteSubscriber object.
   */
  public function __construct(
    private readonly AnalyzePluginManager $pluginManagerAnalyze,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->hasLinkTemplate('canonical')) {
        $link = $entity_type->getLinkTemplate('canonical');

        foreach ($this->pluginManagerAnalyze->getDefinitions() as $plugin_id) {
          $route = new Route($link . '/' . $plugin_id);
          $route
            ->setDefaults([
              '_controller' => '\Drupal\analyze\Controller::analyze',
              'plugin' => $plugin_id,
            ])
            ->setOption('_admin_route', TRUE);

          $collection->add($entity_type->id() . '.' . $plugin_id, $route);
        }

        $route = new Route($link . '/analyze');

        $route
          ->setDefaults([
            '_controller' => '\Drupal\analyze\Controller::analyze',
          ])
          ->setOption('_admin_route', TRUE);

        $collection->add($entity_type->id() . '.analyze', $route);

      }

    }
  }

}