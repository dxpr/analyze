<?php

declare(strict_types=1);

namespace Drupal\analyze\Drush\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml;

/**
 * Shared base class for Analyze Drush commands.
 *
 * Provides common utilities following the cross-project pattern
 * established by dxt:*, dxb:*, and rl:* command namespaces.
 */
abstract class AnalyzeCommandsBase extends DrushCommands {

  /**
   * Switch to the admin user for elevated operations.
   */
  protected function switchToAdmin(): void {
    // @phpstan-ignore-next-line
    if (!\Drupal::hasContainer()) {
      return;
    }

    try {
      /** @var \Drupal\Core\Session\AccountSwitcherInterface $switcher */
      // @phpstan-ignore-next-line
      $switcher = \Drupal::service('account_switcher');
      /** @var \Drupal\user\UserStorageInterface $storage */
      // @phpstan-ignore-next-line
      $storage = \Drupal::entityTypeManager()->getStorage('user');
      $admin = $storage->load(1);

      if ($admin) {
        $switcher->switchTo($admin);
      }
    }
    catch (\Exception $e) {
      // Silently fail if services aren't available yet.
    }
  }

  /**
   * Format data as YAML string.
   *
   * @param array<string, mixed> $data
   *   The data to format.
   * @param int $inline
   *   The level at which to switch to inline YAML.
   *
   * @return string
   *   YAML-formatted string.
   */
  protected function yaml(array $data, int $inline = 4): string {
    return Yaml::dump($data, $inline, 2);
  }

  /**
   * Return a success response as YAML.
   *
   * @param string $message
   *   The success message.
   * @param array<string, mixed> $data
   *   Additional data to include.
   *
   * @return string
   *   YAML-formatted success response.
   */
  protected function success(
    string $message,
    array $data = [],
  ): string {
    return $this->yaml(array_merge([
      'success' => TRUE,
      'message' => $message,
    ], $data));
  }

  /**
   * Return an error response as YAML.
   *
   * @param string $message
   *   The error message.
   * @param array<string, mixed> $errors
   *   Additional error details.
   *
   * @return string
   *   YAML-formatted error response.
   */
  protected function error(
    string $message,
    array $errors = [],
  ): string {
    return $this->yaml(array_merge([
      'success' => FALSE,
      'message' => $message,
    ], $errors ? ['errors' => $errors] : []));
  }

  /**
   * Get the module path for the analyze module.
   *
   * @return string|null
   *   The module path, or NULL if not found.
   */
  protected function getModulePath(): ?string {
    try {
      // @phpstan-ignore-next-line
      return \Drupal::service('extension.list.module')
        ->getPath('analyze');
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get the project root directory.
   *
   * Walks up from DRUPAL_ROOT looking for composer.json.
   *
   * @return string|null
   *   The project root path, or NULL if not found.
   */
  protected function getProjectRoot(): ?string {
    $dir = DRUPAL_ROOT;
    for ($i = 0; $i < 5; $i++) {
      if (file_exists($dir . '/composer.json')) {
        return $dir;
      }
      $dir = dirname($dir);
    }
    return NULL;
  }

}
