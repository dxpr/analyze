<?php

declare(strict_types=1);

namespace Drupal\analyze;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Base class for analyze plugins.
 */
abstract class AnalyzePluginBase extends PluginBase implements AnalyzeInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Creates the plugin.
   *
   * @param array<string, mixed> $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array<string, mixed> $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\analyze\HelperInterface $helper
   *   Analyze helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $configFactory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HelperInterface $helper,
    protected AccountProxyInterface $currentUser,
    protected ?ConfigFactoryInterface $configFactory = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * Gets the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  protected function getConfigFactory(): ConfigFactoryInterface {
    if (!$this->configFactory) {
      $this->configFactory = \Drupal::service('config.factory');
    }
    return $this->configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    $entity_type = $entity->getEntityTypeId();

    return Url::fromRoute('analyze.' . $entity_type . '.' . $this->getPluginId(), [$entity_type => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function fullReportUrlOverridden(EntityInterface $entity): bool {
    $return = TRUE;

    if ($url = $this->getFullReportUrl($entity)) {
      $entity_type = $entity->getEntityTypeId();

      if ($url->isRouted() && $url->getRouteName() == 'analyze.' . $entity_type . '.' . $this->getPluginId()) {
        $return = FALSE;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity): bool {
    $return = FALSE;

    if ($config = $this->helper->getConfig()->get('status')) {
      $return = !empty($config[$entity->getEntityTypeId()][$entity->bundle()][$this->getPluginId()]);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type, ?string $bundle = NULL): bool {
    // Default to all entity types and bundles being accessible.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    // Default to having access.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function extraSummaryLinks(EntityInterface $entity): array {
    return [];
  }

  /**
   * Gets the entity-specific settings for this analyzer.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle ID.
   *
   * @return array
   *   The settings for this analyzer on the given entity type and bundle.
   */
  protected function getEntityTypeSettings(string $entity_type_id, ?string $bundle = NULL): array {
    $config = $this->getConfigFactory()->get('analyze.entity_settings');
    $settings = [];
    
    // Get entity type level settings
    $type_settings = $config->get("$entity_type_id.analyzers." . $this->getPluginId()) ?: [];
    $settings = $type_settings;
    
    // Get bundle level settings if specified
    if ($bundle !== NULL) {
      $bundle_settings = $config->get("$entity_type_id.$bundle.analyzers." . $this->getPluginId()) ?: [];
      // Merge bundle settings over type settings, preserving defaults
      $settings = array_replace_recursive($settings, $bundle_settings);
    }
    
    return $settings;
  }

  /**
   * Checks if this analyzer is enabled for a specific entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle ID.
   *
   * @return bool
   *   TRUE if the analyzer is enabled for this entity type and bundle.
   */
  public function isEnabledForEntityType(string $entity_type_id, ?string $bundle = NULL): bool {
    $settings = $this->getEntityTypeSettings($entity_type_id, $bundle);
    return $settings['enabled'] ?? TRUE;
  }

  /**
   * Gets the settings form for this analyzer for a specific entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle ID.
   *
   * @return array
   *   A form array to be included in the entity type's analyze settings.
   */
  public function getEntityTypeSettingsForm(string $entity_type_id, ?string $bundle = NULL): array {
    $settings = $this->getEntityTypeSettings($entity_type_id, $bundle);
    $plugin_id = $this->getPluginId();

    // Get the enabled state from the status config
    $status_config = $this->getConfigFactory()->get('analyze.settings');
    $status = $status_config->get('status') ?? [];
    $is_enabled = isset($status[$entity_type_id][$bundle][$plugin_id]);

    // Get the plugin settings from plugin_settings config
    $plugin_settings_config = $this->getConfigFactory()->get('analyze.plugin_settings');
    $key = sprintf('%s.%s.%s', $entity_type_id, $bundle, $plugin_id);
    $plugin_settings = $plugin_settings_config->get($key) ?? [];

    $form = [
      'enabled' => [
        '#type' => 'checkbox',
        '#title' => $this->t('@analyzer', ['@analyzer' => $this->getPluginDefinition()['label']]),
        '#description' => $this->getPluginDefinition()['description'],
        '#default_value' => $is_enabled,
      ],
    ];

    // Add configurable settings if defined
    $configurable_settings = $this->getConfigurableSettings();
    foreach ($configurable_settings as $group_key => $group) {
      $form[$group_key] = [
        '#type' => $group['type'],
        '#title' => $group['title'],
        '#description' => $group['description'] ?? '',
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="analyze[' . $plugin_id . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      foreach ($group['settings'] as $setting_key => $setting) {
        $form[$group_key][$setting_key] = [
          '#type' => $setting['type'],
          '#title' => $setting['title'],
          '#default_value' => $plugin_settings[$group_key][$setting_key] ?? ($setting['default_value'] ?? NULL),
        ];
      }
    }

    return $form;
  }

  /**
   * Gets the configurable settings for this analyzer.
   *
   * @return array
   *   The configurable settings structure.
   */
  public function getConfigurableSettings(): array {
    return [];
  }

  /**
   * Saves the entity type settings for this analyzer.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $settings
   *   The settings to save.
   * @param string|null $bundle
   *   The bundle ID.
   */
  protected function saveEntityTypeSettings(string $entity_type_id, array $settings, ?string $bundle = NULL): void {
    $config = $this->getConfigFactory()->getEditable('analyze.entity_settings');
    
    if ($bundle !== NULL) {
      $config->set("$entity_type_id.$bundle.analyzers." . $this->getPluginId(), $settings);
    }
    else {
      $config->set("$entity_type_id.analyzers." . $this->getPluginId(), $settings);
    }
    
    $config->save();
  }

  /**
   * Saves the settings for this analyzer.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle ID.
   * @param array $settings
   *   The settings to save.
   */
  public function saveSettings(string $entity_type_id, ?string $bundle, array $settings): void {
    $config = \Drupal::configFactory()->getEditable('analyze.settings');
    $current = $config->get('status') ?? [];

    // Save enabled state
    if (isset($settings['enabled'])) {
      $current[$entity_type_id][$bundle][$this->getPluginId()] = $settings['enabled'];
      $config->set('status', $current)->save();
    }

    // Save detailed settings if present
    if (isset($settings['settings'])) {
      $detailed_config = \Drupal::configFactory()->getEditable('analyze.plugin_settings');
      $key = sprintf('%s.%s.%s', $entity_type_id, $bundle, $this->getPluginId());
      $detailed_config->set($key, $settings['settings'])->save();
    }
  }

}
