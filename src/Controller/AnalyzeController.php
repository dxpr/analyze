<?php

namespace Drupal\analyze\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

class AnalyzeController extends ControllerBase {

  public function analyze(NodeInterface $node) {
    $build = [
      '#type' => 'markup',
      '#markup' => '<h2>Sentiment Analysis</h2>',
    ];

    $build['gauge_1'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Sentiment Score - General Sentiment',
      '#range_min_label' => 'Negative',
      '#range_mid_label' => 'Neutral',
      '#range_max_label' => 'Positive',
      '#range_min' => '0',
      '#value' => '0.2',  // Example: 20% Positive
      '#display_value' => '20%',
      '#range_max' => '1',
    ];

    $build['gauge_2'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Sentiment Score - Joy',
      '#range_min_label' => 'Sad',
      '#range_mid_label' => 'Neutral',
      '#range_max_label' => 'Joyful',
      '#range_min' => '0',
      '#value' => '0.6',  // Example: 60% Joyful
      '#display_value' => '60%',
      '#range_max' => '1',
    ];

    $build['gauge_3'] = [
      '#theme' => 'analyze_gauge',
      '#caption' => 'Sentiment Score - Anger',
      '#range_min_label' => 'Calm',
      '#range_mid_label' => 'Neutral',
      '#range_max_label' => 'Angry',
      '#range_min' => '0',
      '#value' => '0.8',  // Example: 80% Angry
      '#display_value' => '80%',
      '#range_max' => '1',
    ];

    $build['sentiment_report'] = [
      '#type' => 'link',
      '#title' => $this->t('View sitewide sentiment report'),
      '#url' => Url::fromRoute('system.status'),
      '#attributes' => ['class' => ['action-link', 'view-sitewide-report']],
    ];

    $build['basic_header'] = [
      '#markup' => '<h2>Basic content information</h2>',
    ];

    $basic_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Security', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Word count', 498]],
        ['data' => ['Image count', 2]],
      ],
      '#attributes' => [
        'class' => ['basic-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['basic_table'] = $basic_table;

    $build['security_header'] = [
      '#markup' => '<h2>Security</h2>',
    ];

    $security_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Security', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['PII (Personally Identifiable Information) Detection', 'No PII detected']],
        ['data' => ['Malicious Link Detection', 'No malicious links detected']],
      ],
      '#attributes' => [
        'class' => ['security-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['security_table'] = $security_table;

    $build['security_report'] = [
      '#type' => 'link',
      '#title' => $this->t('View sitewide security report'),
      '#url' => Url::fromRoute('system.status'),
      '#attributes' => ['class' => ['action-link', 'view-sitewide-report']],
    ];

    $build['statistics_header'] = [
      '#markup' => '<h2>Page views</h2>',
    ];

    $statistics_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Page popularity', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Total page views', 3229]],
        ['data' => ['Today\'s page views', 29]],
      ],
      '#attributes' => [
        'class' => ['statistics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['statistics_table'] = $statistics_table;

    $build['statistics_report'] = [
      '#type' => 'link',
      '#title' => $this->t('View sitewide page views report'),
      '#url' => Url::fromRoute('system.status'),
      '#attributes' => ['class' => ['action-link', 'view-sitewide-report']],
    ];

    $build['realtime_seo_header'] = [
      '#markup' => '<h2>Realtime SEO</h2>',
    ];

    $realtime_seo_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Realtime SEO', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['SEO Score', '85%']],
        ['data' => ['Focus Keyword', '"Drupal 10 Module"']],
        ['data' => ['Readability', 'Good']],
      ],
      '#attributes' => [
        'class' => ['realtime-seo-data-table'],
        'style' => ['table-layout: fixed;'],
      ],    ];

    $build['realtime_seo_table'] = $realtime_seo_table;
    $build['realtime_seo_link'] = [
      '#type' => 'link',
      '#title' => $this->t('View full report for this page'),
      '#url' => Url::fromRoute('analyze.realtime_seo_full_report', ['node' => $node->id()]),
      '#attributes' => ['class' => ['action-link', 'view-full-report']],
    ];
    $build['realtime_seo_report'] = [
      '#type' => 'link',
      '#title' => $this->t('View sitewide SEO report'),
      '#url' => Url::fromRoute('system.status'),
      '#attributes' => ['class' => ['action-link', 'view-sitewide-report']],
    ];

    $build['google_analytics_header'] = [
      '#markup' => '<h2>Google Analytics Node Reports</h2>',
    ];

    $google_analytics_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Google Analytics Node Reports', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Page Views', '1234']],
        ['data' => ['Bounce Rate', '45%']],
        ['data' => ['Average Time on Page', '2 minutes']],
      ],
      '#attributes' => [
        'class' => ['google-analytics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['google_analytics_table'] = $google_analytics_table;
    $build['google_analytics_link'] = [
      '#type' => 'link',
      '#title' => $this->t('View full report for this page'),
      '#url' => Url::fromRoute('analyze.google_analytics_full_report', ['node' => $node->id()]),
      '#attributes' => ['class' => ['action-link', 'view-full-report']],
    ];
    $build['google_analytics_report'] = [
      '#type' => 'link',
      '#title' => $this->t('View sitewide Google Analytics report'),
      '#url' => Url::fromRoute('system.status'),
      '#attributes' => ['class' => ['action-link', 'view-sitewide-report']],
    ];

    $build['accessibility_header'] = [
      '#markup' => '<h2>Editoria11y Accessibility Checker</h2>',
    ];

    $accessibility_table = [
      '#type' => 'table',
      '#header' => [['data' => 'Editoria11y Accessibility Checker', 'colspan' => 2, 'class' => ['header']]],
      '#rows' => [
        ['data' => ['Valid HTML', 'Yes']],
        ['data' => ['Alt Text for Images', '95% complete']],
        ['data' => ['Headings Structure', 'Proper']],
      ],
      '#attributes' => [
        'class' => ['accessibility-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];

    $build['accessibility_table'] = $accessibility_table;
    $build['accessibility_link'] = [
      '#type' => 'link',
      '#title' => $this->t('View full report for this page'),
      '#url' => Url::fromRoute('analyze.accessibility_full_report', ['node' => $node->id()]),
      '#attributes' => ['class' => ['action-link', 'view-full-report']],
    ];
    $build['accessibility_report'] = [
      '#type' => 'link',
      '#title' => $this->t('View sitewide accessibility report'),
      '#url' => Url::fromRoute('system.status'),
      '#attributes' => ['class' => ['action-link', 'view-sitewide-report']],
    ];

    return $build;
  }

  public function realtimeSeoFullReport(NodeInterface $node) {
    $header = [
      ['data' => 'Metric', 'class' => ['header']],
      ['data' => 'Value', 'class' => ['header']],
    ];
    $rows = [
      ['data' => ['SEO Score', '85%']],
      ['data' => ['Focus Keyword', '"Drupal 10 Module"']],
      ['data' => ['Readability', 'Good']],
      ['data' => ['Meta Description', 'Optimal length']],
      ['data' => ['Internal Links', '5']],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['realtime-seo-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  public function googleAnalyticsFullReport(NodeInterface $node) {
    $header = [
      ['data' => 'Metric', 'class' => ['header']],
      ['data' => 'Value', 'class' => ['header']],
    ];
    $rows = [
      ['data' => ['Page Views', '1234']],
      ['data' => ['Bounce Rate', '45%']],
      ['data' => ['Average Time on Page', '2 minutes']],
      ['data' => ['New Users', '345']],
      ['data' => ['Returning Users', '789']],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['google-analytics-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }

  public function accessibilityFullReport(NodeInterface $node) {
    $header = [
      ['data' => 'Metric', 'class' => ['header']],
      ['data' => 'Value', 'class' => ['header']],
    ];
    $rows = [
      ['data' => ['Valid HTML', 'Yes']],
      ['data' => ['Alt Text for Images', '95% complete']],
      ['data' => ['Headings Structure', 'Proper']],
      ['data' => ['ARIA Roles', 'All present']],
      ['data' => ['Contrast Ratio', 'Pass']],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['accessibility-data-table'],
        'style' => ['table-layout: fixed;'],
      ],
    ];
  }
}
