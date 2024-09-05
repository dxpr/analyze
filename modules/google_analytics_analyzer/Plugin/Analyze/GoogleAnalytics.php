<?php

declare(strict_types=1);

namespace Drupal\analyze\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;

/**
 * Plugin implementation of the analyze.
 *
 * @Analyze(
 *   id = "google_analytics",
 *   label = @Translation("Google Analytics"),
 *   description = @Translation("Provides data from the Google Analytics module for Analyzer.")
 * )
 */
final class GoogleAnalytics extends AnalyzePluginBase {

}
