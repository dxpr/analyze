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
  #[CLI\Option(name: 'status', description: 'Show analysis coverage status and exit')]
  #[CLI\Usage(name: 'analyze:batch', description: 'Run all batch-capable analyzers on all enabled content types')]
  #[CLI\Usage(name: 'analyze:batch --analyzers=sentiments,brand_voice', description: 'Run specific analyzers')]
  #[CLI\Usage(name: 'analyze:batch --types=node:article --limit=50 --force', description: 'Force analyze up to 50 articles')]
  #[CLI\Usage(name: 'analyze:batch --list', description: 'List available batch-capable analyzers')]
  #[CLI\Usage(name: 'analyze:batch --status', description: 'Show how many entities have been analyzed')]
  public function batch(
    array $options = [
      'analyzers' => '',
      'types' => '',
      'limit' => 0,
      'force' => FALSE,
      'list' => FALSE,
      'status' => FALSE,
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

    // --status: show coverage and exit.
    if ($options['status']) {
      $this->showStatus($analyzer_ids, $types, $available);
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
      $this->logger()->success(dt('All entities are up to date. Nothing to process.'));
      return;
    }

    $total = count($entities);
    $analyzer_names = implode(', ', array_intersect_key($available, array_flip($analyzer_ids)));

    // Confirmation prompt: tell the user what's about to happen.
    $this->logger()->notice(dt('Will process @count entities with @analyzers.', [
      '@count' => $total,
      '@analyzers' => $analyzer_names,
    ]));
    $this->logger()->notice(dt('Some analyzers use external API requests that may incur costs.'));

    if (!$this->io()->confirm(dt('Continue?'), TRUE)) {
      $this->logger()->notice(dt('Cancelled.'));
      return;
    }

    $processed = 0;
    $failed = 0;
    $rate_limited = 0;
    $errors = [];
    $entity_num = 0;

    foreach ($entities as $entity_data) {
      $entity_num++;
      $this->io()->write(dt('  [@num/@total] @type @id ... ', [
        '@num' => $entity_num,
        '@total' => $total,
        '@type' => $entity_data['entity_type'],
        '@id' => $entity_data['entity_id'],
      ]));

      $context = [
        'sandbox' => ['total_entities' => 1],
        'results' => [
          'processed' => 0,
          'failed' => 0,
          'rate_limited' => 0,
          'errors' => [],
        ],
      ];

      $this->batchService->processBatch(
        [$entity_data],
        $analyzer_ids,
        $force,
        1,
        $context
      );

      if ($context['results']['failed'] > 0) {
        $failed++;
        $this->io()->writeln('<fg=red>FAILED</>');
      }
      else {
        $processed++;
        $this->io()->writeln('<fg=green>OK</>');
      }

      $rate_limited += $context['results']['rate_limited'];
      $errors = array_merge($errors, $context['results']['errors']);
    }

    // Final summary.
    if (!empty($errors)) {
      $this->io()->writeln('');
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

  /**
   * Show analysis coverage status.
   *
   * @param array<string> $analyzer_ids
   *   Analyzer plugin IDs.
   * @param array<string> $types
   *   Entity type:bundle pairs.
   * @param array<string, string> $available
   *   Available analyzers keyed by ID => label.
   */
  private function showStatus(array $analyzer_ids, array $types, array $available): void {
    $status = $this->batchService->getAnalysisStatus($analyzer_ids, $types);

    if (empty($status)) {
      $this->logger()->notice(dt('No content found for the selected analyzers and types.'));
      return;
    }

    $rows = [];
    $total_pending = 0;
    foreach ($status as $info) {
      $pending_str = (string) $info['pending'];
      if ($info['pending_approximate']) {
        $pending_str = '>' . $pending_str;
      }
      $analyzed = $info['total'] - $info['pending'];
      if ($info['pending_approximate']) {
        $coverage = '<' . round(($analyzed / $info['total']) * 100) . '%';
      }
      else {
        $coverage = $info['total'] > 0
          ? round(($analyzed / $info['total']) * 100) . '%'
          : '–';
      }
      $rows[] = [
        $info['label'],
        $info['total'],
        $pending_str,
        $coverage,
      ];
      $total_pending += $info['pending'];
    }

    $this->io()->table(
      ['Bundle', 'Total', 'Pending', 'Coverage'],
      $rows,
    );

    $analyzer_names = implode(', ', array_intersect_key($available, array_flip($analyzer_ids)));
    $this->logger()->notice(dt('Analyzers: @names', ['@names' => $analyzer_names]));

    if ($total_pending > 0) {
      $this->logger()->notice(dt('Run "drush analyze:batch" to process pending entities.'));
    }
    else {
      $this->logger()->success(dt('All entities are up to date.'));
    }
  }

}
