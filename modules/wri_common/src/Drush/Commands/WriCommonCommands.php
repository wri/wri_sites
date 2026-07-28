<?php

namespace Drupal\wri_common\Drush\Commands;

use Drush\Drush;
use Drupal\Core\Extension\ProfileExtensionList;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Question\Question;
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
   * The actively-installed install profile.
   *
   * @var string
   */
  protected string $installProfile;

  /**
   * The profile directory.
   *
   * @var string
   */
  protected $profileDirectory;

  /**
   * Constructs a new WriCommonCommands instance.
   *
   * @param string $app_root
   *   The app root.
   * @param string $install_profile
   *   The install profile.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_extension_list
   *   The profile extension list.
   */
  public function __construct(string $app_root, string $install_profile, ProfileExtensionList $profile_extension_list) {
    parent::__construct();
    $this->appRoot = $app_root;
    $this->installProfile = $install_profile;
    $this->profileDirectory = $profile_extension_list->getPath($install_profile);
  }

  /**
   * Copies a config file into the matching profile file.
   *
   * @param string $source
   *   Path to the source config file (absolute or relative to the app root).
   * @param array $options
   *   Command options.
   *
   * @command wri_common:copy-config-to-profile
   * @aliases wri-ccp
   * @option write-update-hook Also append an update hook to wri_common.install that calls wri_common to sync the config.
   */
  #[CLI\Command(name: 'wri_common:copy-config-to-profile', aliases: ['wri-ccp'])]
  #[CLI\Argument(name: 'source', description: 'Path to the source config file (absolute or relative to the project root).')]
  #[CLI\Option(name: 'write-update-hook', description: 'Also append an update hook to wri_common.install that calls wri_common to sync the config.')]
  #[CLI\Usage(name: 'wri_common:copy-config-to-profile config/node.type.page.yml', description: 'Copy node.type.page.yml into the matching config file in a module in your profile.')]
  #[CLI\Usage(name: 'wri_common:copy-config-to-profile config/node.type.page.yml --write-update-hook', description: 'Copy and also generate an update hook.')]
  public function copyConfigToProfile(string $source, array $options = ['write-update-hook' => FALSE]): void {
    if (!str_starts_with($source, '/')) {
      // @todo determine this dynamically. We use ../config, but not everyone
      // does.
      $source = $this->appRoot . '/../' . $source;
    }

    if (!file_exists($source)) {
      throw new \InvalidArgumentException("Source file not found: $source");
    }

    if (!is_dir($this->profileDirectory)) {
      throw new \RuntimeException("{$this->installProfile} directory not found: {$this->profileDirectory}");
    }

    $this->processConfigFile($source, (bool) $options['write-update-hook']);
  }

  /**
   * Syncs all config files changed between two git branches to the profile.
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
   * @option write-update-hook Also append update hooks to the module for each synced file.
   */
  #[CLI\Command(name: 'wri_common:sync-config-from-diff', aliases: ['wri-scd'])]
  #[CLI\Argument(name: 'base', description: 'The base branch.')]
  #[CLI\Argument(name: 'target', description: 'The target branch.')]
  #[CLI\Option(name: 'write-update-hook', description: 'Also append update hooks for each synced file.')]
  #[CLI\Usage(name: 'wri_common:sync-config-from-diff main issue-585', description: 'Sync all config changes between main and issue-585 into the profile.')]
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

    if (!is_dir($this->profileDirectory)) {
      throw new \RuntimeException("{$this->installProfile} directory not found: {$this->profileDirectory}");
    }

    $this->io()->section(sprintf('Syncing %d config file(s) to profile…', count($changedFiles)));

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
   * Processes a yml config file within the site's config directory.
   *
   * Reads the source config file, strips disallowed keys, and writes it to the
   * matching file in the installed profile.
   *
   * In non-interactive mode, logs a warning and skips when no matching profile
   * file is found. In interactive mode, prompts for a target module instead.
   *
   * @param string $absoluteSourcePath
   *   Absolute path to the source config file.
   * @param bool $writeUpdateHook
   *   When TRUE, appends an update hook to the module where the config lives.
   */
  protected function processConfigFile(string $absoluteSourcePath, bool $writeUpdateHook = FALSE): void {
    $filename = basename($absoluteSourcePath);
    $profileBase = $this->profileDirectory;

    $destPath = $this->findFileInDirectory($profileBase, $filename);
    if ($destPath === NULL) {
      if (!$this->input()->isInteractive()) {
        $this->logger()->warning("No file named '$filename' found under $profileBase — skipped.");
        return;
      }
      $destPath = $this->askForModuleDestination($profileBase, $filename);
      if ($destPath === NULL) {
        return;
      }
      $dir = dirname($destPath);
      if (!is_dir($dir)) {
        mkdir($dir, 0755, TRUE);
      }
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
      [$module, $directory] = $this->resolveModuleAndDirectory($destPath);
      $installFile = $this->resolveInstallFile($module, $destPath);
      $hookCode = $this->generateUpdateHook($configName, $module, $directory, $oldContent, $filtered, $installFile);
      $this->appendUpdateHook($installFile, $hookCode);
    }
  }

  /**
   * Generates an update hook string that calls wri_common to sync a config.
   *
   * The hook number is one higher than the current maximum in the install file.
   * The module and directory arguments to updateConfig are derived from the
   * destination file path so wri_common can locate the yml file.
   *
   * @param string $configName
   *   Config name without the .yml extension (e.g. 'node.type.page').
   * @param string $module
   *   The module machine name owning the config file.
   * @param string $directory
   *   The config subdirectory (e.g. 'install' or 'optional').
   * @param string $oldContent
   *   The previous yml content, used to detect changed keys.
   * @param string $newContent
   *   The new filtered yml content.
   * @param string $installFile
   *   Absolute path to the .install file to number the hook against.
   *
   * @return string
   *   PHP source for the new update hook, ready to append.
   */
  protected function generateUpdateHook(string $configName, string $module, string $directory, string $oldContent, string $newContent, string $installFile): string {
    $keys = $this->extractChangedYamlKeys($oldContent, $newContent);
    if (empty($keys)) {
      $keys = $this->extractTopLevelYamlKeys($newContent);
    }
    $hookNumber = $this->nextUpdateHookNumber($installFile, $module);

    $keyLines = implode(",\n    ", array_map(fn($k) => "'$k'", $keys));

    return "\nfunction {$module}_update_{$hookNumber}() {\n  \\Drupal::service('distro_helper.updates')->updateConfig('$configName', [\n    $keyLines,\n  ], '$module', '$directory');\n}\n";
  }

  /**
   * Returns the top-level YAML keys that differ between two content strings.
   *
   * @param string $oldContent
   *   The previous raw yml content.
   * @param string $newContent
   *   The new raw yml content.
   *
   * @return string[]
   *   Keys whose values changed, were added, or whose nested values differ.
   */
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

  /**
   * Recursively finds keys that differ between two arrays.
   *
   * Nested differences are reported as '#'-delimited paths
   * (e.g. 'parent#child').
   *
   * @param array $old
   *   The previous value array.
   * @param array $new
   *   The new value array.
   * @param string $prefix
   *   Key path prefix for nested calls.
   *
   * @return string[]
   *   Flat list of changed key paths.
   */
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
   *
   * @param string $installFile
   *   Absolute path to the .install file.
   * @param string $hookCode
   *   The PHP function source to append.
   *
   * @throws \RuntimeException
   *   If the install file does not exist or cannot be written.
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
   * Resolves the module name and config install directory for a config file.
   *
   * For files inside a module's config dir the module name and subdirectory
   * are extracted directly. For profile-level config a relative path from
   * the module is used.
   *
   * @param string $destPath
   *   Absolute path to the destination yml file.
   *
   * @return array{0: string, 1: string}
   *   A [module, directory] tuple.
   */
  protected function resolveModuleAndDirectory(string $destPath): array {
    if (preg_match('#/modules/([^/]+)/config/([^/]+)/#', $destPath, $m)) {
      return [$m[1], $m[2]];
    }
    if (preg_match('#/config/([^/]+)/#', $destPath, $m)) {
      // Relative path from wri_common/config/ up to the profile's
      // /config/<dir>/.
      return ['wri_common', '../../../config/' . $m[1]];
    }
    return ['wri_common', 'install'];
  }

  /**
   * Extracts top-level YAML key names from config file content.
   *
   * Skips Drupal metadata keys that should not be passed to updateConfig.
   *
   * @param string $content
   *   Raw yml file content.
   *
   * @return string[]
   *   The yml file content with skip items removed.
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
   *
   * @param string $installFile
   *   Absolute path to the module's .install file.
   * @param string $moduleName
   *   The module machine name.
   *
   * @return int
   *   One higher than the current maximum hook number, or the Drush-version-
   *   derived baseline when no hooks exist yet.
   */
  protected function nextUpdateHookNumber(string $installFile, string $moduleName): int {
    $content = file_get_contents($installFile);
    preg_match_all('/function ' . preg_quote($moduleName, '/') . '_update_(\d+)\(/', $content, $matches);
    if (!empty($matches[1])) {
      return (int) max($matches[1]) + 1;
    }

    return $this->drushVersionAsHookNumber();
  }

  /**
   * Returns a hook number baseline derived from the installed Drush version.
   *
   * Converts major.minor.patch to major * 1000 + minor * 100 + patch, matching
   * the Drupal update hook numbering convention (e.g. 10.5.0 → 10500).
   *
   * @return int
   *   The computed baseline hook number.
   */
  protected function drushVersionAsHookNumber(): int {
    $version = Drush::getVersion() ?? '0.0.0';
    if (preg_match('/^(\d+)\.(\d+)\.(\d+)/', $version, $m)) {
      return (int) $m[1] * 1000 + (int) $m[2] * 100 + (int) $m[3];
    }
    return 10000;
  }

  /**
   * Returns the path to a module's .install file, creating it if absent.
   *
   * @param string $module
   *   The module machine name.
   * @param string $destPath
   *   Absolute path to the destination config file, used to locate the module
   *   directory.
   *
   * @return string
   *   Absolute path to the .install file.
   */
  protected function resolveInstallFile(string $module, string $destPath): string {
    if (preg_match('#(.*?/modules/' . preg_quote($module, '#') . ')/#', $destPath, $m)) {
      $moduleDir = $m[1];
    }
    $installFile = "$moduleDir/$module.install";
    if (!file_exists($installFile)) {
      file_put_contents($installFile, "<?php\n");
      $this->io()->writeln("  Created: $module.install");
    }
    return $installFile;
  }

  /**
   * Prompts the user to pick a module and returns its config/install path.
   *
   * Uses the same Question + setAutocompleterValues pattern as drush generate.
   *
   * @param string $profileBase
   *   Absolute path to the profile root directory.
   * @param string $filename
   *   The config filename to write (e.g. 'node.type.page.yml').
   *
   * @return string|null
   *   The destination path, or NULL if the user cancels.
   */
  protected function askForModuleDestination(string $profileBase, string $filename): ?string {
    $modulesDir = $profileBase . '/modules';
    $modules = array_map('basename', glob($modulesDir . '/*', GLOB_ONLYDIR) ?: []);
    sort($modules);

    $this->io()->note("'$filename' was not found in any module under '$this->installProfile'. Choose where to write it.");

    $question = new Question('Module machine name: ');
    $question->setAutocompleterValues($modules);
    $question->setValidator(function (?string $value) use ($modules): string {
      if ($value === NULL || $value === '') {
        throw new \RuntimeException('A module name is required.');
      }
      if (!in_array($value, $modules, TRUE)) {
        throw new \RuntimeException("'$value' is not a module under '$this->installProfile'.");
      }
      return $value;
    });

    $module = $this->io()->askQuestion($question);
    return "$modulesDir/$module/config/install/$filename";
  }

  /**
   * Recursively searches a directory for a file matching the given name.
   *
   * @param string $directory
   *   The root directory to search within.
   * @param string $filename
   *   The filename to look for.
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
