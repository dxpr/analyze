<?php

declare(strict_types=1);

namespace Drupal\analyze_google_analytics\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;
use Drupal\analyze\HelperInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analyze plugin to display Google Analytics data.
 *
 * @Analyze(
 *   id = "analytics",
 *   label = @Translation("Google Analytics Entity Reports"),
 *   description = @Translation("Provides data from Google Analytics for
 *   Analyzer.")
 * )
 */
final class GoogleAnalytics extends AnalyzePluginBase {

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
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   Statistics service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelperInterface $helper,
    AccountProxyInterface $currentUser,
    protected AliasManagerInterface $aliasManager,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('path_alias.manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $return = [
      '#theme' => 'analyze_table',
      '#table_title' => 'Google Analytics Summary',
      '#row_one' => [
        'label' => 'No data',
        'data' => 'There is no data recorded for this entity.',
      ],
    ];

    $results = $this->getSummaryResults($entity);

    if (!empty($results)) {
      foreach ($results as $row) {
        // @phpstan-ignore-next-line
        if ($row->screenPageViews) {
          $return['#row_one'] = [
            'label' => 'Page views',
            'data' => $row->screenPageViews,
          ];
        }
        // @phpstan-ignore-next-line
        if ($row->screenPageViewsPerUser) {
          $return['#row_two'] = [
            'label' => 'Page views per user',
            'data' => number_format((float) $row->screenPageViewsPerUser, 2),
          ];
        }
        // @phpstan-ignore-next-line
        if ($row->bounceRate) {
          $return['#row_three'] = [
            'label' => 'Bounce Rate',
            'data' => number_format((float) $row->bounceRate, 2),
          ];
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    $url = $entity->toUrl()->toString();

    return [
      '#type' => 'view',
      '#name' => 'analyze_google_analytics',
      '#display_id' => 'full_report',
      '#arguments' => [
        $this->aliasManager->getAliasByPath($url),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {

    // Only show a full report URL if we have results.
    if ($this->getSummaryResults($entity)) {
      return parent::getFullReportUrl($entity);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    // Use the permission from the Google Analytics Reports module.
    return $this->currentUser->hasPermission('access google analytics reports');
  }

  /**
   * Helper to get the results from the Summary display of our view.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the results for.
   *
   * @return \Drupal\views\ResultRow[]|null
   *   An array of view results, or null on error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getSummaryResults(EntityInterface $entity): ?array {
    $return = NULL;
    $url = $entity->toUrl()->toString();

    if ($view_entity = $this->entityTypeManager->getStorage('view')->load('analyze_google_analytics')) {
      /** @var \Drupal\views\ViewEntityInterface $view_entity */
      $view = $view_entity->getExecutable();
      $view->setArguments([$this->aliasManager->getAliasByPath($url)]);
      $view->execute('summary');

      if ($view->result) {
        $return = $view->result;
      }
    }

    return $return;
  }

}
