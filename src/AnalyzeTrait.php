<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

trait AnalyzeTrait {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|NULL
   */
  protected EntityTypeManagerInterface|NULL $entityTypeManager = NULL;

  /**
   * Route Match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|null
   */
  protected RouteMatchInterface|NULL $routeMatch = NULL;

  /**
   * Helper to set and get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The Entity Type Manager.
   */
  private function entityTypeManager(): EntityTypeManagerInterface {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }

    return $this->entityTypeManager;
  }

  /**
   * Helper to get and set the RouteMatchInterface.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   Route Match Interface.
   */
  private function routeMatch(): RouteMatchInterface {
    if (!$this->routeMatch) {
      $this->routeMatch = \Drupal::routeMatch();
    }

    return $this->routeMatch;
  }

  /**
   * Helper to return an entity from parameters.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity, or NULL on error.
   */
  private function getEntity(string $entity_type): ?ContentEntityInterface {
    return $this->routeMatch()->getParameter($entity_type);
  }
}