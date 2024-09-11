<?php

declare(strict_types=1);

namespace Drupal\analyze_basic_content_info\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;
use Drupal\analyze\HelperInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
final class ContentInfo extends AnalyzePluginBase {

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
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Statistics service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelperInterface $helper,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    return [
      '#type' => 'table',
      '#header' => [['data' => 'Basic Info', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Word count', $this->getWordCount($entity)]],
        ['data' => ['Image count', $this->getImageCount($entity)]],
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
    $countables = [];

    if ($entity instanceof FieldableEntityInterface) {
      foreach ($this->getFieldDefinitionsByType($entity, [
        'string',
        'string_long',
        'text_long',
        'text_with_summary',
        'text',
      ]) as $definition) {
        if ($name = $definition->getName()) {
          if ($name !== 'timezone') {
            $value = $entity->get($name)->getValue();

            if (!empty($value[0]['value'])) {
              $countables[] = $value[0]['value'];
            }
          }
        }
      }
    }
    else {
      $countables[] = $entity->label();
    }

    foreach ($countables as $countable) {
      $matches = [];
      preg_match_all("/(\w+)/", strip_tags($countable), $matches);

      if ($matches[0]) {
        $return += count($matches[0]);
      }
    }

    return $return;
  }

  /**
   * Helper to get a count of images attached to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to count images on.
   *
   * @return int
   *   The number of images. Defaults to 0.
   */
  private function getImageCount(EntityInterface $entity): int {
    $return = 0;

    if ($entity instanceof FieldableEntityInterface) {
      foreach ($this->getFieldDefinitionsByType($entity, ['image']) as $definition) {
        if ($name = $definition->getName()) {
          if (!$entity->get($name)->isEmpty()) {
            $values = $entity->get($name)->getValue();

            $return += count($values);
          }
        }
      }
    }

    return $return;
  }

  /**
   * Helper to return field definitions of a given type.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get the definitions for.
   * @param array $types
   *   An array of types we want to return.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Any definitions of the required types.
   */
  private function getFieldDefinitionsByType(FieldableEntityInterface $entity, array $types): array {
    $return = [];

    foreach ($this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()) as $definition) {
      if (in_array($definition->getType(), $types)) {
        $return[] = $definition;
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
