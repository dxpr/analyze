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
   *
   * @return mixed[]
   *   A render array containing the analysis results.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function analyze(string $plugin = NULL, string $entity_type = NULL): array {
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
      $build[$id] = $plugin->renderSummary($entity);
      $build[$id]['#weight'] = $weight;
      $build[$id . '-report'] = [
        '#type' => 'link',
        '#title' => $this->t('View the full report'),
        '#url' => $plugin->getFullReportUrl($entity),
        '#attributes' => ['class' => ['action-link', Html::cleanCssIdentifier('view-' . $id . '-report')]],
        '#wight' => $weight,
      ];
      $weight++;
    }

    $build['header'] = [
      '#type' => 'markup',
      '#markup' => '<h2>Sentiment Analysis</h2>',
    ];

    $build['gauge_1'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Sentiment Score - General Sentiment',
      '#range_min_label' => 'Negative',
      '#range_mid_label' => 'Neutral',
      '#range_max_label' => 'Positive',
      '#range_min' => '0',
    // Example: 20% Positive.
      '#value' => '0.2',
      '#display_value' => '20%',
      '#range_max' => '1',
    ];

    $build['gauge_2'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Sentiment Score - Joy',
      '#range_min_label' => 'Sad',
      '#range_mid_label' => 'Neutral',
      '#range_max_label' => 'Joyful',
      '#range_min' => '0',
    // Example: 60% Joyful.
      '#value' => '0.6',
      '#display_value' => '60%',
      '#range_max' => '1',
    ];

    $build['gauge_3'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Sentiment Score - Anger',
      '#range_min_label' => 'Calm',
      '#range_mid_label' => 'Neutral',
      '#range_max_label' => 'Angry',
      '#range_min' => '0',
    // Example: 80% Angry.
      '#value' => '0.8',
      '#display_value' => '80%',
      '#range_max' => '1',
    ];

    $build['basic_header'] = [
      '#markup' => '<h2>Basic content information</h2>',
    ];

    $basic_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Security', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Word count', 498]],
        ['data' => ['Image count', 2]],
      ],
      '#attributes' => [
        'class' => ['basic-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['basic_table'] = $basic_table;

    $build['security_header'] = [
      '#markup' => '<h2>Security</h2>',
    ];

    $build['gauge_4'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'PII (Personally Identifiable Information) Detection',
      '#range_min_label' => 'PII Highly Improbable',
      '#range_mid_label' => '',
      '#range_max_label' => 'PII Highly Probable',
      '#range_min' => '.01',
      '#value' => '0',
      '#display_value' => '1%',
      '#range_max' => '.99',
    ];

    $build['gauge_5'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Malicious Link Detection',
      '#range_min_label' => 'Malicious Links Highly Improbable',
      '#range_mid_label' => '',
      '#range_max_label' => 'Malicious Links Highly Probable',
      '#range_min' => '.01',
      '#value' => '0',
      '#display_value' => '1%',
      '#range_max' => '.99',
    ];

    $build['statistics_header'] = [
      '#markup' => '<h2>Page views</h2>',
    ];

    $statistics_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Page popularity', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Total page views', 3229]],
        ['data' => ['Today\'s page views', 29]],
      ],
      '#attributes' => [
        'class' => ['statistics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['statistics_table'] = $statistics_table;

    $build['realtime_seo_header'] = [
      '#markup' => '<h2>Realtime SEO</h2>',
    ];

    $realtime_seo_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Realtime SEO', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['SEO Score', '85%']],
        ['data' => ['Focus Keyword', '"Drupal 10 Module"']],
        ['data' => ['Readability', 'Good']],
      ],
      '#attributes' => [
        'class' => ['realtime-seo-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['realtime_seo_table'] = $realtime_seo_table;

    $build['accessibility_header'] = [
      '#markup' => '<h2>Editoria11y Accessibility Checker</h2>',
    ];

    $accessibility_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Editoria11y Accessibility Checker', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Valid HTML', 'Yes']],
        ['data' => ['Alt Text for Images', '95% complete']],
        ['data' => ['Headings Structure', 'Proper']],
      ],
      '#attributes' => [
        'class' => ['accessibility-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['accessibility_table'] = $accessibility_table;

    return $build;
  }

  /**
   * Generates a full report for realtime SEO analysis of a node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node to analyze.
   *
   * @return array
   *   A render array containing the full realtime SEO report.
   */
  public function realtimeSeoFullReport(NodeInterface $entity) {
    $header = [
      ['data' => 'Metric', 'class' => ['header']],
      ['data' => 'Value', 'class' => ['header']],
    ];
    $rows = [
      ['data' => ['SEO Score', '85%']],
      ['data' => ['Focus Keyword', '"Drupal 10 Module"']],
      ['data' => ['Readability', 'Good']],
      ['data' => ['Meta Description', 'Optimal length']],
      ['data' => ['Internal Links', '5']],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['realtime-seo-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  /**
   * Generates a full report for Google Analytics data of a node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node to analyze.
   *
   * @return array
   *   A render array containing the full Google Analytics report.
   */
  public function googleAnalyticsFullReport(NodeInterface $entity) {
    $header = [
      ['data' => 'Metric', 'class' => ['header']],
      ['data' => 'Value', 'class' => ['header']],
    ];
    $rows = [
      ['data' => ['Page Views', '1234']],
      ['data' => ['Bounce Rate', '45%']],
      ['data' => ['Average Time on Page', '2 minutes']],
      ['data' => ['New Users', '345']],
      ['data' => ['Returning Users', '789']],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['google-analytics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  /**
   * Generates a full accessibility report for a node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node to analyze.
   *
   * @return array
   *   A render array containing the full accessibility report.
   */
  public function accessibilityFullReport(NodeInterface $entity) {
    $header = [
      ['data' => 'Metric', 'class' => ['header']],
      ['data' => 'Value', 'class' => ['header']],
    ];
    $rows = [
      ['data' => ['Valid HTML', 'Yes']],
      ['data' => ['Alt Text for Images', '95% complete']],
      ['data' => ['Headings Structure', 'Proper']],
      ['data' => ['ARIA Roles', 'All present']],
      ['data' => ['Contrast Ratio', 'Pass']],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['accessibility-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

}
