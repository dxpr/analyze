<?php

declare(strict_types=1);

namespace Drupal\analyze;

/**
 * Interface for analyze plugins.
 */
interface AnalyzeInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

}
