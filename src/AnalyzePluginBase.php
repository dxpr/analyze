<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for analyze plugins.
 */
abstract class AnalyzePluginBase extends PluginBase implements AnalyzeInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
