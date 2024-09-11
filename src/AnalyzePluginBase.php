<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * @param array $configuration
   *   Configuration.
   * @param $plugin_id
   *   Plugin ID.
   * @param $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\analyze\HelperInterface $helper
   *   Analyze helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HelperInterface $helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper')
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
   * Helper to check whether a plugin has overridden the full report URL.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to get against.
   *
   * @return bool
   *   TRUE if the URL is overridden or nullified, FALSE if not.
   */
  public function fullReportUrlOverridden(EntityInterface $entity): bool {
    $return = TRUE;

    if ($url = $this->getFullReportUrl($entity)) {
      $entity_type = $entity->getEntityTypeId();

      if ($url->getRouteName() == 'analyze.' . $entity_type . '.' . $this->getPluginId()) {
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

}
