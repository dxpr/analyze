<?php

declare(strict_types=1);

namespace Drupal\analyze\Service;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\analyze\AnalyzePluginManager;
use Drupal\analyze\BatchableAnalyzerInterface;

/**
 * Centralized batch processing service for all analyze plugins.
 *
 * Handles entity discovery, batch orchestration, and progress tracking
 * for any analyzer plugin implementing BatchableAnalyzerInterface.
 */
final class AnalyzeBatchService {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AnalyzePluginManager $analyzePluginManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {}

  /**
   * Gets analyzer plugin IDs that support batch processing.
   *
   * @return array<string, string>
   *   Array of plugin_id => label for batch-capable analyzers.
   */
  public function getBatchableAnalyzers(): array {
    $analyzers = [];

    foreach ($this->analyzePluginManager->getDefinitions() as $id => $definition) {
      $plugin = $this->analyzePluginManager->createInstance($id);
      if ($plugin instanceof BatchableAnalyzerInterface) {
        $analyzers[$id] = (string) $definition['label'];
      }
    }

    return $analyzers;
  }

  /**
   * Gets entity bundles that have any of the given analyzers enabled.
   *
   * @param array<string> $analyzer_ids
   *   Array of analyzer plugin IDs to check for.
   *
   * @return array<string, string>
   *   Array of entity_type:bundle => label pairs.
   */
  public function getAvailableEntityBundles(array $analyzer_ids): array {
    $config = $this->configFactory->get('analyze.settings');
    $status = $config->get('status') ?? [];

    $options = [];
    foreach ($status as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $analyzers) {
        foreach ($analyzer_ids as $analyzer_id) {
          if (isset($analyzers[$analyzer_id])) {
            $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
            $label = $bundle_info[$bundle]['label'] ?? $bundle;
            $key = "{$entity_type_id}:{$bundle}";
            $options[$key] = "{$entity_type_id} - {$label}";
            break;
          }
        }
      }
    }

    return $options;
  }

  /**
   * Gets entities that need analysis by any of the given analyzers.
   *
   * @param array<string> $analyzer_ids
   *   Array of analyzer plugin IDs.
   * @param array<string> $entity_bundles
   *   Array of entity_type:bundle strings.
   * @param bool $force_refresh
   *   Whether to include entities with existing analysis.
   * @param int $limit
   *   Maximum number of entities to return (0 for no limit).
   *
   * @return array<array{entity_type: string, entity_id: string|int, bundle: string}>
   *   Array of entity info arrays.
   */
  public function getEntitiesForAnalysis(array $analyzer_ids, array $entity_bundles, bool $force_refresh = FALSE, int $limit = 0): array {
    $entities = [];

    foreach ($entity_bundles as $entity_bundle) {
      [$entity_type_id, $bundle] = explode(':', $entity_bundle);

      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $query = $storage->getQuery();
      $query->accessCheck(FALSE);

      $bundle_key = $entity_type->getKey('bundle');
      if ($bundle_key) {
        $query->condition($bundle_key, $bundle);
      }

      $status_key = $entity_type->getKey('status');
      if ($status_key) {
        $query->condition($status_key, 1);
      }

      if (!$force_refresh) {
        $analyzed_ids = $this->getFullyAnalyzedEntityIds($analyzer_ids, $entity_type_id, $bundle);
        if (!empty($analyzed_ids)) {
          $id_key = $entity_type->getKey('id');
          $query->condition($id_key, $analyzed_ids, 'NOT IN');
        }
      }

      if ($limit > 0) {
        $remaining = $limit - count($entities);
        if ($remaining <= 0) {
          break;
        }
        $query->range(0, $remaining);
      }

      $ids = $query->execute();

      foreach ($ids as $id) {
        $entities[] = [
          'entity_type' => $entity_type_id,
          'entity_id' => $id,
          'bundle' => $bundle,
        ];
      }
    }

    return $entities;
  }

  /**
   * Processes a batch of entities with the given analyzers.
   *
   * @param array<array{entity_type: string, entity_id: string|int, bundle: string}> $entities
   *   Array of entity info.
   * @param array<string> $analyzer_ids
   *   Array of analyzer plugin IDs to run.
   * @param bool $force_refresh
   *   Whether to force fresh analysis.
   * @param int $total_entities
   *   Total number of entities being processed across all batches.
   * @param array<string, mixed> $context
   *   Batch context.
   */
  public function processBatch(array $entities, array $analyzer_ids, bool $force_refresh, int $total_entities, array &$context): void {
    if (!isset($context['sandbox']['total_entities'])) {
      $context['sandbox']['total_entities'] = $total_entities;
      $context['results']['processed'] = 0;
      $context['results']['errors'] = [];
    }

    $analyzers = [];
    foreach ($analyzer_ids as $analyzer_id) {
      $plugin = $this->analyzePluginManager->createInstance($analyzer_id);
      if ($plugin instanceof BatchableAnalyzerInterface) {
        $analyzers[$analyzer_id] = $plugin;
      }
    }

    foreach ($entities as $entity_data) {
      try {
        $entity = $this->entityTypeManager
          ->getStorage($entity_data['entity_type'])
          ->load($entity_data['entity_id']);

        if ($entity) {
          foreach ($analyzers as $analyzer) {
            $analyzer->processEntity($entity, $force_refresh);
          }
          $context['results']['processed']++;
        }
      }
      catch (\Exception $e) {
        $context['results']['errors'][] = $this->t('Error processing @type @id: @message', [
          '@type' => $entity_data['entity_type'],
          '@id' => $entity_data['entity_id'],
          '@message' => $e->getMessage(),
        ])->render();
      }
    }

    $context['message'] = $this->t('Processed @current of @max entities...', [
      '@current' => $context['results']['processed'],
      '@max' => $context['sandbox']['total_entities'],
    ])->render();

    if ($context['sandbox']['total_entities'] > 0) {
      $context['finished'] = $context['results']['processed'] / $context['sandbox']['total_entities'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Gets entity IDs that have results from ALL specified analyzers.
   *
   * @param array<string> $analyzer_ids
   *   Array of analyzer plugin IDs.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   *
   * @return array<string|int>
   *   Array of entity IDs that have results from all analyzers.
   */
  private function getFullyAnalyzedEntityIds(array $analyzer_ids, string $entity_type_id, string $bundle): array {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);

    $bundle_key = $entity_type->getKey('bundle');
    if ($bundle_key) {
      $query->condition($bundle_key, $bundle);
    }

    $all_ids = $query->execute();
    if (empty($all_ids)) {
      return [];
    }

    $analyzers = [];
    foreach ($analyzer_ids as $analyzer_id) {
      $plugin = $this->analyzePluginManager->createInstance($analyzer_id);
      if ($plugin instanceof BatchableAnalyzerInterface) {
        $analyzers[$analyzer_id] = $plugin;
      }
    }

    if (empty($analyzers)) {
      return [];
    }

    $analyzed_ids = [];
    foreach ($all_ids as $id) {
      $entity = $storage->load($id);
      if (!$entity) {
        continue;
      }

      $all_have_results = TRUE;
      foreach ($analyzers as $analyzer) {
        if (!$analyzer->hasResults($entity)) {
          $all_have_results = FALSE;
          break;
        }
      }

      if ($all_have_results) {
        $analyzed_ids[] = $id;
      }
    }

    return $analyzed_ids;
  }

}
