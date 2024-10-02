<?php

declare(strict_types=1);

namespace Drupal\analyze_plugin_example\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;

/**
 * Analyze plugin to display Example data.
 *
 * @Analyze(
 *   id = "example",
 *   label = @Translation("Example Entity Reports"),
 *   description = @Translation("Provides an example plugin implementation for Analyzer.")
 * )
 *
 * The base Analyze module will create a summary page for every entity on the
 * Drupal site with a canonical URL property. It will also create a full
 * report URL for every enabled plugin that does not override the base
 * Plugin's getFullReportUrl() method. To create a basic Analyze plugin, all
 * that is required is to create your own version of this file in your
 * module's /src/Plugin/Analyze folder and implement renderSummary() and
 * renderFullReport() methods that return valid Drupal render arrays.
 */
final class Example extends AnalyzePluginBase {

  /**
   * {@inheritdoc}
   *
   * This method is used to return a summary to display on an entity's Analyze
   * report page. Max 3 pieces of information can be shown - any large amounts
   * of data should be restricted to the plugin's full report page.
   */
  public function renderSummary(EntityInterface $entity): array {

    // This method must return a valid Drupal render array. This will be wrapped
    // in a "fieldset" render array by the base module, and displayed on the
    // summary page for the entity. This can be any valid render array: this
    // example describes a table.
    // First, you will have to implement your own methods to create your data
    // from the provided entity as required.
    $data = $this->getData($entity);

    // Then that data will need to be formatted into either an analyze_gauge or
    // analyze_table render array: any other type of render array will throw an
    // Exception when the summary tab is viewed. For an example of an
    // analyze_gauge render array, please see $this->renderFullReport().
    $build = [
      '#theme' => 'analyze_table',
      '#table_title' => 'Example Table',
    ];

    $key = 0;

    // The analyze_table only allows three rows of two columns for a summary, in
    // order to keep the summary page usable. All summary data must be adapted
    // to fit this structure or it will be ignored.
    foreach ($data as $label => $value) {
      $name = match ($key) {
        0 => 'one',
        1 => 'two',
        default => 'three',
      };

      $build['#row_' . $name] = [
        'label' => $label,
        'data' => $value,
      ];

      $key++;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * By default, this method will return and empty array. If you intend to
   * display additional metrics in a dedicated page, you can override this
   * method to return a valid render array of data and it will be displayed by
   * default at /<entity_type>/<entity_id>/analyze/<plugin_id>.
   *
   * If you do not wish to display a full report at the default URL, you MUST
   * override the getFullReportUrl() method to return either an alternate URL or
   * a NULL value to disable the default URL: once that is done, you can remove
   * this method completely from your plugin. Failure to do so will result in an
   * empty page being displayed at the default URL.
   */
  public function renderFullReport(EntityInterface $entity): array {

    // Again, you will need to implement your own methods to obtain the data you
    // wish to display on the full report page.
    $data = $this->getData($entity);

    // The default Full Report page allows the return of any valid Drupal render
    // array.
    $build = [
      '#type' => 'fieldset',
      '#title' => $this->t('Example Full Report'),
    ];

    // One you have your data, you can format it into a render array.
    foreach ($data as $key => $values) {

      // The base Analyze module provides an 'analyze_gauge' theming function
      // which render a three point bar chart and display a value as a point
      // somewhere on the scale. This uses a Drupal Single Directory Components
      // to render the component, and so  can be used in your custom templates
      // if required. See analyze_theme() and the /components folder for the
      // implementation.
      $build[$key] = [
        '#theme' => 'analyze_gauge',
        '#caption' => 'A Caption for Your Chart (string)',
        '#range_min_label' => 'The Label for the Minimum of Your Scale (string)',
        '#range_mid_label' => 'The Label for the Middle of Your Scale (string)',
        '#range_max_label' => 'The Label for the Maximum of Your Scale (string)',
        // The start point of the scale: must be a number.
        '#range_min' => 0,
        // The end point of the scale: must be a number.
        '#range_max' => 1,
        // The value to display on your scale: must be a number.
        '#value' => 0.2,
        '#display_value' => 'The Label to Display with Your #value (string)',
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * This method overrides the default Full Report URL for this plugin. If you
   * have implemented $this::renderFullReport(), you can remove this from your
   * plugin. If you have decided not to use the default full report, you MUST
   * override this method in your plugin: if you return a valid Drupal Url
   * object, this URL will be displayed on the entity's Analyze summary but the
   * link will NOT be shown as a local task for the page. If you return NULL,
   * the default URL will be disabled and your plugin will show no Full Report
   * URL in the summary.
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {

    // If you want to use an alternative Url, you can create it yourself using
    // Drupal's existing options. If you use a custom route, your module will
    // need to provide a both it and a controller to display the report. Or to
    // completely disable the Full return, you can just return a NULL.
    if ($data = $this->getData($entity)) {
      return parent::getFullReportUrl($entity);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * This method exists to calculate if the plugin is enabled for an entity
   * type. In most cases, you can remove this from your own plugin and rely on
   * the default implementation.
   */
  public function isEnabled(EntityInterface $entity): bool {

    // If you wanted to always disable this plugin for a specific entity type
    // regardless of the user's settings, you can override the implementation
    // and perform your own calculation.
    if ($entity->getEntityTypeId() == 'user') {
      return FALSE;
    }
    else {
      return parent::isEnabled($entity);
    }
  }

  /**
   * {@inheritdoc}
   *
   * This method exists to calculate if the plugin is configurable for an entity
   * type or bundle. In most cases you can remove this from your own plugin and
   * rely on the default implementation.
   */
  public function isApplicable(string $entity_type, string $bundle): bool {

    // If you for instance want this plugin to only be configurable for the
    // 'article' bundle of the 'node' entity type, you can override this method
    // and perform your own calculation.
    if ($entity_type == 'node' && $bundle == 'article') {
      return TRUE;
    }
    else {
      return parent::isApplicable($entity_type, $bundle);
    }
  }

  /**
   * {@inheritdoc}
   *
   * This method exists to calculate if the user has access to the plugin. If
   * the plugin is using a third party module, you can usually use that module's
   * permissions to determine access.
   */
  public function access(EntityInterface $entity): bool {

    // If you want to use the permissions from another module, you can override
    // this method and perform your own calculation.
    if ($this->currentUser->hasPermission('access content')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Sample method to retrieve data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The data being analyzed.
   *
   * @return array<string, string>
   *   The label/values pairs of the analyze data.
   */
  private function getData(EntityInterface $entity): array {
    return [
      'Label One' => $entity->id(),
      'Label Two' => 'Data',
      'Label Three' => 'Data',
    ];
  }

}
