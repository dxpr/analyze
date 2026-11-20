<?php

declare(strict_types=1);

namespace Drupal\analyze\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Declares a Analyze plugin.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Analyze extends Plugin {

  /**
   * Constructs a plugin definition.
   */
  public function __construct(
    string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    ?string $deriver = NULL,
  ) {
    parent::__construct($id, $deriver);
  }

}
