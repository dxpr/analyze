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
   * Helper to return the URL to the full report.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the URL from.
   *
   * @return \Drupal\Core\Url
   *   A URL to the Full Report page for this plugin.
   */
  public function getFullReportUrl(EntityInterface $entity): Url;

  /**
   * Helper to identify if the plugin is enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity the plugin is showing for.
   *
   * @return bool
   */
  public function isEnabled(EntityInterface $entity): bool;

}
