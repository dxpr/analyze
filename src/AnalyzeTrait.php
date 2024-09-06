<?php&#10&#10declare(strict_types=1);&#10&#10namespace Drupal\analyze;&#10&#10use Drupal\Core\Entity\ContentEntityInterface;&#10use Drupal\Core\Entity\EntityTypeManagerInterface;&#10use Drupal\Core\Routing\RouteMatchInterface;&#10&#10trait AnalyzeTrait {&#10&#10  /**&#10   * Entity Type Manager.&#10   *&#10   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|NULL&#10   */&#10  protected EntityTypeManagerInterface|NULL $entityTypeManager = NULL;&#10&#10  /**&#10   * Route Match interface.&#10   *&#10   * @var \Drupal\Core\Routing\RouteMatchInterface|null&#10   */&#10  protected RouteMatchInterface|NULL $routeMatch = NULL;&#10&#10  /**&#10   * Helper to set and get the entity type manager.&#10   *&#10   * @return \Drupal\Core\Entity\EntityTypeManagerInterface&#10   *   The Entity Type Manager.&#10   */&#10  private function entityTypeManager(): EntityTypeManagerInterface {&#10    if (!$this->entityTypeManager) {&#10      $this->entityTypeManager = \Drupal::entityTypeManager();&#10    }&#10&#10    return $this->entityTypeManager;&#10  }&#10&#10  /**&#10   * Helper to get and set the RouteMatchInterface.&#10   *&#10   * @return \Drupal\Core\Routing\RouteMatchInterface&#10   *   Route Match Interface.&#10   */&#10  private function routeMatch(): RouteMatchInterface {&#10    if (!$this->routeMatch) {&#10      $this->routeMatch = \Drupal::routeMatch();&#10    }&#10&#10    return $this->routeMatch;&#10  }&#10&#10  /**&#10   * Helper to return an entity from parameters.&#10   *&#10   * @param string $entity_type&#10   *   The entity type.&#10   *&#10   * @return \Drupal\Core\Entity\ContentEntityInterface|null&#10   *   The entity, or NULL on error.&#10   */&#10  private function getEntity(string $entity_type): ?ContentEntityInterface {&#10    return $this->routeMatch()->getParameter($entity_type);&#10  }&#10}