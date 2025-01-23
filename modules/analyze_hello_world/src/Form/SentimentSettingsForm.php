<?php

namespace Drupal\analyze_hello_world\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure sentiment analysis settings.
 */
class SentimentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'analyze_hello_world_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['analyze_hello_world.settings'];
  }

  /**
   * Get default sentiments.
   *
   * @return array
   *   Array of default sentiment configurations.
   */
  public function getDefaultSentiments(): array {
    return [
      'sentiment' => [
        'id' => 'sentiment',
        'label' => 'Content Sentiment',
        'min_label' => 'Negative (-1.0)',
        'mid_label' => 'Neutral (0.0)',
        'max_label' => 'Positive (+1.0)',
      ],
      'engagement' => [
        'id' => 'engagement',
        'label' => 'Engagement Level',
        'min_label' => 'Passive (-1.0)',
        'mid_label' => 'Balanced (0.0)',
        'max_label' => 'Engaging (+1.0)',
      ],
      'trust' => [
        'id' => 'trust',
        'label' => 'Trust/Credibility',
        'min_label' => 'Promotional (-1.0)',
        'mid_label' => 'Neutral (0.0)',
        'max_label' => 'Credible (+1.0)',
      ],
      'objectivity' => [
        'id' => 'objectivity',
        'label' => 'Objectivity',
        'min_label' => 'Subjective (-1.0)',
        'mid_label' => 'Mixed (0.0)',
        'max_label' => 'Objective (+1.0)',
      ],
      'complexity' => [
        'id' => 'complexity',
        'label' => 'Technical Complexity',
        'min_label' => 'Simple (-1.0)',
        'mid_label' => 'Moderate (0.0)',
        'max_label' => 'Complex (+1.0)',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('analyze_hello_world.settings');
    $sentiments = $config->get('sentiments') ?: $this->getDefaultSentiments();

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['sentiment-description']],
      'content' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Configure the sentiment metrics used to analyze content. Each sentiment has a scale from -1.0 to +1.0 with customizable labels.'),
      ],
    ];

    $form['table'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['sentiment-table-container']],
    ];

    $form['table']['sentiments'] = [
      '#type' => 'table',
      '#header' => [
        'id' => $this->t('ID'),
        'label' => $this->t('Label'),
        'min_label' => $this->t('Minimum Label (-1.0)'),
        'mid_label' => $this->t('Middle Label (0.0)'),
        'max_label' => $this->t('Maximum Label (+1.0)'),
        'weight' => $this->t('Weight'),
        'operations' => $this->t('Operations'),
      ],
      '#empty' => $this->t('No sentiments configured yet. Click the "Add sentiment" button above to get started.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sentiment-weight',
        ],
      ],
    ];

    // Sort sentiments by weight
    uasort($sentiments, function ($a, $b) {
      return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
    });

    // Add existing sentiments to the table
    foreach ($sentiments as $id => $sentiment) {
      $form['table']['sentiments'][$id] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        'id' => [
          '#plain_text' => $id,
        ],
        'label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Label'),
          '#title_display' => 'invisible',
          '#default_value' => $sentiment['label'],
          '#required' => TRUE,
          '#placeholder' => $this->t('Enter sentiment name'),
        ],
        'min_label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Minimum Label'),
          '#title_display' => 'invisible',
          '#default_value' => $sentiment['min_label'],
          '#required' => TRUE,
          '#placeholder' => $this->t('Label for -1.0'),
        ],
        'mid_label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Middle Label'),
          '#title_display' => 'invisible',
          '#default_value' => $sentiment['mid_label'],
          '#required' => TRUE,
          '#placeholder' => $this->t('Label for 0.0'),
        ],
        'max_label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Maximum Label'),
          '#title_display' => 'invisible',
          '#default_value' => $sentiment['max_label'],
          '#required' => TRUE,
          '#placeholder' => $this->t('Label for +1.0'),
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $sentiment['weight'] ?? 0,
          '#attributes' => ['class' => ['sentiment-weight']],
          '#delta' => 50,
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => \Drupal\Core\Url::fromRoute('analyze_hello_world.delete_sentiment', ['sentiment_id' => $id]),
              'attributes' => [
                'class' => ['button', 'button--danger', 'button--small'],
                'aria-label' => $this->t('Delete @sentiment', ['@sentiment' => $sentiment['label']]),
              ],
            ],
          ],
        ],
      ];
    }

    // Help text for drag-and-drop
    if (!empty($sentiments)) {
      $form['table_help'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Drag and drop rows to reorder the sentiments. This order will be reflected in the analysis display.'),
        '#attributes' => ['class' => ['sentiment-help-text', 'description']],
        '#weight' => 5,
      ];
    }

    $form = parent::buildForm($form, $form_state);
    
    // Improve the save button
    $form['actions']['submit']['#value'] = $this->t('Save changes');
    $form['actions']['submit']['#attributes']['class'][] = 'button--primary';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('analyze_hello_world.settings');
    $sentiments = $form_state->getValue('sentiments');
    
    // Update existing sentiments
    foreach ($sentiments as $id => $sentiment) {
      $sentiments[$id]['id'] = $id;
      $sentiments[$id]['weight'] = (int) $sentiment['weight'];
    }
    
    // Sort by weight before saving
    uasort($sentiments, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });
    
    $config->set('sentiments', $sentiments)->save();
    parent::submitForm($form, $form_state);
  }

} 