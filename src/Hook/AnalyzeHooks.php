<?php

namespace Drupal\analyze\Hook;

use Drupal\analyze\AnalyzePluginManager;
use Drupal\analyze\Helper;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for analyze.
 */
class AnalyzeHooks {
  use StringTranslationTrait;

  /**
   * Constructs the hook service.
   */
  public function __construct(
    protected AnalyzePluginManager $pluginManagerAnalyze,
    protected Helper $analyzeHelper,
  ) {}

  /**
   * Implements hook_theme().
   *
   * Defines the theme for the analyze gauge.
   *
   * @phpstan-return array<string, array<string, mixed>>
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'analyze_gauge' => [
        'variables' => [
          'caption' => NULL,
          'range_min_label' => NULL,
          'range_mid_label' => NULL,
          'range_max_label' => NULL,
          'range_min' => NULL,
          'value' => NULL,
          'display_value' => NULL,
          'range_max' => NULL,
        ],
      ],
      'analyze_circular_gauge' => [
        'variables' => [
          'caption' => NULL,
          'range_min_label' => NULL,
          'range_mid_label' => NULL,
          'range_max_label' => NULL,
          'range_min' => NULL,
          'value' => NULL,
          'display_value' => NULL,
          'range_max' => NULL,
        ],
      ],
      'analyze_table' => [
        'variables' => [
          'table_title' => NULL,
          'rows' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_local_tasks_alter().
   *
   * @phpstan-param array<string, array<string, mixed>> $local_tasks
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(array &$local_tasks): void {
    /** @var \Drupal\analyze\AnalyzePluginManager $service */
    $service = $this->pluginManagerAnalyze;
    // Add local actions for all entities if we have plugins enabled.
    if ($plugins = $service->getDefinitions()) {
      foreach ($this->analyzeHelper->getEntityDefinitions() as $entity_type) {
        $id = $entity_type->id();
        $analyze_route = 'entity.' . $id . '.analyze';
        // The analyze route.
        $local_tasks[$analyze_route] = [
          'id' => $analyze_route,
          'title' => $this->t('Analyze'),
          'route_name' => $analyze_route,
          'route_parameters' => [],
          'base_route' => 'entity.' . $id . '.canonical',
          'class' => 'Drupal\Core\Menu\LocalTaskDefault',
          'provider' => 'analyze',
          'options' => [],
          'weight' => 0,
        ];
        // The default secondary tab.
        $default = $analyze_route . '.default';
        $local_tasks[$default] = [
          'id' => $default,
          'title' => $this->t('Summary'),
          'route_name' => $analyze_route,
          'route_parameters' => [],
          'base_route' => 'entity.' . $id . '.canonical',
          'class' => 'Drupal\Core\Menu\LocalTaskDefault',
          'provider' => 'analyze',
          'parent_id' => $analyze_route,
          'options' => [],
          'weight' => 0,
        ];
        // Add secondary tabs for all entities if we have plugins enabled.
        if ($service->getDefinitions()) {
          foreach ($plugins as $plugin) {
            $route = 'analyze.' . $id . '.' . $plugin['id'];
            $local_tasks[$route] = [
              'id' => $route,
              'title' => $plugin['label'],
              'route_name' => $route,
              'route_parameters' => [],
              'class' => 'Drupal\Core\Menu\LocalTaskDefault',
              'parent_id' => $analyze_route,
              'provider' => 'analyze',
              'options' => [],
              'base_route' => 'entity.' . $id . '.canonical',
            ];
          }
        }
      }
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-param \Drupal\Core\Form\FormStateInterface $form_state
   * @phpstan-param string $form_id
   *
   * @param-out array<string, mixed> $form
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id !== 'analyze_analyze_settings') {
      $is_bundle_form = $entity_type = $bundle = FALSE;
      $form_object = $form_state->getFormObject();
      // User's aren't bundle-able, but we want the opportunity to include them in
      // insights so we'll hard-code the form id.
      if ($form_id == 'user_admin_settings') {
        $is_bundle_form = TRUE;
        $entity_type = 'user';
        $bundle = 'user';
      }
      elseif ($form_object instanceof EntityFormInterface) {
        $entity = $form_object->getEntity();
        $type = $entity->getEntityType();
        if ($entity_type = $type->getBundleOf()) {
          $is_bundle_form = TRUE;
          $bundle = $entity->id();
        }
      }
      if ($is_bundle_form) {
        analyze_add_settings_form($form, $entity_type, $bundle);
        $form_state->set('analyze.entity_type', $entity_type);
        $form_state->set('analyze.bundle', $bundle);
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * @phpstan-param array<string, mixed> $variables
   */
  #[Hook('preprocess_analyze_table')]
  public function preprocessAnalyzeTable(array &$variables): void {
    if (!empty($variables['rows'])) {
      foreach ($variables['rows'] as $key => $row) {
        // Make sure to case row as string.
        $variables['rows'][$key]['data'] = (string) $row['data'];
      }
    }
  }

}
