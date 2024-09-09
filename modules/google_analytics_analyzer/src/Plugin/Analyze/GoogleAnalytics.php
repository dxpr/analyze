<?php

declare(strict_types=1);

namespace Drupal\google_analytics_analyzer\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Analyze plugin to display Google Analytics data.
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
      '#type' => 'table',
      '#header' => [['data' => $this->t('Google Analytics'), 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => [$this->t('Page Views'), '1234']],
        ['data' => [$this->t('Bounce Rate'), '45%']],
        ['data' => [$this->t('Average Time on Page'), '2 minutes']],
      ],
      '#attributes' => [
        'class' => ['google-analytics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    return [
      '#type' => 'table',
      '#header' => [['data' => $this->t('Google Analytics'), 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => [$this->t('Page Views'), '1234']],
        ['data' => [$this->t('Bounce Rate'), '45%']],
        ['data' => [$this->t('Average Time on Page'), '2 minutes']],
      ],
      '#attributes' => [
        'class' => ['google-analytics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

}
