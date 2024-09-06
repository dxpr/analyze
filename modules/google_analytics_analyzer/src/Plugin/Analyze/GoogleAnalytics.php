<?php

declare(strict_types=1);

namespace Drupal\google_analytics_analyzer\src\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the analyze.
 *
 * @Analyze(
 *   id = "analytics",
 *   label = @Translation("Google Analytics Entity Reports"),
 *   description = @Translation("Provides data from Google Analytics for Analyzer.")
 * )
 */
final class GoogleAnalytics extends AnalyzePluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Analytics Node Reports'),
      'table' => [
        '#type' => 'table',
        '#header' => [['data' => 'Google Analytics Node Reports', 'colspan' => 2, 'class' => ['header']]],
        '#rows' => [
          ['data' => ['Page Views', '1234']],
          ['data' => ['Bounce Rate', '45%']],
          ['data' => ['Average Time on Page', '2 minutes']],
        ],
        '#attributes' => [
          'class' => ['google-analytics-data-table'],
          'style' => ['table-layout: fixed;'],
        ],
      ],
    ];
  }

}
