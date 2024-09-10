<?php

/**
 * @file
 * API documentation for the Analyze module.
 *
 * This file provides information on how to implement and extend the Analyze module
 * in Drupal 10 by creating custom analysis plugins that integrate with the Analyze
 * tab. The file also provides best practices for implementation, error handling,
 * security, and performance.
 */

namespace Drupal\analyze;

/**
 * Implements hook_analyze_info_alter().
 *
 * Allows modules to alter the list of analyzers available in the Analyze module.
 *
 * @param array $analyzers
 *   An associative array of analyzer definitions. The keys are the analyzer IDs
 *   and the values are the analyzer information arrays.
 */
function hook_analyze_info_alter(array &$analyzers): void {
  // Example: Change the label of an existing analyzer.
  if (isset($analyzers['security_analysis'])) {
    $analyzers['security_analysis']['label'] = t('Enhanced Security Analysis');
  }
}
