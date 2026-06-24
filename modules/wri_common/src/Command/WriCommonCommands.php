<?php

namespace Drupal\wri_common\Command;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class WriCommonCommands extends DrushCommands {

  /**
   * The Drupal app root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $app_root) {
    $this->appRoot = $app_root;
  }

  /**
   * Copies a config file into the matching wri_sites profile file, stripping uuid and langcode.
   *
   * Finds a file with the same name under web/profiles/contrib/wri_sites and
   * overwrites it with the contents of the source file, removing the top-level
   * `uuid` and `langcode` keys that must not exist in profile config.
   *
   * @param string $source
   *   Path to the source config file (absolute or relative to the Drupal root).
   *
   * @command wri_common:copy-config-to-profile
   * @usage drush wri_common:copy-config-to-profile config/node.type.page.yml
   *   Copy node.type.page.yml into the matching wri_sites config file.
   * @aliases wri-ccp
   */
  public function copyConfigToProfile(string $source): void {
    // Resolve relative paths against the Drupal root.
    if (!str_starts_with($source, '/')) {
      $source = $this->appRoot . '/' . $source;
    }

    if (!file_exists($source)) {
      throw new \InvalidArgumentException("Source file not found: {$source}");
    }

    $filename = basename($source);
    $profileBase = $this->appRoot . '/profiles/contrib/wri_sites';

    if (!is_dir($profileBase)) {
      throw new \RuntimeException("wri_sites directory not found: {$profileBase}");
    }

    $destPath = $this->findFileInDirectory($profileBase, $filename);

    if ($destPath === NULL) {
      throw new \RuntimeException("No file named '{$filename}' found under {$profileBase}");
    }

    $content = file_get_contents($source);
    $filtered = $this->stripTopLevelKeys($content, ['uuid', 'langcode']);

    if (file_put_contents($destPath, $filtered) === FALSE) {
      throw new \RuntimeException("Could not write to: {$destPath}");
    }

    $this->io()->success("Copied {$source}");
    $this->logger()->info("Destination: {$destPath}");
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


}
