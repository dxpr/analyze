<?php

declare(strict_types=1);

namespace Drupal\google_analytics_analyzer\src\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;

/**
 * Plugin implementation of the analyze.
 *
 * @Analyze(
 *   id = "analytics",
 *   label = @Translation("Google Analytics Entity Reports"),
 *   description = @Translation("Provides data from Google Analytics for Analyzer.")
 * )
 */
final class GoogleAnalytics extends AnalyzePluginBase {

}
