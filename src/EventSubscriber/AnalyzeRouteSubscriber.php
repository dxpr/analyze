<?php

declare(strict_types=1);

namespace Drupal\analyze\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\analyze\AnalyzePluginManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class AnalyzeRouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

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
    if ($plugins = $this->pluginManagerAnalyze->getDefinitions()) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
        if ($entity_type->hasLinkTemplate('canonical')) {
          $link = $entity_type->getLinkTemplate('canonical');
          // Upcast the entity so its own view access can be checked.
          $parameters = [$entity_type->id() => ['type' => 'entity:' . $entity_type->id()]];

          foreach ($plugins as $plugin_id => $plugin) {
            $route = new Route($link . '/analyze/' . $plugin_id);
            $route
              ->setDefaults([
                '_controller' => 'Drupal\analyze\Controller\AnalyzeController::analyze',
                'plugin' => $plugin_id,
                'entity_type' => $entity_type->id(),
                'full_report' => TRUE,
                '_title' => $plugin['label'] . ' Full Report',
              ])
              ->setOption('_admin_route', TRUE)
              ->setOption('parameters', $parameters)
              ->setRequirement('_analyze_access', 'TRUE')
              ->setRequirement('_entity_access', $entity_type->id() . '.view');

            $collection->add('analyze.' . $entity_type->id() . '.' . $plugin_id, $route);
          }

          $route = new Route($link . '/analyze');
          $route
            ->setDefaults([
              '_controller' => 'Drupal\analyze\Controller\AnalyzeController::analyze',
              'entity_type' => $entity_type->id(),
              '_title_callback' => 'Drupal\analyze\Controller\AnalyzeController::analyzeTitle',
            ])
            ->setOption('_admin_route', TRUE)
            ->setOption('parameters', $parameters)
            ->setRequirement('_analyze_access', 'TRUE')
            ->setRequirement('_entity_access', $entity_type->id() . '.view');

          $collection->add('entity.' . $entity_type->id() . '.analyze', $route);
        }
      }
    }
  }

}
