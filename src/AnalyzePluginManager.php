<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\analyze\Annotation\Analyze;

/**
 * Analyze plugin manager.
 */
final class AnalyzePluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   *
   * @phpstan-param \Traversable<string, string[]> $namespaces
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Analyze', $namespaces, $module_handler, AnalyzeInterface::class, Analyze::class);
    $this->alterInfo('analyze_info');
    $this->setCacheBackend($cache_backend, 'analyze_plugins');
  }

}
