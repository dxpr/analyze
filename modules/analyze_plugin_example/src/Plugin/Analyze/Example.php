<?php

declare(strict_types=1);

namespace Drupal\analyze_plugin_example\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;

/**
 * Analyze plugin to display Example data.
 *
 * @Analyze(
 *   id = "example",
 *   label = @Translation("Example Entity Reports"),
 *   description = @Translation("Provides an example plugin implementation for Analyzer.")
 * )
 *
 * The base Analyze module will create a summary page for every entity on the
 * Drupal site with a canonical URL property. It will also create a full
 * report URL for every enabled plugin that does not override the base
 * Plugin's getFullReportUrl() method.
 */
final class Example extends AnalyzePluginBase {

  /**
   * {@inheritdoc}
   *
   * This method is used to return a summary to display on an entity's Analyze
   * report page. Max 3 pieces of information can be shown - any large amounts
   * of data should be restricted to the plugin's full report page.
   */
  public function renderSummary(EntityInterface $entity): array {

    return [
      '#theme' => 'analyze_table',
      '#table_title' => 'Example Table',
      '#rows' => [
        [
          'label' => 'Label One',
          'data' => $entity->id(),
        ],
        [
          'label' => 'Label Two',
          'data' => 'Data',
        ],
        [
          'label' => 'Label Three',
          'data' => 'Data',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Example Full Report'),
      'gauge' => [
        '#theme' => 'analyze_gauge',
        '#caption' => 'Example Gauge',
        '#range_min_label' => 'Low',
        '#range_mid_label' => 'Medium',
        '#range_max_label' => 'High',
        '#range_min' => 0,
        '#range_max' => 1,
        '#value' => 0.5,
        '#display_value' => '50%',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type, ?string $bundle = NULL): bool {
    // Example: Only enable for article nodes.
    if ($entity_type == 'node' && $bundle == 'article') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    return $this->currentUser->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function extraSummaryLinks(EntityInterface $entity): array {
    return [
      'global_report' => [
        'title' => $this->t('Global Report'),
        'url' => Url::fromUri('https://example.com/global-report'),
      ],
    ];
  }

}
