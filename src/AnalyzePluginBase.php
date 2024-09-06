<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Base class for analyze plugins.
 */
abstract class AnalyzePluginBase extends PluginBase implements AnalyzeInterface {

  use AnalyzeTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): Url {
    $entity_type = $entity->getEntityTypeId();

    return Url::fromRoute($entity_type . '.' . $this->getPluginId(), [$entity_type => $entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity):bool {
    // @todo Implement proper check against config.
    return TRUE;
  }

}
