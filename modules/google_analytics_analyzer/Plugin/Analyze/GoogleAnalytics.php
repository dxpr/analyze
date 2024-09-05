<?php

declare(strict_types=1);

namespace Drupal\analyze\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;

/**
 * Plugin implementation of the analyze.
 *
 * @Analyze(
 *   id = "analytics",
 *   label = @Translation("Google Analytics Node Reports"),
 *   description = @Translation("Provides data from Google Analytics for Analyzer.")
 * )
 */
final class GoogleAnalytics extends AnalyzePluginBase {

}
