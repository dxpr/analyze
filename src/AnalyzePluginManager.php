<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\analyze\Annotation\Analyze as AnalyzeAnnotation;
use Drupal\analyze\Attribute\Analyze;

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
    parent::__construct('Plugin/Analyze', $namespaces, $module_handler, AnalyzeInterface::class, Analyze::class, AnalyzeAnnotation::class);
    $this->alterInfo('analyze_info');
    $this->setCacheBackend($cache_backend, 'analyze_plugins');
    $this->factory = new ContainerFactory($this, AnalyzeInterface::class);
  }

}
