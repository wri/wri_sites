<?php

namespace Drupal\wri_common\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
   * @param array $options
   *   Command options.
   *
   * @command wri_common:copy-config-to-profile
   * @aliases wri-ccp
   * @option write-update-hook Also append an update hook to wri_common.install that calls distro_helper to sync the config.
   */
  #[CLI\Command(name: 'wri_common:copy-config-to-profile', aliases: ['wri-ccp'])]
  #[CLI\Argument(name: 'source', description: 'Path to the source config file (absolute or relative to the project root).')]
  #[CLI\Option(name: 'write-update-hook', description: 'Also append an update hook to wri_common.install that calls distro_helper to sync the config.')]
  #[CLI\Usage(name: 'wri_common:copy-config-to-profile config/node.type.page.yml', description: 'Copy node.type.page.yml into the matching wri_sites config file.')]
  #[CLI\Usage(name: 'wri_common:copy-config-to-profile config/node.type.page.yml --write-update-hook', description: 'Copy and also generate an update hook.')]
  public function copyConfigToProfile(string $source, array $options = ['write-update-hook' => FALSE]): void {
    if (!str_starts_with($source, '/')) {
      $source = $this->appRoot . '/../' . $source;
    }

    if (!file_exists($source)) {
      throw new \InvalidArgumentException("Source file not found: $source");
    }

    if (!is_dir($this->appRoot . '/profiles/contrib/wri_sites')) {
      throw new \RuntimeException("wri_sites directory not found: {$this->appRoot}/profiles/contrib/wri_sites");
    }

    $this->processConfigFile($source, (bool) $options['write-update-hook']);
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
   * @param array $options
   *   Command options.
   *
   * @command wri_common:sync-config-from-diff
   * @aliases wri-scd
   * @option write-update-hook Also append update hooks to wri_common.install for each synced file.
   */
  #[CLI\Command(name: 'wri_common:sync-config-from-diff', aliases: ['wri-scd'])]
  #[CLI\Argument(name: 'base', description: 'The base branch.')]
  #[CLI\Argument(name: 'target', description: 'The target branch.')]
  #[CLI\Option(name: 'write-update-hook', description: 'Also append update hooks to wri_common.install for each synced file.')]
  #[CLI\Usage(name: 'wri_common:sync-config-from-diff main issue-585', description: 'Sync all config changes between main and issue-585 into the wri_sites profile.')]
  #[CLI\Usage(name: 'wri_common:sync-config-from-diff main issue-585 --write-update-hook', description: 'Sync and generate update hooks for every changed config file.')]
  public function syncConfigFromDiff(string $base, string $target, array $options = ['write-update-hook' => FALSE]): void {
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
      $this->processConfigFile($absolutePath, (bool) $options['write-update-hook']);
    }

    $this->io()->success('Sync complete.');
  }

  /**
   * Reads a source config file, strips disallowed keys, and writes it to the
   * matching file under web/profiles/contrib/wri_sites.
   *
   * Logs a warning and returns without error when no matching profile file is found.
   *
   * @param bool $writeUpdateHook
   *   When TRUE, appends an update hook to wri_common.install.
   */
  protected function processConfigFile(string $absoluteSourcePath, bool $writeUpdateHook = FALSE): void {
    $filename = basename($absoluteSourcePath);
    $profileBase = $this->appRoot . '/profiles/contrib/wri_sites';

    $destPath = $this->findFileInDirectory($profileBase, $filename);
    if ($destPath === NULL) {
      $this->logger()->warning("No file named '$filename' found under $profileBase — skipped.");
      return;
    }

    $oldContent = file_get_contents($destPath) ?: '';

    $content = file_get_contents($absoluteSourcePath);
    $filtered = $this->stripTopLevelKeys($content, ['uuid', 'langcode']);
    $filtered = $this->stripField('field_share_with_io', $filtered);

    if (file_put_contents($destPath, $filtered) === FALSE) {
      throw new \RuntimeException("Could not write to: $destPath");
    }

    $this->io()->writeln("  Synced: $filename");

    if ($writeUpdateHook) {
      $configName = basename($filename, '.yml');
      $installFile = $this->appRoot . '/profiles/contrib/wri_sites/modules/wri_common/wri_common.install';
      $hookCode = $this->generateUpdateHook($configName, $destPath, $oldContent, $filtered, $installFile);
      $this->appendUpdateHook($installFile, $hookCode);
    }
  }

  /**
   * Generates an update hook string that calls distro_helper to sync a config.
   *
   * The hook number is one higher than the current maximum in the install file.
   * The module and directory arguments to updateConfig are derived from the
   * destination file path so distro_helper can locate the yml file.
   *
   * @param string $configName
   *   Config name without the .yml extension (e.g. 'node.type.page').
   * @param string $destPath
   *   Absolute path to the destination yml file inside the profile.
   * @param string $filteredContent
   *   The filtered yml content (used to extract top-level keys).
   * @param string $installFile
   *   Absolute path to the .install file to number the hook against.
   *
   * @return string
   *   PHP source for the new update hook, ready to append.
   */
  protected function generateUpdateHook(string $configName, string $destPath, string $oldContent, string $newContent, string $installFile): string {
    [$module, $directory] = $this->resolveModuleAndDirectory($destPath);
    $keys = $this->extractChangedYamlKeys($oldContent, $newContent);
    if (empty($keys)) {
      $keys = $this->extractTopLevelYamlKeys($newContent);
    }
    $hookNumber = $this->nextUpdateHookNumber($installFile, 'wri_common');

    $keyLines = implode(",\n    ", array_map(fn($k) => "'$k'", $keys));

    return "\nfunction wri_common_update_{$hookNumber}() {\n  \\Drupal::service('distro_helper.updates')->updateConfig('$configName', [\n    $keyLines,\n  ], '$module', '$directory');\n}\n";
  }

  protected function extractChangedYamlKeys(string $oldContent, string $newContent): array {
    try {
      $old = Yaml::parse($oldContent) ?: [];
      $new = Yaml::parse($newContent) ?: [];
    }
    catch (ParseException $e) {
      return [];
    }
    $skip = ['_core', 'uuid', 'langcode'];
    foreach ($skip as $key) {
      unset($old[$key], $new[$key]);
    }
    return $this->diffArrays($old, $new);
  }

  protected function diffArrays(array $old, array $new, string $prefix = ''): array {
    $changed = [];
    foreach ($new as $key => $value) {
      $path = $prefix !== '' ? "$prefix#$key" : (string) $key;
      if (!array_key_exists($key, $old)) {
        $changed[] = $path;
      }
      elseif (is_array($value) && is_array($old[$key])) {
        $changed = array_merge($changed, $this->diffArrays($old[$key], $value, $path));
      }
      elseif ($value !== $old[$key]) {
        $changed[] = $path;
      }
    }
    return $changed;
  }

  /**
   * Appends hook PHP code to the given .install file.
   */
  protected function appendUpdateHook(string $installFile, string $hookCode): void {
    if (!file_exists($installFile)) {
      throw new \RuntimeException("Install file not found: $installFile");
    }
    if (file_put_contents($installFile, $hookCode, FILE_APPEND) === FALSE) {
      throw new \RuntimeException("Could not write to install file: $installFile");
    }
    $this->io()->writeln("  Hook written to: " . basename($installFile));
  }

  /**
   * Resolves the distro_helper module name and directory for a profile file.
   *
   * distro_helper resolves config files as:
   *   extensionPathResolver->getPath('module', $module) . '/config/' . $directory . '/'
   *
   * For files inside a module's config dir the module name and subdirectory
   * are extracted directly. For profile-level config a relative path from
   * wri_common is used (mirroring the pattern in wri_common_update_10500).
   *
   * @return array{0: string, 1: string}
   *   A [module, directory] tuple.
   */
  protected function resolveModuleAndDirectory(string $destPath): array {
    if (preg_match('#/modules/([^/]+)/config/([^/]+)/#', $destPath, $m)) {
      return [$m[1], $m[2]];
    }
    if (preg_match('#/config/([^/]+)/#', $destPath, $m)) {
      // Relative path from wri_common/config/ up to wri_sites/config/<dir>/.
      return ['wri_common', '../../../config/' . $m[1]];
    }
    return ['wri_common', 'install'];
  }

  /**
   * Extracts top-level YAML key names from config file content.
   *
   * Skips Drupal metadata keys that should not be passed to updateConfig.
   *
   * @return string[]
   */
  protected function extractTopLevelYamlKeys(string $content): array {
    $skip = ['_core', 'uuid', 'langcode'];
    $keys = [];
    foreach (explode("\n", $content) as $line) {
      if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*):\s*/', $line, $m) && !in_array($m[1], $skip, TRUE)) {
        $keys[] = $m[1];
      }
    }
    return $keys;
  }

  /**
   * Returns the next available update hook number for a module's install file.
   */
  protected function nextUpdateHookNumber(string $installFile, string $moduleName): int {
    $content = file_get_contents($installFile);
    preg_match_all('/function ' . preg_quote($moduleName, '/') . '_update_(\d+)\(/', $content, $matches);
    if (empty($matches[1])) {
      return 10601;
    }
    return (int) max($matches[1]) + 1;
  }

  /**
   * Recursively searches a directory for a file matching the given name.
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
