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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface;

/**
 * A sentiment analyzer that uses AI to analyze content sentiment.
 *
 * @Analyze(
 *   id = "hello_world_analyzer",
 *   label = @Translation("Content Sentiment Analyzer"),
 *   description = @Translation("Analyzes the sentiment of content using AI.")
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
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The prompt JSON decoder service.
   *
   * @var \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface
   */
  protected PromptJsonDecoderInterface $promptJsonDecoder;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface $promptJsonDecoder
   *   The prompt JSON decoder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $helper,
    $currentUser,
    AiProviderPluginManager $aiProvider,
    ConfigFactoryInterface $config_factory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected LanguageManagerInterface $languageManager,
    MessengerInterface $messenger,
    PromptJsonDecoderInterface $promptJsonDecoder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper, $currentUser);
    $this->aiProvider = $aiProvider;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->promptJsonDecoder = $promptJsonDecoder;
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
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('ai.prompt_json_decode'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    $scores = $this->analyzeSentiment($entity);
    
    // Convert -1 to +1 range to 0 to 1 for gauge
    $gauge_value = ($scores['sentiment'] + 1) / 2;
    
    // For summary, we'll just show the main sentiment gauge
    return [
      '#theme' => 'analyze_gauge',
      '#caption' => $this->t('Content Sentiment'),
      '#range_min_label' => $this->t('Negative (-1.0)'),
      '#range_mid_label' => $this->t('Neutral (0.0)'),
      '#range_max_label' => $this->t('Positive (+1.0)'),
      '#range_min' => 0,
      '#range_max' => 1,
      '#value' => $gauge_value,
      '#display_value' => sprintf('%+.1f', $scores['sentiment']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFullReport(EntityInterface $entity): array {
    $scores = $this->analyzeSentiment($entity);
    
    // Convert all -1 to +1 ranges to 0 to 1 for gauges
    $gauge_values = [
      'sentiment' => ($scores['sentiment'] + 1) / 2,
      'engagement' => ($scores['engagement'] + 1) / 2,
      'trust' => ($scores['trust'] + 1) / 2,
      'objectivity' => ($scores['objectivity'] + 1) / 2,
      'complexity' => ($scores['complexity'] + 1) / 2,
    ];

    return [
      '#type' => 'details',
      '#title' => $this->t('Content Analysis Report'),
      '#open' => TRUE,
      'content' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['analyze-full-report'],
        ],
        'sentiment' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['analyze-gauge-wrapper'],
          ],
          'gauge' => [
            '#theme' => 'analyze_gauge',
            '#caption' => $this->t('Content Sentiment'),
            '#range_min_label' => $this->t('Negative (-1.0)'),
            '#range_mid_label' => $this->t('Neutral (0.0)'),
            '#range_max_label' => $this->t('Positive (+1.0)'),
            '#range_min' => 0,
            '#range_max' => 1,
            '#value' => $gauge_values['sentiment'],
            '#display_value' => sprintf('%+.1f', $scores['sentiment']),
          ],
        ],
        'engagement' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['analyze-gauge-wrapper'],
          ],
          'gauge' => [
            '#theme' => 'analyze_gauge',
            '#caption' => $this->t('Engagement Level'),
            '#range_min_label' => $this->t('Passive (-1.0)'),
            '#range_mid_label' => $this->t('Balanced (0.0)'),
            '#range_max_label' => $this->t('Engaging (+1.0)'),
            '#range_min' => 0,
            '#range_max' => 1,
            '#value' => $gauge_values['engagement'],
            '#display_value' => sprintf('%+.1f', $scores['engagement']),
          ],
        ],
        'trust' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['analyze-gauge-wrapper'],
          ],
          'gauge' => [
            '#theme' => 'analyze_gauge',
            '#caption' => $this->t('Trust/Credibility'),
            '#range_min_label' => $this->t('Promotional (-1.0)'),
            '#range_mid_label' => $this->t('Neutral (0.0)'),
            '#range_max_label' => $this->t('Credible (+1.0)'),
            '#range_min' => 0,
            '#range_max' => 1,
            '#value' => $gauge_values['trust'],
            '#display_value' => sprintf('%+.1f', $scores['trust']),
          ],
        ],
        'objectivity' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['analyze-gauge-wrapper'],
          ],
          'gauge' => [
            '#theme' => 'analyze_gauge',
            '#caption' => $this->t('Objectivity'),
            '#range_min_label' => $this->t('Subjective (-1.0)'),
            '#range_mid_label' => $this->t('Balanced (0.0)'),
            '#range_max_label' => $this->t('Objective (+1.0)'),
            '#range_min' => 0,
            '#range_max' => 1,
            '#value' => $gauge_values['objectivity'],
            '#display_value' => sprintf('%+.1f', $scores['objectivity']),
          ],
        ],
        'complexity' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['analyze-gauge-wrapper'],
          ],
          'gauge' => [
            '#theme' => 'analyze_gauge',
            '#caption' => $this->t('Complexity'),
            '#range_min_label' => $this->t('Simple (-1.0)'),
            '#range_mid_label' => $this->t('Moderate (0.0)'),
            '#range_max_label' => $this->t('Complex (+1.0)'),
            '#range_min' => 0,
            '#range_max' => 1,
            '#value' => $gauge_values['complexity'],
            '#display_value' => sprintf('%+.1f', $scores['complexity']),
          ],
        ],
      ],
    ];
  }

  /**
   * Helper to get the rendered entity content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   *
   * @return string
   *   A HTML string of rendered content.
   *
   * @throws \Exception
   */
  private function getHtml(EntityInterface $entity): string {
    // Get the current active langcode from the site.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Get the rendered entity view in default mode.
    $view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, 'default', $langcode);
    $rendered = $this->renderer->render($view);

    // Handle both string and Markup object cases
    return is_object($rendered) && method_exists($rendered, '__toString') 
        ? $rendered->__toString() 
        : (string) $rendered;
  }

  /**
   * Analyze the sentiment of entity content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to analyze.
   *
   * @return array
   *   Array with sentiment scores.
   */
  protected function analyzeSentiment(EntityInterface $entity): array {
    try {
      // Check if we have any chat providers available
      if (!$this->aiProvider->hasProvidersForOperationType('chat', TRUE)) {
        $this->messenger->addWarning('Debug: No chat providers available');
        return [
          'sentiment' => 0.0,
          'engagement' => 0.0,
          'trust' => 0.0,
          'objectivity' => 0.0,
          'complexity' => 0.0,
        ];
      }

      // Get the default provider for chat
      $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
      if (empty($defaults['provider_id']) || empty($defaults['model_id'])) {
        $this->messenger->addWarning('Debug: No default provider configured. Provider ID: ' . ($defaults['provider_id'] ?? 'none') . ', Model ID: ' . ($defaults['model_id'] ?? 'none'));
        return [
          'sentiment' => 0.0,
          'engagement' => 0.0,
          'trust' => 0.0,
          'objectivity' => 0.0,
          'complexity' => 0.0,
        ];
      }

      // Initialize AI provider
      $ai_provider = $this->aiProvider->createInstance($defaults['provider_id']);

      // Get content
      $content = strip_tags($this->getHtml($entity));
      $content = str_replace('&nbsp;', ' ', $content);
      $content = trim($content);

      if (empty($content)) {
        $this->messenger->addWarning('Debug: No content available for analysis');
        return [
          'sentiment' => 0.0,
          'engagement' => 0.0,
          'trust' => 0.0,
          'objectivity' => 0.0,
          'complexity' => 0.0,
        ];
      }

      $this->messenger->addStatus('Debug: Content to analyze: ' . substr($content, 0, 100) . '...');

      // Configure provider with low temperature for more consistent results
      $config = [
        'temperature' => 0.2,
      ];
      $ai_provider->setConfiguration($config);

      // Create chat message with explicit JSON request
      $prompt = $content;
      $prompt .= "\n\nAnalyze this content and provide scores. Respond with a simple JSON object containing only the required scores:\n{\"sentiment\": number, \"engagement\": number, \"trust\": number, \"objectivity\": number, \"complexity\": number}";
      
      $chat_array = [
        new ChatMessage('user', $prompt),
      ];

      // Get response
      $messages = new ChatInput($chat_array);
      $message = $ai_provider->chat($messages, $defaults['model_id'])->getNormalized();
      $raw_response = $message->getText();
      $this->messenger->addStatus('Debug: Raw AI response: ' . $raw_response);
      
      // Use the injected PromptJsonDecoder service
      $decoded = $this->promptJsonDecoder->decode($message);
      
      // If we couldn't decode the JSON at all
      if (!is_array($decoded)) {
        $this->messenger->addError('Debug: Could not decode JSON response');
        return [];
      }
      
      // All scores should be directly in the decoded array
      return array_filter([
        'sentiment' => $decoded['sentiment'] ?? NULL,
        'engagement' => $decoded['engagement'] ?? NULL,
        'trust' => $decoded['trust'] ?? NULL,
        'objectivity' => $decoded['objectivity'] ?? NULL,
        'complexity' => $decoded['complexity'] ?? NULL,
      ], function($value) { return $value !== NULL; });

    } catch (\Exception $e) {
      $this->messenger->addError('Debug: Error analyzing sentiment: ' . $e->getMessage());
      return [];
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