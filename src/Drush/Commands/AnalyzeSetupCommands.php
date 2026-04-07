<?php

declare(strict_types=1);

namespace Drupal\analyze\Drush\Commands;

use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Attributes as CLI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for AI skill file management.
 */
final class AnalyzeSetupCommands extends AnalyzeCommandsBase {

  /**
   * Skill files to install, relative to module root.
   *
   * @var array<string>
   */
  private const SKILL_FILES = [
    '.claude/skills/analyze/SKILL.md',
    '.agents/skills/analyze/SKILL.md',
    '.agents/skills/analyze/agents/openai.yaml',
  ];

  public function __construct(
    protected readonly ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('extension.list.module'),
    );
  }

  /**
   * Install AI skill files to the project root.
   */
  #[CLI\Command(name: 'analyze:setup-ai', aliases: ['analyze-sa'])]
  #[CLI\Help(description: '[YAML] Install Analyze AI skill files for Claude Code and other agents.')]
  #[CLI\Option(name: 'host', description: 'Target: claude, agents, or all (default: all)')]
  #[CLI\Option(name: 'check', description: 'Check if installed files are up to date (no changes made)')]
  #[CLI\Usage(name: 'drush analyze:setup-ai', description: 'Install for all AI tools')]
  #[CLI\Usage(name: 'drush analyze:setup-ai --check', description: 'Check if skill files are up to date')]
  #[CLI\Usage(name: 'drush analyze-sa --host=claude', description: 'Install for Claude Code only')]
  #[CLI\Usage(name: 'drush analyze-sa --host=agents', description: 'Install for Codex/Gemini/Copilot/Cursor')]
  public function setupAi(
    array $options = [
      'host' => 'all',
      'check' => FALSE,
    ],
  ): string {
    $host = $options['host'];

    if (!in_array($host, ['all', 'claude', 'agents'], TRUE)) {
      return $this->error('Invalid --host value. Use: claude, agents, or all');
    }

    $modulePath = $this->moduleExtensionList->getPath('analyze');
    $moduleRoot = DRUPAL_ROOT . '/' . $modulePath;
    $projectRoot = $this->getProjectRoot();

    if (!$projectRoot) {
      return $this->error('Could not locate project root (no composer.json found)');
    }

    $files = $this->filterFilesByHost($host);

    if ($options['check']) {
      return $this->checkFiles($moduleRoot, $projectRoot, $files);
    }

    return $this->installFiles($moduleRoot, $projectRoot, $files);
  }

  /**
   * Filter skill files by host target.
   *
   * @param string $host
   *   The host target: claude, agents, or all.
   *
   * @return array<string>
   *   Filtered list of relative file paths.
   */
  private function filterFilesByHost(string $host): array {
    if ($host === 'all') {
      return self::SKILL_FILES;
    }

    $prefix = $host === 'claude' ? '.claude/' : '.agents/';
    return array_values(array_filter(
      self::SKILL_FILES,
      fn(string $f) => str_starts_with($f, $prefix),
    ));
  }

  /**
   * Check if installed files are up to date.
   *
   * @param string $moduleRoot
   *   The module root path.
   * @param string $projectRoot
   *   The project root path.
   * @param array<string> $files
   *   Relative file paths to check.
   *
   * @return string
   *   YAML-formatted check result.
   */
  private function checkFiles(
    string $moduleRoot,
    string $projectRoot,
    array $files,
  ): string {
    $statuses = [];
    $allCurrent = TRUE;

    foreach ($files as $relative) {
      $source = $moduleRoot . '/' . $relative;
      $dest = $projectRoot . '/' . $relative;

      if (!file_exists($source)) {
        continue;
      }

      if (!file_exists($dest)) {
        $statuses[$relative] = 'NOT INSTALLED';
        $allCurrent = FALSE;
      }
      elseif (md5_file($source) !== md5_file($dest)) {
        $statuses[$relative] = 'OUTDATED';
        $allCurrent = FALSE;
      }
      else {
        $statuses[$relative] = 'up to date';
      }
    }

    if ($allCurrent) {
      return $this->success(
        'All skill files are up to date.',
        ['files' => $statuses]
      );
    }

    return $this->yaml([
      'success' => FALSE,
      'message' => 'Skill files need updating. Run: drush analyze:setup-ai',
      'files' => $statuses,
    ]);
  }

  /**
   * Install skill files to the project root.
   *
   * @param string $moduleRoot
   *   The module root path.
   * @param string $projectRoot
   *   The project root path.
   * @param array<string> $files
   *   Relative file paths to install.
   *
   * @return string
   *   YAML-formatted install result.
   */
  private function installFiles(
    string $moduleRoot,
    string $projectRoot,
    array $files,
  ): string {
    $results = [];

    foreach ($files as $relative) {
      $source = $moduleRoot . '/' . $relative;
      $dest = $projectRoot . '/' . $relative;

      if (!file_exists($source)) {
        $results[] = sprintf(
          'Source not found: %s',
          $relative,
        );
        continue;
      }

      $destDir = dirname($dest);
      if (!is_dir($destDir)) {
        mkdir($destDir, 0755, TRUE);
      }

      $action = file_exists($dest) ? 'updated' : 'installed';
      copy($source, $dest);
      $results[] = sprintf(
        '%s %s at %s',
        basename($relative),
        $action,
        $relative,
      );
    }

    return $this->success(
      'AI skill files installed.',
      ['files' => $results]
    );
  }

}
