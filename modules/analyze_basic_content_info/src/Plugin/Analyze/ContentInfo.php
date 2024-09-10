<?php

declare(strict_types=1);

namespace Drupal\analyze_basic_content_info\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analyze plugin to display Basic data.
 *
 * @Analyze(
 *   id = "content_info",
 *   label = @Translation("Basic Content Entity Reports"),
 *   description = @Translation("Provides basic statistics about content for Analyzer.")
 * )
 */
final class ContentInfo extends AnalyzePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Creates the plugin.
   *
   * @param array $configuration
   *   Configuration.
   * @param $plugin_id
   *   Plugin ID.
   * @param $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Statistics service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $word_count = $this->getWordCount($entity);

    return [
      '#type' => 'table',
      '#header' => [['data' => 'Security', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Word count', 498]],
        ['data' => ['Image count', 2]],
      ],
      '#attributes' => [
        'class' => ['basic-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  /**
   * Helper to calculate a rough word count for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a word count for.
   *
   * @return int
   *   The counted words. Defaults to 0.
   */
  private function getWordCount(EntityInterface $entity): int {
    $return = 0;
    $countable = [];

    // Handle potential differences in how titles are collected.
    if (method_exists($entity, 'getTitle')) {
      $countable[] = $entity->getTitle();
    }
    elseif (method_exists($entity, 'label')) {
      $countable[] = $entity->label();
    }

    if ($entity instanceof FieldableEntityInterface) {
      foreach ($this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $definition) {

      }

    }



    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    return NULL;
  }

}
