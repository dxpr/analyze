<?php

declare(strict_types=1);

namespace Drupal\analyze_page_views\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analyze plugin to display Basic data.
 *
 * @Analyze(
 *   id = "node_views",
 *   label = @Translation("Node Page Views Reports"),
 *   description = @Translation("Provides details from the Statistics module about nodes for Analyzer.")
 * )
 */
final class NodeViews extends AnalyzePluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('statistics.storage.node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $views = $this->nodeStatisticsDatabaseStorage->fetchView($entity->id());

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

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity): bool {

    // Statistics are only recorded for node entities.
    if ($entity->getEntityTypeId() == 'node') {
      return parent::isEnabled($entity);
    }

    return FALSE;
  }

}
