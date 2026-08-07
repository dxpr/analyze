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
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AnalyzePluginManager $analyzePluginManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
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
   * Gets analysis coverage status per bundle.
   *
   * Uses getEntitiesForAnalysis() with a cap to avoid full entity scans on
   * large sites. If pending count hits the cap, reports it as approximate.
   *
   * @param array<string> $analyzer_ids
   *   Array of analyzer plugin IDs.
   * @param array<string> $entity_bundles
   *   Array of entity_type:bundle strings.
   *
   * @return array<string, array{label: string, total: int, pending: int, pending_approximate: bool}>
   *   Status per bundle.
   */
  public function getAnalysisStatus(array $analyzer_ids, array $entity_bundles): array {
    $bundle_labels = $this->getAvailableEntityBundles($analyzer_ids);
    $status = [];

    foreach ($entity_bundles as $entity_bundle) {
      [$entity_type_id, $bundle] = explode(':', $entity_bundle);

      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      // Count total published entities (fast count query, no entity load).
      $query = $storage->getQuery()->accessCheck(FALSE);
      $bundle_key = $entity_type->getKey('bundle');
      if ($bundle_key) {
        $query->condition($bundle_key, $bundle);
      }
      $status_key = $entity_type->getKey('status');
      if ($status_key) {
        $query->condition($status_key, 1);
      }
      $total = (int) $query->count()->execute();

      if ($total === 0) {
        continue;
      }

      // Fast count: ask each analyzer how many entities it has results for.
      // An entity is "analyzed" if ALL selected analyzers have results.
      $analyzers = [];
      foreach ($analyzer_ids as $aid) {
        $plugin = $this->analyzePluginManager->createInstance($aid);
        if ($plugin instanceof BatchableAnalyzerInterface) {
          $analyzers[$aid] = $plugin;
        }
      }

      // Use the minimum count across analyzers as the "fully analyzed" count.
      $min_analyzed = $total;
      foreach ($analyzers as $analyzer) {
        if (method_exists($analyzer, 'countAnalyzedEntities')) {
          $count = $analyzer->countAnalyzedEntities($entity_type_id, $bundle);
        }
        else {
          $count = 0;
        }
        $min_analyzed = min($min_analyzed, $count);
      }

      $pending = $total - $min_analyzed;

      $status[$entity_bundle] = [
        'label' => $bundle_labels[$entity_bundle] ?? $entity_bundle,
        'total' => $total,
        'pending' => $pending,
        'pending_approximate' => FALSE,
      ];
    }

    return $status;
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
      $context['results']['failed'] = 0;
      $context['results']['rate_limited'] = 0;
      $context['results']['errors'] = [];
    }

    // Build analyzer instances.
    $all_analyzers = [];
    foreach ($analyzer_ids as $analyzer_id) {
      $plugin = $this->analyzePluginManager->createInstance($analyzer_id);
      if ($plugin instanceof BatchableAnalyzerInterface) {
        $all_analyzers[$analyzer_id] = $plugin;
      }
    }

    // Build bundle → enabled analyzer map for correct matrix enforcement.
    $config = $this->configFactory->get('analyze.settings');
    $status_config = $config->get('status') ?? [];

    foreach ($entities as $entity_data) {
      $entity = $this->entityTypeManager
        ->getStorage($entity_data['entity_type'])
        ->load($entity_data['entity_id']);

      if (!$entity) {
        continue;
      }

      // Only run analyzers that are enabled for this entity's bundle.
      $enabled = $status_config[$entity_data['entity_type']][$entity_data['bundle']] ?? [];
      $analyzers = array_intersect_key($all_analyzers, $enabled);

      if (empty($analyzers)) {
        continue;
      }

      $entity_succeeded = TRUE;
      foreach ($analyzers as $analyzer_id => $analyzer) {
        try {
          $result = $this->runWithBackoff(
            fn() => $analyzer->processEntity($entity, $force_refresh),
          );
          if ($result === FALSE) {
            $entity_succeeded = FALSE;
          }
        }
        catch (\Exception $e) {
          $entity_succeeded = FALSE;
          if ($this->isRateLimitException($e)) {
            $context['results']['rate_limited']++;
            $context['results']['errors'][] = $this->t('Rate limited on @type @id (@analyzer): @msg', [
              '@type' => $entity_data['entity_type'],
              '@id' => $entity_data['entity_id'],
              '@analyzer' => $analyzer_id,
              '@msg' => $e->getMessage(),
            ])->render();
          }
          else {
            $context['results']['errors'][] = $this->t('Error on @type @id (@analyzer): @msg', [
              '@type' => $entity_data['entity_type'],
              '@id' => $entity_data['entity_id'],
              '@analyzer' => $analyzer_id,
              '@msg' => $e->getMessage(),
            ])->render();
          }
        }
      }

      if ($entity_succeeded) {
        $context['results']['processed']++;
      }
      else {
        $context['results']['failed']++;
      }
    }

    $done = $context['results']['processed'] + $context['results']['failed'];
    $context['message'] = $this->t('Processed @current of @max entities...', [
      '@current' => $done,
      '@max' => $context['sandbox']['total_entities'],
    ])->render();

    if ($context['sandbox']['total_entities'] > 0) {
      $context['finished'] = $done / $context['sandbox']['total_entities'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Runs a callable with exponential backoff on rate limit exceptions.
   *
   * @param callable $fn
   *   The callable to execute.
   * @param int $max_retries
   *   Maximum number of retries.
   *
   * @return bool
   *   The return value from the callable.
   *
   * @throws \Exception
   *   If all retries are exhausted or a non-rate-limit exception occurs.
   */
  private function runWithBackoff(callable $fn, int $max_retries = 3): bool {
    $delay = 2;
    for ($attempt = 0; $attempt <= $max_retries; $attempt++) {
      try {
        return (bool) $fn();
      }
      catch (\Exception $e) {
        if (!$this->isRateLimitException($e) || $attempt === $max_retries) {
          throw $e;
        }
        sleep($delay);
        $delay *= 2;
      }
    }
    return FALSE;
  }

  /**
   * Check if an exception is an AI rate limit exception.
   *
   * Uses class name check to avoid a hard dependency on drupal/ai.
   */
  private function isRateLimitException(\Exception $e): bool {
    // String-based check avoids a hard dependency on drupal/ai.
    return is_a($e, 'Drupal\ai\Exception\AiRateLimitException');
  }

  /**
   * Gets entity IDs that have results from ALL specified analyzers.
   *
   * Uses chunked loading to avoid memory issues on large sites.
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
    foreach (array_chunk($all_ids, 50, TRUE) as $chunk) {
      $entities = $storage->loadMultiple($chunk);
      foreach ($entities as $id => $entity) {
        try {
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
        catch (\Exception) {
          // Entity type may lack a view_builder; skip it.
        }
      }
      // Clear static entity cache to keep memory flat.
      $storage->resetCache($chunk);
    }

    return $analyzed_ids;
  }

}
