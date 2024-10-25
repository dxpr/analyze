<?php

namespace Drupal\analyze\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzeInterface;
use Drupal\analyze\HelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Analyze module.
 */
class AnalyzeController extends ControllerBase {

  /**
   * Constructs the controller.
   *
   * @param \Drupal\analyze\HelperInterface $helper
   *   The Analyze Helper service.
   */
  public function __construct(
    protected readonly HelperInterface $helper,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('analyze.helper')
    );
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
  public function analyze(?string $plugin = NULL, ?string $entity_type = NULL, bool $full_report = FALSE): array {
    if ($plugin) {
      $plugins = $this->helper->getPlugins([$plugin]);
    }
    else {
      $plugins = $this->helper->getPlugins();
    }

    $entity = $this->helper->getEntity($entity_type);
    $build = [];
    $weight = 0;

    foreach ($plugins as $id => $plugin) {
      // It should be enabled and the user should have access to it.
      if ($plugin->isEnabled($entity) && $plugin->access($entity)) {
        if ($plugin_data = $this->validatePluginData($plugin, $entity, $full_report)) {
          $build[$id . '-title'] = [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $plugin->label(),
            '#weight' => $weight,
          ];
          $weight++;
          $build[$id] = $plugin_data;
          $build[$id]['#weight'] = $weight;
          $weight++;

          if (!$full_report && $url = $plugin->getFullReportUrl($entity)) {
            $build[$id . '-link'] = [
              '#type' => 'link',
              '#title' => $this->t('View full report of this page'),
              '#url' => $url,
              '#attributes' => [
                'class' => [
                  'action-link',
                  Html::cleanCssIdentifier('view-' . $id . '-report'),
                ],
              ],
              '#weight' => $weight,
            ];

            $weight++;
          }
          elseif ($full_report) {
            $build[$id . '-back'] = [
              '#type' => 'link',
              '#title' => $this->t('Back to the Summary'),
              '#url' => Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.analyze', [
                $entity->getEntityTypeId() => $entity->id(),
              ]),
              '#attributes' => [
                'class' => [
                  'action-link',
                  Html::cleanCssIdentifier('view-' . $id . '-back'),
                  'analyze-back',
                ],
              ],
              '#weight' => $weight,
            ];

            $weight++;
          }
          // If there are extra links for the summary, add them.
          if (!$full_report && $links = $plugin->extraSummaryLinks($entity)) {
            foreach ($links as $key => $link) {
              $build[$id . '-extra-link-' . $key] = [
                '#type' => 'link',
                '#title' => $link['title'],
                '#url' => $link['url'],
                '#attributes' => [
                  'class' => [
                    'action-link',
                    Html::cleanCssIdentifier('view-' . $id . '-report'),
                  ],
                ],
                '#weight' => $weight,
              ];

              $weight++;
            }
          }
        }
        elseif (!$full_report) {

          // Throw an exception if this is a summary as the plugin is malformed.
          throw new InvalidPluginDefinitionException($id, 'Plugin does not return an approved render array type for its summary.');
        }
      }
    }

    // Ensure reports don't get cached for the wrong entities.
    $build['#cache']['contexts'][] = 'url';
    $build['#cache']['tags'] = $entity->getCacheTags();

    return $build;
  }

  /**
   * Helper to ensure a plugin summary returns an allowed render array.
   *
   * @param \Drupal\analyze\AnalyzeInterface $plugin
   *   The Analyze plugin.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the report for.
   * @param bool $full_report
   *   Whether this is the full report or the summary.
   *
   * @return array<string, mixed>|null
   *   The validated data, or NULL if it fails.
   */
  private function validatePluginData(AnalyzeInterface $plugin, EntityInterface $entity, bool $full_report): ?array {
    $return = NULL;

    if ($full_report) {

      // The full report can contain any data, so we need no further validation.
      $return = $plugin->renderFullReport($entity);
    }
    else {
      if ($plugin_data = $plugin->renderSummary($entity)) {
        if (isset($plugin_data['#theme'])) {

          // Summaries can only return a gauge or a table so we can enforce a
          // three item limit on the render array.
          if ($plugin_data['#theme'] == 'analyze_gauge' || $plugin_data['#theme'] == 'analyze_table') {
            $return = $plugin_data;
          }
        }
      }
    }

    return $return;
  }

}
