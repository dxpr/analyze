<?php

namespace Drupal\analyze\Controller;

use Drupal\analyze\AnalyzePluginManager;
use Drupal\analyze\AnalyzeTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Analyze module.
 */
class AnalyzeController extends ControllerBase {

  use AnalyzeTrait;

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly AnalyzePluginManager $pluginManagerAnalyze,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.analyze')
    );
  }

  /**
   * Helper to obtain all the Analyze Plugins.
   *
   * @param array $plugin_ids
   *   An array of specific plugins to load.
   *
   * @return \Drupal\analyze\AnalyzeInterface[]
   *   An array of analyze plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getPlugins(array $plugin_ids = []): array {
    $return = $this->pluginManagerAnalyze->getDefinitions();

    foreach ($return as $key => $plugin) {
      if (!empty($plugin_ids)) {
        if (!in_array($key, $plugin_ids)) {
          unset($return[$key]);
        }
        else {
          $return[$key] = $this->pluginManagerAnalyze->createInstance($key);
        }
      }
      else {
        $return[$key] = $this->pluginManagerAnalyze->createInstance($key);
      }
    }

    return $return;
  }

  /**
   * Analyzes an entity and returns a render array with various metrics.
   *
   * @param string|null $plugin
   *   A plugin id to load the report for.
   * @param string|null $entity_type
   *   An entity type to load the report for.
   * @param bool $full_report
   *   Whether to render the full report. FALSE to render the summary.
   *
   * @return mixed[]
   *   A render array containing the analysis results.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function analyze(string $plugin = NULL, string $entity_type = NULL, $full_report = FALSE): array {
    if ($plugin) {
      $plugins = $this->getPlugins([$plugin]);
    }
    else {
      $plugins = $this->getPlugins();
    }

    $entity = $this->getEntity($entity_type);
    $build = [];
    $weight = 0;

    foreach ($plugins as $id => $plugin) {
      if ($plugin->isEnabled($entity)) {
        $build[$id . '/wrapper'] = [
          '#type' => 'fieldset',
          '#title' => $plugin->label(),
          '#weight' => $weight,
          $id => ($full_report) ? $plugin->renderFullReport($entity) : $plugin->renderSummary($entity),
        ];

        if (!$full_report) {
          $build[$id . '/wrapper']['full_report'] = [
            '#type' => 'link',
            '#title' => $this->t('View the full report'),
            '#url' => $plugin->getFullReportUrl($entity),
            '#attributes' => [
              'class' => [
                'action-link',
                Html::cleanCssIdentifier('view-' . $id . '-report'),
              ],
            ],
          ];
        }
        $weight++;
      }
    }

    return $build;
  }

}
