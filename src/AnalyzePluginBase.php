<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for analyze plugins.
 */
abstract class AnalyzePluginBase extends PluginBase implements AnalyzeInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HelperInterface $helper,
    protected AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    $entity_type = $entity->getEntityTypeId();

    return Url::fromRoute('analyze.' . $entity_type . '.' . $this->getPluginId(), [$entity_type => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function fullReportUrlOverridden(EntityInterface $entity): bool {
    $return = TRUE;

    if ($url = $this->getFullReportUrl($entity)) {
      $entity_type = $entity->getEntityTypeId();

      if ($url->isRouted() && $url->getRouteName() == 'analyze.' . $entity_type . '.' . $this->getPluginId()) {
        $return = FALSE;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity): bool {
    $return = FALSE;

    if ($config = $this->helper->getConfig()->get('status')) {
      $return = !empty($config[$entity->getEntityTypeId()][$entity->bundle()][$this->getPluginId()]);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type, string $bundle): bool {
    // Default to all entity types and bundles being accessible.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    // Default to having access.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function extraSummaryLinks(EntityInterface $entity): array {
    return [];
  }

}
