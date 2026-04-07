<?php

declare(strict_types=1);

namespace Drupal\analyze\Drush\Commands;

use Drupal\analyze\Service\AnalyzeBatchService;
use Drush\Attributes as CLI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Centralized Drush commands for batch analysis.
 */
final class AnalyzeBatchCommands extends AnalyzeCommandsBase {

  public function __construct(
    private readonly AnalyzeBatchService $batchService,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('analyze.batch_service'),
    );
  }

  /**
   * Run batch analysis across content.
   */
  #[CLI\Command(name: 'analyze:batch', aliases: ['ab'])]
  #[CLI\Option(name: 'analyzers', description: 'Comma-separated analyzer plugin IDs (default: all batch-capable)')]
  #[CLI\Option(name: 'types', description: 'Comma-separated entity type:bundle pairs (e.g., node:article,node:page)')]
  #[CLI\Option(name: 'limit', description: 'Maximum entities to process (0 for no limit)')]
  #[CLI\Option(name: 'force', description: 'Force re-analysis even if results exist')]
  #[CLI\Option(name: 'list', description: 'List available batch-capable analyzers and exit')]
  #[CLI\Usage(name: 'analyze:batch', description: 'Run all batch-capable analyzers on all enabled content types')]
  #[CLI\Usage(name: 'analyze:batch --analyzers=sentiments,brand_voice', description: 'Run specific analyzers')]
  #[CLI\Usage(name: 'analyze:batch --types=node:article --limit=50 --force', description: 'Force analyze up to 50 articles')]
  #[CLI\Usage(name: 'analyze:batch --list', description: 'List available batch-capable analyzers')]
  public function batch(
    array $options = [
      'analyzers' => '',
      'types' => '',
      'limit' => 0,
      'force' => FALSE,
      'list' => FALSE,
    ],
  ): void {
    $this->switchToAdmin();
    $available = $this->batchService->getBatchableAnalyzers();

    if (empty($available)) {
      $this->logger()->warning(dt('No analyzers support batch processing. Install modules that implement BatchableAnalyzerInterface.'));
      return;
    }

    if ($options['list']) {
      $this->logger()->notice(dt('Available batch-capable analyzers:'));
      foreach ($available as $id => $label) {
        $this->logger()->notice(dt('  @id — @label', [
          '@id' => $id,
          '@label' => $label,
        ]));
      }
      return;
    }

    $analyzer_ids = !empty($options['analyzers'])
      ? explode(',', $options['analyzers'])
      : array_keys($available);

    // Validate analyzer IDs.
    foreach ($analyzer_ids as $id) {
      if (!isset($available[$id])) {
        $this->logger()->error(dt('Unknown analyzer "@id". Use --list to see available analyzers.', [
          '@id' => $id,
        ]));
        return;
      }
    }

    $types = !empty($options['types'])
      ? explode(',', $options['types'])
      : array_keys($this->batchService->getAvailableEntityBundles($analyzer_ids));

    if (empty($types)) {
      $this->logger()->warning(dt('No content types have the selected analyzers enabled. Enable analyzers at /admin/config/content/analyze-settings first.'));
      return;
    }

    $force = (bool) $options['force'];
    $limit = (int) $options['limit'];

    $entities = $this->batchService->getEntitiesForAnalysis(
      $analyzer_ids,
      $types,
      $force,
      $limit
    );

    if (empty($entities)) {
      $this->logger()->notice(dt('No entities found for analysis.'));
      return;
    }

    $total = count($entities);
    $analyzer_names = implode(', ', array_intersect_key($available, array_flip($analyzer_ids)));
    $this->logger()->notice(dt('Running @analyzers on @count entities...', [
      '@analyzers' => $analyzer_names,
      '@count' => $total,
    ]));

    $processed = 0;
    $failed = 0;
    $rate_limited = 0;
    $errors = [];

    foreach (array_chunk($entities, 5) as $chunk) {
      $context = [
        'sandbox' => ['total_entities' => $total],
        'results' => [
          'processed' => $processed,
          'failed' => $failed,
          'rate_limited' => $rate_limited,
          'errors' => $errors,
        ],
      ];

      $this->batchService->processBatch(
        $chunk,
        $analyzer_ids,
        $force,
        $total,
        $context
      );

      $processed = $context['results']['processed'];
      $failed = $context['results']['failed'];
      $rate_limited = $context['results']['rate_limited'];
      $errors = $context['results']['errors'];

      $done = $processed + $failed;
      $this->logger()->notice(dt('Processed @current/@total entities.', [
        '@current' => $done,
        '@total' => $total,
      ]));
    }

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $this->logger()->error($error);
      }
    }

    $summary = dt('Batch complete: @ok succeeded, @fail failed out of @total with @analyzers.', [
      '@ok' => $processed,
      '@fail' => $failed,
      '@total' => $total,
      '@analyzers' => $analyzer_names,
    ]);

    if ($rate_limited > 0) {
      $summary .= ' ' . dt('(@rl rate-limited after retries)', ['@rl' => $rate_limited]);
    }

    if ($failed > 0) {
      $this->logger()->warning($summary);
    }
    else {
      $this->logger()->success($summary);
    }
  }

}
