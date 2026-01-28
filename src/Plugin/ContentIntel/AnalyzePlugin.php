<?php

declare(strict_types=1);

namespace Drupal\analyze\Plugin\ContentIntel;

use Drupal\Core\Render\RenderContext;
use Drupal\analyze\AnalyzeInterface;
use Drupal\analyze\HelperInterface;
use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\content_intel\ContentIntelPluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides analysis data from the Analyze module.
 */
#[ContentIntel(
  id: 'analyze',
  label: new TranslatableMarkup('Content Analysis'),
  description: new TranslatableMarkup('Analysis data from Analyze module plugins.'),
  weight: 30,
)]
class AnalyzePlugin extends ContentIntelPluginBase {

  /**
   * The analyze helper.
   *
   * @var \Drupal\analyze\HelperInterface|null
   */
  protected ?HelperInterface $analyzeHelper = NULL;

  /**
   * The analyze plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|null
   */
  protected $analyzePluginManager = NULL;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|null
   */
  protected ?RendererInterface $renderer = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    if ($container->has('analyze.helper')) {
      $instance->analyzeHelper = $container->get('analyze.helper');
    }
    if ($container->has('plugin.manager.analyze')) {
      $instance->analyzePluginManager = $container->get('plugin.manager.analyze');
    }
    if ($container->has('renderer')) {
      $instance->renderer = $container->get('renderer');
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return $this->analyzeHelper !== NULL && $this->analyzePluginManager !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    if (!$this->analyzeHelper) {
      return FALSE;
    }

    // Check if any analyze plugins apply to this entity.
    $definitions = $this->analyzeHelper->getApplicableDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    return !empty($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function collect(ContentEntityInterface $entity): array {
    if (!$this->analyzeHelper || !$this->analyzePluginManager) {
      return [];
    }

    $data = [];

    // Get applicable analyze plugins.
    $definitions = $this->analyzeHelper->getApplicableDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    foreach ($definitions as $plugin_id => $definition) {
      try {
        /** @var \Drupal\analyze\AnalyzeInterface $plugin */
        $plugin = $this->analyzePluginManager->createInstance($plugin_id);

        if (!$plugin instanceof AnalyzeInterface) {
          continue;
        }

        // Check if plugin is enabled for this entity.
        if (!$plugin->isEnabled($entity)) {
          continue;
        }

        // Check access.
        if (!$plugin->access($entity)) {
          continue;
        }

        // Get the summary data with render isolation for CLI context.
        $summary = [];
        $plugin_label = $plugin->label();

        // Wrap in renderInIsolation to provide render context for CLI.
        if ($this->renderer) {
          $render_callback = function () use ($plugin, $entity, &$summary) {
            $summary = $plugin->renderSummary($entity);
            return ['#markup' => ''];
          };
          try {
            $this->renderer->executeInRenderContext(
              new RenderContext(),
              $render_callback
            );
          }
          catch (\Exception $e) {
            // If even isolated render fails, skip this plugin.
            continue;
          }
        }
        else {
          $summary = $plugin->renderSummary($entity);
        }

        $plugin_data = $this->extractDataFromRenderArray($summary, $plugin_id);

        if (!empty($plugin_data)) {
          $data[$plugin_id] = [
            'plugin_label' => $plugin_label,
            'data' => $plugin_data,
          ];
        }
      }
      catch (\Exception $e) {
        $data[$plugin_id] = [
          'plugin_label' => $definition['label'] ?? $plugin_id,
          'error' => $e->getMessage(),
        ];
      }
    }

    return $data;
  }

  /**
   * Extracts structured data from a render array.
   *
   * @param array $render_array
   *   The render array.
   * @param string $plugin_id
   *   The plugin ID for context.
   *
   * @return array
   *   Extracted data.
   */
  protected function extractDataFromRenderArray(array $render_array, string $plugin_id): array {
    $data = [];

    // Handle analyze_table theme.
    if (isset($render_array['#theme']) && $render_array['#theme'] === 'analyze_table') {
      if (!empty($render_array['#table_title'])) {
        $data['title'] = (string) $render_array['#table_title'];
      }
      if (!empty($render_array['#rows']) && is_array($render_array['#rows'])) {
        foreach ($render_array['#rows'] as $row) {
          if (isset($row['label']) && isset($row['data'])) {
            $key = $this->normalizeKey((string) $row['label']);
            $data[$key] = $row['data'];
          }
        }
      }
      return $data;
    }

    // Handle analyze_gauge theme.
    if (isset($render_array['#theme']) && $render_array['#theme'] === 'analyze_gauge') {
      if (!empty($render_array['#caption'])) {
        $data['caption'] = (string) $render_array['#caption'];
      }
      if (isset($render_array['#value'])) {
        $data['value'] = $render_array['#value'];
      }
      if (!empty($render_array['#display_value'])) {
        $data['display_value'] = (string) $render_array['#display_value'];
      }
      if (!empty($render_array['#range_min_label'])) {
        $data['range_min_label'] = (string) $render_array['#range_min_label'];
      }
      if (!empty($render_array['#range_mid_label'])) {
        $data['range_mid_label'] = (string) $render_array['#range_mid_label'];
      }
      if (!empty($render_array['#range_max_label'])) {
        $data['range_max_label'] = (string) $render_array['#range_max_label'];
      }
      if (isset($render_array['#range_min'])) {
        $data['range_min'] = $render_array['#range_min'];
      }
      if (isset($render_array['#range_max'])) {
        $data['range_max'] = $render_array['#range_max'];
      }
      return $data;
    }

    // Look for common patterns in other render arrays.
    foreach ($render_array as $key => $value) {
      // Skip Drupal internal keys.
      if (str_starts_with($key, '#')) {
        // Handle #markup specially.
        if ($key === '#markup' && is_string($value)) {
          $data['content'] = strip_tags($value);
        }
        continue;
      }

      if (is_array($value)) {
        // Check for value key (common pattern).
        if (isset($value['#markup'])) {
          $data[$key] = strip_tags((string) $value['#markup']);
        }
        elseif (isset($value['#plain_text'])) {
          $data[$key] = $value['#plain_text'];
        }
        elseif (isset($value['value'])) {
          $data[$key] = $value['value'];
        }
        // Recursively extract from nested arrays.
        else {
          $nested = $this->extractDataFromRenderArray($value, $plugin_id);
          if (!empty($nested)) {
            $data[$key] = $nested;
          }
        }
      }
      elseif (is_scalar($value)) {
        $data[$key] = $value;
      }
    }

    return $data;
  }

  /**
   * Normalizes a label to a machine key.
   *
   * @param string $label
   *   The human-readable label.
   *
   * @return string
   *   A normalized key.
   */
  protected function normalizeKey(string $label): string {
    return strtolower(str_replace([' ', '-'], '_', trim($label)));
  }

}
