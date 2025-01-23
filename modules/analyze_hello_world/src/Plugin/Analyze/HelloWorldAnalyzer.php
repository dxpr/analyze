<?php

namespace Drupal\analyze_hello_world\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * A simple hello world analyzer that responds to "Marco?".
 *
 * @Analyze(
 *   id = "hello_world_analyzer",
 *   label = @Translation("Hello World Analyzer"),
 *   description = @Translation("A simple analyzer that responds to Marco?")
 * )
 */
final class HelloWorldAnalyzer extends AnalyzePluginBase {

  /**
   * The AI provider manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\ai\AiProviderPluginManager $aiProvider
   *   The AI provider manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $helper,
    $currentUser,
    AiProviderPluginManager $aiProvider,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper, $currentUser);
    $this->aiProvider = $aiProvider;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('analyze.helper'),
      $container->get('current_user'),
      $container->get('ai.provider'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $response = $this->getAiResponse();

    return [
      '#theme' => 'analyze_table',
      '#table_title' => 'Hello World Response',
      '#rows' => [
        [
          'label' => 'Query',
          'data' => 'Marco?',
        ],
        [
          'label' => 'AI Response',
          'data' => $response,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    $response = $this->getAiResponse();

    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Hello World Full Report'),
      'content' => [
        '#markup' => $this->t('Query: Marco?<br>Response: @response', ['@response' => $response]),
      ],
    ];
  }

  /**
   * Get response from AI provider.
   *
   * @return string
   *   The AI response.
   */
  protected function getAiResponse(): string {
    try {
      // Check if we have any chat providers available
      if (!$this->aiProvider->hasProvidersForOperationType('chat', TRUE)) {
        return 'Polo! (No chat providers available)';
      }

      // Get the default provider for chat
      $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
      if (empty($defaults['provider_id']) || empty($defaults['model_id'])) {
        return 'Polo! (No default chat provider configured)';
      }

      // Initialize AI provider
      $ai_provider = $this->aiProvider->createInstance($defaults['provider_id']);

      // Set system message
      $ai_provider->setChatSystemRole('You are a friendly AI that plays Marco Polo. When someone says "Marco?", you respond with a variation of "Polo!"');

      // Create chat message
      $chat_array = [
        new ChatMessage('user', 'Marco?'),
      ];

      // Get response
      $messages = new ChatInput($chat_array);
      $message = $ai_provider->chat($messages, $defaults['model_id'])->getNormalized();
      return trim($message->getText()) ?? 'Polo! (Default response)';
    }
    catch (\Exception $e) {
      return 'Polo! (Error: ' . $e->getMessage() . ')';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type, ?string $bundle = NULL): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity): bool {
    return $this->currentUser->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    return parent::getFullReportUrl($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function extraSummaryLinks(EntityInterface $entity): array {
    return [];
  }

} 