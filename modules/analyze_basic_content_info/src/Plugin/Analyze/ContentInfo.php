<?php

declare(strict_types=1);

namespace Drupal\analyze_basic_content_info\Plugin\Analyze;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\analyze\AnalyzePluginBase;
use Drupal\analyze\HelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analyze plugin to display Basic data.
 *
 * @Analyze(
 *   id = "content_info",
 *   label = @Translation("Basic Content Info"),
 *   description = @Translation("Provides basic statistics about content for Analyzer.")
 * )
 */
final class ContentInfo extends AnalyzePluginBase {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelperInterface $helper,
    AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $helper, $currentUser);
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
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderSummary(EntityInterface $entity): array {
    return [
      '#theme' => 'analyze_table',
      '#table_title' => 'Basic Info',
      '#rows' => [
        [
          'label' => 'Word count',
          'data' => $this->getWordCount($entity),
        ],
        [
          'label' => 'Image count',
          'data' => $this->getImageCount($entity),
        ],
      ],
    ];
  }

  /**
   * Helper to calculate a rough word count for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a word count for.
   *
   * @return int
   *   The counted words. Defaults to 0.
   *
   * @throws \Exception
   */
  private function getWordCount(EntityInterface $entity): int {
    $rendered = strip_tags($this->getHtml($entity));

    // Non-breaking spaces are getting counted as words, so strip them out to
    // improve the accuracy of the count.
    $rendered = str_replace('&nbsp;', ' ', $rendered);
    return str_word_count($rendered);
  }

  /**
   * Helper to get a count of images attached to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to count images on.
   *
   * @return int
   *   The number of images. Defaults to 0.
   *
   * @throws \Exception
   */
  private function getImageCount(EntityInterface $entity): int {
    $return = 0;

    $render = $this->getHtml($entity);

    $matches = [];
    preg_match_all('/<img/', $render, $matches);

    if (isset($matches[0])) {
      $return = count($matches[0]);
    }

    return $return;
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
   * {@inheritdoc}
   */
  public function getFullReportUrl(EntityInterface $entity): ?Url {
    return NULL;
  }

}
