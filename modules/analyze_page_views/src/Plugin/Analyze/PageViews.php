<?php

declare(strict_types=1);

namespace Drupal\analyze_page_views\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Analyze plugin to display Basic data.
 *
 * @Analyze(
 *   id = "page_views",
 *   label = @Translation("Entity Page Views Reports"),
 *   description = @Translation("Provides details from the Statistics module about content for Analyzer.")
 * )
 */
final class PageViews extends AnalyzePluginBase {

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
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    return NULL;
  }

}
