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
   * @return array<string, mixed>
   *   A render array to include on the summary page.
   */
  public function renderSummary(EntityInterface $entity): array;

  /**
   * Render a full report for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render the summary for.
   *
   * @return array<string, mixed>
   *   A render array to include in the full report page.
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
   *   TRUE if the plugin is enabled.
   */
  public function isEnabled(EntityInterface $entity): bool;

  /**
   * Helper to identify if the plugin is configurable on an entity type/bundle.
   *
   * @param string $entity_type
   *   The Entity Type the plugin is showing for.
   * @param string|null $bundle
   *   The bundle the plugin is showing for, might not be set on creation form.
   *
   * @return bool
   *   TRUE if the plugin is applicable.
   */
  public function isApplicable(string $entity_type, ?string $bundle = NULL): bool;

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

  /**
   * Helper to check whether a plugin has overridden the full report URL.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to get against.
   *
   * @return bool
   *   TRUE if the URL is overridden or nullified, FALSE if not.
   */
  public function fullReportUrlOverridden(EntityInterface $entity): bool;

  /**
   * If the plugin wants to expose extra summary links, it can do so here.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to create links against.
   *
   * @return array<mixed>
   *   An array of title and url pairs.
   */
  public function extraSummaryLinks(EntityInterface $entity): array;

  /**
   * Gets the configurable settings for this analyzer.
   *
   * @return array<string, array{type: string, title: string, description?: string, settings?: array<string, array{type: string, title: string, default_value: mixed}>}>
   *   An array defining the configurable settings.
   */
  public function getConfigurableSettings(): array;

}
