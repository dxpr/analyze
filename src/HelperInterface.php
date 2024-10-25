<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for the Analyze helper service.
 */
interface HelperInterface {

  /**
   * Helper to return an entity from parameters.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or NULL on error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntity(string $entity_type): ?EntityInterface;

  /**
   * Helper to obtain all the Analyze Plugins.
   *
   * @param string[] $plugin_ids
   *   An array of specific plugins to load.
   *
   * @return \Drupal\analyze\AnalyzeInterface[]
   *   An array of analyze plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPlugins(array $plugin_ids = []): array;

  /**
   * Helper to obtain all bundles for an entity type.
   *
   * @param string $entity_type
   *   The entity type machine name.
   *
   * @return mixed[]
   *   An array of bundle information.
   */
  public function getEntityBundles(string $entity_type): array;

  /**
   * Helper to return the analyze config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The Analyze config, or NULL if not available.
   */
  public function getConfig(): ?ImmutableConfig;

  /**
   * Helper to return all entity definitions.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  public function getEntityDefinitions(): array;

}
