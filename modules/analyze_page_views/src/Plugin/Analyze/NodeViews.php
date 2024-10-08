<?php

declare(strict_types=1);

namespace Drupal\analyze_page_views\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;
use Drupal\analyze\HelperInterface;
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
final class NodeViews extends AnalyzePluginBase {

  /**
   * Creates the plugin.
   *
   * @param array<string, mixed> $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array<string, mixed> $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\analyze\HelperInterface $helper
   *   Analyze helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\statistics\NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage
   *   Statistics service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelperInterface $helper,
    AccountProxyInterface $currentUser,
    protected NodeStatisticsDatabaseStorage $nodeStatisticsDatabaseStorage,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper, $currentUser);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper'),
      $container->get('current_user'),
      $container->get('statistics.storage.node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $total = $day = 0;

    if ($views = $this->nodeStatisticsDatabaseStorage->fetchView($entity->id())) {
      $total = $views->getTotalCount();
      $day = $views->getDayCount();
    }

    return [
      '#theme' => 'analyze_table',
      '#table_title' => 'Node views',
      '#rows' => [
        [
          'label' => 'Total views',
          'data' => $total,
        ],
        [
          'label' => "Today's count",
          'data' => $day,
        ],
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
  public function isApplicable(string $entity_type, string $bundle): bool {
    // Statistics are only recorded for node entities.
    return $entity_type == 'node';
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    // Use the permission from the Statistics module.
    return $this->currentUser->hasPermission('view post access counter');
  }

}
