<?php

declare(strict_types=1);

namespace Drupal\analyze\Form;

use Drupal\analyze\HelperInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Analyze settings for this site.
 */
final class AnalyzeSettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\analyze\HelperInterface $helper
   *   The Analyze Helper service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected readonly HelperInterface $helper,
    protected $typedConfigManager = NULL,
  ) {
    parent::__construct($config_factory, $this->typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('analyze.helper'),
      $container->get('config.typed')
    );
  }

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

    if ($plugins = $this->helper->getPlugins()) {
      $form['welcome'] = [
        '#markup' => $this->t('<p>Enable or disabled the Analyze plugins for each entity with a canonical URL using the form below.</p>'),
      ];
      foreach ($this->helper->getEntityDefinitions() as $entity_type) {
        if ($entity_type->hasLinkTemplate('canonical')) {
          $id = $entity_type->id();
          $form[$id] = [
            '#type' => 'details',
            '#title' => $entity_type->getLabel(),
            '#open' => !empty($values[$id]),
            '#parents' => ['analyze', $id],
          ];

          if ($entity_type->getBundleEntityType()) {
            foreach ($this->helper->getEntityBundles($id) as $bundle => $data) {
              $form[$id][$bundle] = [
                '#type' => 'details',
                '#title' => $data['label'],
                '#open' => isset($values[$id][$bundle]),
                '#parents' => ['analyze', $id, $bundle],
              ];
            }
          }
          else {
            $form[$id][$id] = [
              '#type' => 'details',
              '#title' => $entity_type->getLabel(),
              '#open' => isset($values[$id][$id]),
              '#parents' => ['analyze', $id, $id],
            ];
          }

          foreach (Element::children($form[$id]) as $key) {
            foreach ($plugins as $plugin_id => $plugin) {
              $form[$id][$key][$plugin_id] = [
                '#type' => 'checkbox',
                '#title' => $plugin->label(),
                '#default_value' => isset($values[$id][$key][$plugin_id]),
                '#parents' => ['analyze', $id, $key, $plugin_id],
              ];
            }
          }
        }
      }
    }
    else {
      $form = [
        '#markup' => $this->t("You don't currently have any Analyze plugins available: please enable one or modules implementing the plugins."),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->cleanValues()->getValues();
    $settings = [];

    foreach ($values['analyze'] as $entity_type => $data) {
      foreach ($data as $bundle => $value) {
        foreach ($value as $plugin_id => $setting) {
          if ($setting) {
            $settings[$entity_type][$bundle][$plugin_id] = $setting;
          }
        }
      }
    }

    $this->config('analyze.settings')
      ->set('status', $settings)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
