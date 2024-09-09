<?php

declare(strict_types=1);

namespace Drupal\analyze_basic_content_info\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Analyze plugin to display Google Analytics data.
 *
 * @Analyze(
 *   id = "content_info",
 *   label = @Translation("Basic Content Entity Reports"),
 *   description = @Translation("Provides basic statistics about content for Analyzer.")
 * )
 */
final class ContentInfo extends AnalyzePluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    return [
      '#type' => 'table',
      '#header' => [['data' => 'Security', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Word count', 498]],
        ['data' => ['Image count', 2]],
      ],
      '#attributes' => [
        'class' => ['basic-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic Content'),
      'table' => [
        '#type' => 'table',
        '#header' => [['data' => 'Security', 'colspan' => 2, 'class' => ['header']]],
        '#rows' => [
          ['data' => ['Word count', 498]],
          ['data' => ['Image count', 2]],
        ],
        '#attributes' => [
          'class' => ['basic-data-table'],
          'style' => ['table-layout: fixed;'],
        ],
      ],
      'gauge_one' => [
        '#theme' => 'analyze_gauge',
        '#caption' => 'Sentiment Score - General Sentiment',
        '#range_min_label' => 'Negative',
        '#range_mid_label' => 'Neutral',
        '#range_max_label' => 'Positive',
        '#range_min' => '0',
        // Example: 20% Positive.
        '#value' => '0.2',
        '#display_value' => '20%',
        '#range_max' => '1',
      ],
      'gauge_two' => [
        '#theme' => 'analyze_gauge',
        '#caption' => 'Sentiment Score - Joy',
        '#range_min_label' => 'Sad',
        '#range_mid_label' => 'Neutral',
        '#range_max_label' => 'Joyful',
        '#range_min' => '0',
        // Example: 60% Joyful.
        '#value' => '0.6',
        '#display_value' => '60%',
        '#range_max' => '1',
      ],
      'gauge_three' => [
        '#theme' => 'analyze_gauge',
        '#caption' => 'Sentiment Score - Anger',
        '#range_min_label' => 'Calm',
        '#range_mid_label' => 'Neutral',
        '#range_max_label' => 'Angry',
        '#range_min' => '0',
        // Example: 80% Angry.
        '#value' => '0.8',
        '#display_value' => '80%',
        '#range_max' => '1',
      ],
    ];
  }

}
