<?php

declare(strict_types=1);

namespace Drupal\analyze\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines analyze annotation object.
 *
 * @Annotation
 */
final class Analyze extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $title;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $description;

}
