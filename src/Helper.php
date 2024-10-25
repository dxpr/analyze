<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * The Analyze helper service.
 */
final class Helper implements HelperInterface {

  /**
   * Constructs a Helper object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly AnalyzePluginManager $pluginManagerAnalyze,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    $this->analyzeConfig = $this->configFactory->get('analyze.settings');
  }

  /**
   * Config for the Analyze module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|null
   */
  protected ?ImmutableConfig $analyzeConfig = NULL;

  /**
   * {@inheritdoc}
   */
  public function getConfig(): ?ImmutableConfig {
    return $this->analyzeConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(string $entity_type): ?EntityInterface {
    $return = NULL;

    if ($entity_id = $this->routeMatch->getParameter($entity_type)) {
      if (!$entity_id instanceof EntityInterface) {
        $return = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      }
      else {
        $return = $entity_id;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins(array $plugin_ids = []): array {
    $return = $this->pluginManagerAnalyze->getDefinitions();

    foreach ($return as $key => $plugin) {
      if (!empty($plugin_ids)) {
        if (!in_array($key, $plugin_ids)) {
          unset($return[$key]);
        }
        else {
          $return[$key] = $this->pluginManagerAnalyze->createInstance($key);
        }
      }
      else {
        $return[$key] = $this->pluginManagerAnalyze->createInstance($key);
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundles(string $entity_type): array {
    return $this->entityTypeBundleInfo->getBundleInfo($entity_type);
  }

  /**
   * Helper to return all entity definitions.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  public function getEntityDefinitions(): array {
    $return = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->hasLinkTemplate('canonical')) {
        $return[$entity_type->id()] = $entity_type;
      }

    }
    return $return;
  }

  /**
   * Helper to return all applicable definitions based on an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string|null $bundle
   *   The bundle.
   *
   * @return \Drupal\analyze\AnalyzeInterface[]
   *   An array of Analyze plugins that are applicable.
   */
  public function getApplicableDefinitions(string $entity_type, ?string $bundle = NULL): array {
    $return = [];

    foreach ($this->pluginManagerAnalyze->getDefinitions() as $id => $definition) {
      $plugin = $this->pluginManagerAnalyze->createInstance($id);

      if ($plugin->isApplicable($entity_type, $bundle)) {
        $return[$id] = $definition;
      }
    }

    return $return;
  }

}
