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

    return Url::fromRoute('analyze.' . $entity_type . '.' . $this->getPluginId(), [$entity_type => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity):bool {
    $return = FALSE;

    if ($config = $this->analyzeConfig()->get('status')) {
      $return = !empty($config[$entity->getEntityTypeId()][$entity->bundle()][$this->getPluginId()]);
    }

    return $return;
  }

}
