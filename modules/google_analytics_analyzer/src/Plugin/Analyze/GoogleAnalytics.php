<?php

declare(strict_types=1);

namespace Drupal\google_analytics_analyzer\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\analyze\HelperInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Creates the plugin.
   *
   * @param array $configuration
   *   Configuration.
   * @param $plugin_id
   *   Plugin ID.
   * @param $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\analyze\HelperInterface $helper
   *   Analyze helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   Statistics service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelperInterface $helper,
    AccountProxyInterface $currentUser,
    protected AliasManagerInterface $aliasManager,
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
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $url = $entity->toUrl()->toString();
    return [
      '#type' => 'view',
      '#name' => 'analyze_google_analytics',
      '#display_id' => 'summary',
      '#arguments' => [
        $this->aliasManager->getAliasByPath($url),
      ],
    ];
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
  public function access(EntityInterface $entity): bool {
    // Use the permission from the Google Analytics Reports module.
    return $this->currentUser->hasPermission('access google analytics reports');
  }

}
