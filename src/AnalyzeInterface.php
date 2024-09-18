<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Interface for analyze plugins.
 */
interface AnalyzeInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Render a summary report for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render the summary for.
   *
   * @return mixed[]
   *   A render array to include on the summary page.
   */
  public function renderSummary(EntityInterface $entity): array;

  /**
   * Render a full report for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity to render the summary for.
   *
   * @return mixed[]
   *    A render array to include in the full report page.
   */
  public function renderFullReport(EntityInterface $entity): array;

  /**
   * Helper to return the URL to the full report.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the URL from.
   *
   * @return \Drupal\Core\Url|null
   *   A URL to the Full Report page for this plugin. NULL for no link.
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url;

  /**
   * Helper to identify if the plugin is enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity the plugin is showing for.
   *
   * @return bool
   */
  public function isEnabled(EntityInterface $entity): bool;

  /**
   * Helper to identify if the plugin is configurable on an entity type/bundle.
   *
   * @param string $entity_type
   *   The Entity Type the plugin is showing for.
   * @param string $bundle
   *   The bundle the plugin is showing for.
   *
   * @return bool
   *   TRUE if the plugin is configurable.
   */
  public function isApplicable(string $entity_type, string $bundle): bool;

  /**
   * Helper to identify if the user has access to the plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity the plugin is showing for.
   *
   * @return bool
   *   TRUE if the user has access to the plugin.
   */
  public function access(EntityInterface $entity): bool;

}
