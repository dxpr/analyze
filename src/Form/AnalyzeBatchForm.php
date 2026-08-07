<?php

declare(strict_types=1);

namespace Drupal\analyze\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\analyze\Service\AnalyzeBatchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Centralized batch form for running analysis across all analyzers.
 */
final class AnalyzeBatchForm extends FormBase {

  public function __construct(
    protected AnalyzeBatchService $batchService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('analyze.batch_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'analyze_batch';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   *
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $analyzers = $this->batchService->getBatchableAnalyzers();

    if (empty($analyzers)) {
      $form['no_analyzers'] = [
        '#markup' => $this->t('<p>No analyzers support batch processing. Install and enable analyzer modules that implement batch capability.</p>'),
      ];
      return $form;
    }

    $form['description'] = [
      '#markup' => $this->t('<p>Run batch analysis across your content. Select one or more analyzers and the content types to analyze. Only published content will be processed.</p>'),
    ];

    $form['analyzers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Analyzers'),
      '#description' => $this->t('Select which analyzers to run.'),
      '#options' => $analyzers,
      '#required' => TRUE,
    ];

    $all_bundles = $this->batchService->getAvailableEntityBundles(array_keys($analyzers));

    if (empty($all_bundles)) {
      $configure_url = Url::fromRoute('analyze.analyze_settings');
      $form['no_bundles'] = [
        '#markup' => $this->t('<p>No content types have any analyzers enabled. <a href="@url">Configure content analysis settings</a> first.</p>', [
          '@url' => $configure_url->toString(),
        ]),
      ];
      return $form;
    }

    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Select which content types to analyze.'),
      '#options' => $all_bundles,
      '#required' => TRUE,
    ];

    $form['force_refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force re-analysis'),
      '#description' => $this->t('Re-analyze content even if recent results exist. This will replace all cached results.'),
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#description' => $this->t('Maximum number of entities to analyze (0 for no limit).'),
      '#default_value' => 100,
      '#min' => 0,
      '#max' => 10000,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Start Batch Analysis'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $selected_analyzers = array_values(array_filter($values['analyzers']));
    $selected_types = array_values(array_filter($values['entity_types']));
    $force_refresh = (bool) $values['force_refresh'];
    $limit = (int) $values['limit'];

    $entities = $this->batchService->getEntitiesForAnalysis(
      $selected_analyzers,
      $selected_types,
      $force_refresh,
      $limit
    );

    if (empty($entities)) {
      $this->messenger()->addWarning($this->t('No entities found for analysis.'));
      return;
    }

    $total_entities = count($entities);
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Analyzing @count entities', ['@count' => $total_entities]))
      ->setFinishCallback([static::class, 'batchFinished'])
      ->setProgressive(TRUE);

    $chunks = array_chunk($entities, 5);
    foreach ($chunks as $chunk) {
      $batch_builder->addOperation(
        [$this->batchService, 'processBatch'],
        [$chunk, $selected_analyzers, $force_refresh, $total_entities]
      );
    }

    batch_set($batch_builder->toArray());
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array<string, mixed> $results
   *   The batch results.
   * @param array<mixed> $operations
   *   The remaining operations.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      $processed = $results['processed'] ?? 0;
      \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural(
        $processed,
        'Successfully analyzed @count entity.',
        'Successfully analyzed @count entities.',
        ['@count' => $processed]
      ));

      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          \Drupal::messenger()->addError($error);
        }
      }
    }
    else {
      \Drupal::messenger()->addError(t('Batch analysis processing failed.'));
    }
  }

}
