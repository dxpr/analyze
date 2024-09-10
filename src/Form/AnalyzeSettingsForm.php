<?php

declare(strict_types=1);

namespace Drupal\analyze\Form;

use Drupal\analyze\AnalyzeTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Analyze settings for this site.
 */
final class AnalyzeSettingsForm extends ConfigFormBase {

  use AnalyzeTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'analyze_analyze_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['analyze.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $values = $this->config('analyze.settings')->get('status') ?? [];

    if ($plugins = $this->getPlugins()) {
      foreach ($this->entityTypeManager()->getDefinitions() as $entity_type) {
        if ($entity_type->hasLinkTemplate('canonical')) {
          $id = $entity_type->id();
          $form[$id] = [
            '#type' => 'details',
            '#title' => $entity_type->getLabel(),
            '#open' => !empty($values[$id]),
            '#parents' => ['analyze'],
          ];

          if ($type = $entity_type->getBundleEntityType()) {
            foreach ($this->getEntityBundles($id) as $bundle => $data) {
              $form[$id][$bundle] = [
                '#type' => 'details',
                '#title' => $bundle,
                '#open' => isset($values[$id][$bundle]),
                '#parents' => ['analyze', $id],
              ];
            }
          }
          else {
            $form[$id][$id] = [
              '#type' => 'details',
              '#title' => $entity_type->getLabel(),
              '#open' => isset($values[$id][$id]),
              '#parents' => ['analyze', $id],
            ];
          }

          foreach ($form[$id] as $key => &$value) {
            foreach ($plugins as $plugin_id => $plugin) {
              $value[$plugin_id] = [
                '#type' => 'checkbox',
                '#title' => $plugin->label(),
                '#default_value' => isset($config[$id][$key][$plugin_id]),
                '#parents' => ['analyze', $id, $key],
              ];
            }
          }
        }
      }
    }
    else {
      $form = [
        '#markup' => $this->t('You don\'t currently have any Analyze plugins available: please enable one or modules implementing the plugins.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('analyze.settings')
      ->set('example', $form_state->getValue('example'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
