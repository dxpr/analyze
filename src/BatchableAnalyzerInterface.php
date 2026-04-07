<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for analyze plugins that support batch processing.
 *
 * Plugins implement this interface to opt-in to centralized batch processing
 * via the admin UI and Drush CLI. The centralized batch system discovers all
 * plugins implementing this interface and provides uniform batch operations.
 *
 * @see \Drupal\analyze\AnalyzePluginBase
 * @see \Drupal\analyze\Service\AnalyzeBatchService
 */
interface BatchableAnalyzerInterface {

  /**
   * Process a single entity for this analyzer.
   *
   * Called by the centralized batch system. Each analyzer is responsible for
   * its own storage of results. The method should perform the full analysis
   * and persist any results.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to analyze.
   * @param bool $force_refresh
   *   Whether to re-analyze even if results already exist.
   *
   * @return bool
   *   TRUE if the entity was processed, FALSE if skipped.
   */
  public function processEntity(EntityInterface $entity, bool $force_refresh = FALSE): bool;

  /**
   * Check whether analysis results already exist for an entity.
   *
   * Used to skip already-analyzed entities when force_refresh is FALSE.
   * This may validate content/config hashes and can be slow.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if results exist for this entity.
   */
  public function hasResults(EntityInterface $entity): bool;

  /**
   * Count entities that have any stored results for a given bundle.
   *
   * Fast DB-level count, does not load or render entities.
   * Does not validate content hashes — counts any row.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle machine name.
   *
   * @return int
   *   Number of entities with at least one result row.
   */
  public function countAnalyzedEntities(string $entity_type_id, string $bundle): int;

}
