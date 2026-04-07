<?php

declare(strict_types=1);

namespace Drupal\analyze;

/**
 * Helper for locating the Composer project root.
 */
final class ProjectRootHelper {

  /**
   * Finds the nearest ancestor directory containing composer.json.
   *
   * @param string $start
   *   The directory to start from.
   * @param int $maxDepth
   *   The maximum number of parent directories to inspect.
   *
   * @return string|null
   *   The project root, or NULL if none was found.
   */
  public static function find(string $start, int $maxDepth = 5): ?string {
    $dir = $start;

    for ($i = 0; $i < $maxDepth; $i++) {
      if (file_exists($dir . '/composer.json')) {
        return $dir;
      }

      $parent = dirname($dir);
      if ($parent === $dir) {
        break;
      }
      $dir = $parent;
    }

    return NULL;
  }

}
