<?php

declare(strict_types=1);

namespace Drupal\analyze\Plugin\Analyze;

use Drupal\analyze\AnalyzePluginBase;

/**
 * Plugin implementation of the analyze.
 *
 * @Analyze(
 *   id = "foo",
 *   label = @Translation("Foo"),
 *   description = @Translation("Foo description.")
 * )
 */
final class Foo extends AnalyzePluginBase {

}
