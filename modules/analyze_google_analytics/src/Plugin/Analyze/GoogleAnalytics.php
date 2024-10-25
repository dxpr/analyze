<?php

declare(strict_types=1);

namespace Drupal\analyze_google_analytics\Plugin\Analyze;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;
use Drupal\analyze\HelperInterface;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelperInterface $helper,
    AccountProxyInterface $currentUser,
    protected AliasManagerInterface $aliasManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $return = [
      '#theme' => 'analyze_table',
      '#table_title' => 'Google Analytics Summary Last 30 Days',
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
          $return['#rows'][] = [
            'label' => 'Page views',
            'data' => $row->screenPageViews,
          ];
        }
        // @phpstan-ignore-next-line
        if ($row->screenPageViewsPerUser) {
          $return['#rows'][] = [
            'label' => 'Page views per user',
            'data' => number_format((float) $row->screenPageViewsPerUser, 2),
          ];
        }
        // @phpstan-ignore-next-line
        if ($row->bounceRate) {
          $return['#rows'][] = [
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
    $results = $this->getSummaryResults($entity, 'full_report');
    $return = [
      '#theme' => 'table',
      '#title' => 'Google Analytics Summary Last 30 Days',
      '#header' => [
        $this->t('Google Analytics Summary Last 30 Days'),
      ],
    ];
    // We only get one aggregated row, so we can just grab the first one.
    if (!empty($results[0])) {
      // Views are itterable.
      // @phpstan-ignore-next-line
      foreach ($results[0] as $key => $value) {
        // If key starts with underscore or is the index field, skip it.
        if (strpos($key, '_') === 0 || $key === 'index') {
          continue;
        }

        // If the value has a float precision over 2, we want to format it.


        switch ($key) {
          case 'sessionsPerUser':
            $value = number_format((float) $value, 2);
            break;

          case 'averageSessionDuration':
          case 'userEngagementDuration':
            $value = number_format((float) $value, 2) . 's';
            break;

          case 'engagementRate':
          case 'bounceRate':
            $value = number_format((float) $value * 100, 2) . '%';
            break;
        }
        // Transform every capital letter to a space and the letter.
        $label = ucwords(strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $key)));
        $return['#rows'][] = [
          $label,
          $value,
        ];
      }
    }
    return $return;
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
  public function isApplicable(string $entity_type, ?string $bundle = NULL): bool {
    // Is only applicable Google Analytics report is setup.
    return $this->isGoogleAnalyticsReportsSetup();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity): bool {
    // Is only enabled if Google Analytics report is setup.
    return $this->isGoogleAnalyticsReportsSetup();
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
   * @param string $report
   *   The display of the view to get the results from.
   *
   * @return \Drupal\views\ResultRow[]|null
   *   An array of view results, or null on error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getSummaryResults(EntityInterface $entity, $report = 'summary'): ?array {
    $return = NULL;
    $url = $entity->toUrl()->toString();

    if ($view_entity = $this->entityTypeManager->getStorage('view')->load('analyze_google_analytics')) {
      /** @var \Drupal\views\ViewEntityInterface $view_entity */
      $view = $view_entity->getExecutable();
      $view->setArguments([$this->aliasManager->getAliasByPath($url)]);
      $view->execute($report);

      if ($view->result) {
        $return = $view->result;
      }
    }

    return $return;
  }

  /**
   * Helper function to check if Google Analytics Reports is setup.
   *
   * @return bool
   *   TRUE if Google Analytics Reports is setup.
   */
  private function isGoogleAnalyticsReportsSetup(): bool {
    // @phpstan-ignore-next-line
    $account = GoogleAnalyticsReportsApiFeed::service();
    $imported = $this->configFactory->get('google_analytics_reports.settings')->get('metadata_last_time');
    return $account && $account->isAuthenticated() && $imported;
  }

  /**
   * {@inheritdoc}
   */
  public function extraSummaryLinks(EntityInterface $entity): array {
    return [
      [
        'title' => $this->t('View sitewide Google Analytics report'),
        'url' => Url::fromRoute('view.google_analytics_summary.page_1'),
      ],
    ];
  }

}
