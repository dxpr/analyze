<?php

namespace Drupal\analyze\Controller;

use Drupal\analyze\HelperInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
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
    protected readonly HelperInterface $helper
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
  public function analyze(string $plugin = NULL, string $entity_type = NULL, bool $full_report = FALSE): array {
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
        $build[$id . '/wrapper'] = [
          '#type' => 'fieldset',
          '#title' => $plugin->label(),
          '#weight' => $weight,
          $id => ($full_report) ? $plugin->renderFullReport($entity) : $plugin->renderSummary($entity),
        ];

        if (!$full_report && $url = $plugin->getFullReportUrl($entity)) {
          $build[$id . '/wrapper']['full_report'] = [
            '#type' => 'link',
            '#title' => $this->t('View the full report'),
            '#url' => $url,
            '#attributes' => [
              'class' => [
                'action-link',
                Html::cleanCssIdentifier('view-' . $id . '-report'),
              ],
            ],
          ];
        }
        elseif ($full_report) {
          $build[$id . '/wrapper']['back'] = [
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
          ];
        }
        $weight++;
      }
    }

    return $build;
  }

}
