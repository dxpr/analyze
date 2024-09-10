<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides shared service and functions to use across analyze entities.
 */
trait AnalyzeTrait {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager = NULL;

  /**
   * Route Match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|null
   */
  protected $routeMatch = NULL;

  /**
   * Config for the Analyze module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|null
   */
  protected ImmutableConfig|null $analyzeConfig = NULL;

  /**
   * The plugin manager.
   *
   * @var \Drupal\analyze\AnalyzePluginManager|null
   */
  protected AnalyzePluginManager|null $pluginManager = NULL;

  /**
   * Entity Type Bundle Info Service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null
   */
  protected EntityTypeBundleInfoInterface|null $entityTypeBundleInfo = NULL;

  /**
   * Helper to set and get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The Entity Type Manager.
   */
  protected function entityTypeManager(): EntityTypeManagerInterface {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }

    return $this->entityTypeManager;
  }

  /**
   * Helper to get and set the RouteMatchInterface.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   Route Match Interface.
   */
  private function routeMatch(): RouteMatchInterface {
    if (!$this->routeMatch) {
      $this->routeMatch = \Drupal::routeMatch();
    }

    return $this->routeMatch;
  }

  /**
   * Helper to return the Analyze config, if set.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The config entity, or NULL if not set yet.
   */
  protected function analyzeConfig(): ?ImmutableConfig {
    if (!$this->analyzeConfig) {
      $this->analyzeConfig = \Drupal::config('analyze.settings');
    }

    return $this->analyzeConfig;
  }

  /**
   * Helper to return the Analyze Plugin Manager.
   *
   * @return \Drupal\analyze\AnalyzePluginManager
   *   The Plugin Manager.
   */
  protected function pluginManager(): AnalyzePluginManager {
    if (!isset($this->pluginManager)) {
      $this->pluginManager = \Drupal::service('plugin.manager.analyze');
    }

    return $this->pluginManager;
  }

  /**
   * Helper to return an Entity Type Bundle Info service.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The Entity Type Bundle Info service.
   */
  protected function entityTypeBundleInfo(): EntityTypeBundleInfoInterface {
    if (!isset($this->entityTypeBundleInfo)) {
      $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    }

    return $this->entityTypeBundleInfo;
  }

  /**
   * Helper to return an entity from parameters.
   *
   * @param string $entity_type
   *    The entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *    The entity, or NULL on error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getEntity(string $entity_type): ?EntityInterface {
    $return = NULL;

    if ($entity_id = $this->routeMatch()->getParameter($entity_type)) {
      if (!$entity_id instanceof EntityInterface) {
        $return = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
      }
      else {
        $return = $entity_id;
      }
    }

    return $return;
  }

  /**
   * Helper to obtain all the Analyze Plugins.
   *
   * @param array $plugin_ids
   *   An array of specific plugins to load.
   *
   * @return \Drupal\analyze\AnalyzeInterface[]
   *   An array of analyze plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getPlugins(array $plugin_ids = []): array {
    $return = $this->pluginManager()->getDefinitions();

    foreach ($return as $key => $plugin) {
      if (!empty($plugin_ids)) {
        if (!in_array($key, $plugin_ids)) {
          unset($return[$key]);
        }
        else {
          $return[$key] = $this->pluginManager()->createInstance($key);
        }
      }
      else {
        $return[$key] = $this->pluginManager()->createInstance($key);
      }
    }

    return $return;
  }

  /**
   * Helper to obtain all bundles for an entity type.
   *
   * @param string $entity_type
   *   The entity type machine name.
   *
   * @return mixed[]
   *   An array of bundle information.
   */
  private function getEntityBundles(string $entity_type): array {
    return $this->entityTypeBundleInfo()->getBundleInfo($entity_type);
  }

}
