<?php

namespace Drupal\wri_common\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for wri_common maintenance tasks.
 */
final class WriCommonCommands extends DrushCommands {

  /**
   * The Drupal app root.
   *
   * @var string
   */
  protected string $appRoot;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $app_root) {
    parent::__construct();
    $this->appRoot = $app_root;
  }

  /**
   * Copies a config file into the matching wri_sites profile file, stripping uuid and langcode.
   *
   * @param string $source
   *   Path to the source config file (absolute or relative to the project root).
   *
   * @command wri_common:copy-config-to-profile
   * @aliases wri-ccp
   */
  #[CLI\Command(name: 'wri_common:copy-config-to-profile', aliases: ['wri-ccp'])]
  #[CLI\Argument(name: 'source', description: 'Path to the source config file (absolute or relative to the project root).')]
  #[CLI\Usage(name: 'wri_common:copy-config-to-profile config/node.type.page.yml', description: 'Copy node.type.page.yml into the matching wri_sites config file.')]
  public function copyConfigToProfile(string $source): void {
    if (!str_starts_with($source, '/')) {
      $source = $this->appRoot . '/../' . $source;
    }

    if (!file_exists($source)) {
      throw new \InvalidArgumentException("Source file not found: $source");
    }

    if (!is_dir($this->appRoot . '/profiles/contrib/wri_sites')) {
      throw new \RuntimeException("wri_sites directory not found: {$this->appRoot}/profiles/contrib/wri_sites");
    }

    $this->processConfigFile($source);
  }

  /**
   * Syncs all config files changed between two git branches to the wri_sites profile.
   *
   * Finds every file changed under the config/ directory in the diff between
   * the two branches and runs the same copy-and-strip logic as
   * wri_common:copy-config-to-profile on each one.
   *
   * @param string $base
   *   The base branch name.
   * @param string $target
   *   The target branch name.
   *
   * @command wri_common:sync-config-from-diff
   * @aliases wri-scd
   */
  #[CLI\Command(name: 'wri_common:sync-config-from-diff', aliases: ['wri-scd'])]
  #[CLI\Argument(name: 'base', description: 'The base branch.')]
  #[CLI\Argument(name: 'target', description: 'The target branch.')]
  #[CLI\Usage(name: 'wri_common:sync-config-from-diff main issue-585', description: 'Sync all config changes between main and issue-585 into the wri_sites profile.')]
  public function syncConfigFromDiff(string $base, string $target): void {
    $projectRoot = realpath($this->appRoot . '/..');

    $output = $this->runGitCommand(
      sprintf('git diff --name-only %s %s -- config/', escapeshellarg($base), escapeshellarg($target)),
      $projectRoot
    );

    $changedFiles = array_filter(explode("\n", trim($output)));

    if (empty($changedFiles)) {
      $this->io()->note("No config changes found between '$base' and '$target'.");
      return;
    }

    if (!is_dir($this->appRoot . '/profiles/contrib/wri_sites')) {
      throw new \RuntimeException("wri_sites directory not found: {$this->appRoot}/profiles/contrib/wri_sites");
    }

    $this->io()->section(sprintf('Syncing %d config file(s) to wri_sites profile…', count($changedFiles)));

    foreach ($changedFiles as $relativePath) {
      $absolutePath = $projectRoot . '/' . $relativePath;
      if (!file_exists($absolutePath)) {
        $this->logger()->warning("Skipped (deleted in diff): $relativePath");
        continue;
      }
      $this->processConfigFile($absolutePath);
    }

    $this->io()->success('Sync complete.');
  }

  /**
   * Reads a source config file, strips disallowed keys, and writes it to the
   * matching file under web/profiles/contrib/wri_sites.
   *
   * Logs a warning and returns without error when no matching profile file is found.
   */
  protected function processConfigFile(string $absoluteSourcePath): void {
    $filename = basename($absoluteSourcePath);
    $profileBase = $this->appRoot . '/profiles/contrib/wri_sites';

    $destPath = $this->findFileInDirectory($profileBase, $filename);
    if ($destPath === NULL) {
      $this->logger()->warning("No file named '$filename' found under $profileBase — skipped.");
      return;
    }

    $content = file_get_contents($absoluteSourcePath);
    $filtered = $this->stripTopLevelKeys($content, ['uuid', 'langcode']);
    $filtered = $this->stripField('field_share_with_io', $filtered);

    if (file_put_contents($destPath, $filtered) === FALSE) {
      throw new \RuntimeException("Could not write to: $destPath");
    }

    $this->io()->writeln("  Synced: $filename");
  }

  /**
   * Recursively searches a directory for a file matching the given name.
   *
   * @param string $directory
   *   The directory to search.
   * @param string $filename
   *   The filename to find.
   *
   * @return string|null
   *   The full path to the first match, or NULL if not found.
   */
  protected function findFileInDirectory(string $directory, string $filename): ?string {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getFilename() === $filename) {
        return $file->getPathname();
      }
    }
    return NULL;
  }

  /**
   * Removes all references to a field from config file content.
   *
   * Handles three cases:
   *   - Bare block keys (e.g. `field_share_with_io:`) — removes the key and
   *     its entire indented child block.
   *   - Inline key-value pairs (e.g. `field_share_with_io: true`).
   *   - List items or values containing the string.
   *
   * @param string $field_name
   *   The field's machine name, like "field_my_field".
   * @param string $content
   *   The raw file content.
   *
   * @return string
   *   The filtered content.
   */
  protected function stripField(string $field_name, string $content): string {
    $lines = explode("\n", $content);
    $result = [];
    $blockIndent = NULL;

    foreach ($lines as $line) {
      // If we're inside a skipped block, check whether this line ends it.
      if ($blockIndent !== NULL) {
        $trimmed = ltrim($line);
        $indent = strlen($line) - strlen($trimmed);
        if ($trimmed !== '' && $indent <= $blockIndent) {
          // This line is outside the skipped block.
          // Fall through to evaluate it.
          $blockIndent = NULL;
        }
        else {
          continue;
        }
      }

      if (!str_contains($line, $field_name)) {
        $result[] = $line;
        continue;
      }

      // If the line is a bare block key (nothing after the colon), its
      // indented children must also be removed.
      if (str_ends_with(rtrim($line), ':')) {
        $blockIndent = strlen($line) - strlen(ltrim($line));
      }
      // Skip this line regardless.
    }

    return implode("\n", $result);
  }

  /**
   * Removes top-level YAML key lines from config file content.
   *
   * @param string $content
   *   The raw file content.
   * @param string[] $keys
   *   Top-level keys to strip (e.g. ['uuid', 'langcode']).
   *
   * @return string
   *   The filtered content.
   */
  protected function stripTopLevelKeys(string $content, array $keys): string {
    $pattern = '/^(' . implode('|', array_map('preg_quote', $keys)) . '):.*\n?/m';
    $filtered = preg_replace($pattern, '', $content);
    return ltrim($filtered, "\n");
  }

  /**
   * Runs a shell command in the given working directory and returns stdout.
   *
   * @throws \RuntimeException
   *   When the command exits with a non-zero code.
   */
  protected function runGitCommand(string $command, string $cwd): string {
    $descriptors = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, $cwd);
    if (!is_resource($process)) {
      throw new \RuntimeException("Failed to start: $command");
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
      throw new \RuntimeException("Git command failed (exit $exitCode): $stderr");
    }
    return $stdout;
  }

}