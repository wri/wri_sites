<?php

namespace Drupal\wri_common\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

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
   * Command description here.
   */
  #[CLI\Command(name: 'wri_common:copy-config-to-profile', aliases: ['wri-ccp'])]
  #[CLI\Argument(name: 'source', description: 'The source of the config file.')]
  #[CLI\Usage(name: 'wri_common:copy-config-to-profile config/my-config.yml', description: 'Usage description')]
  public function copyConfigToProfile(string $source) {
    // Resolve relative paths against the Drupal root.
    if (!str_starts_with($source, '/')) {
      $source = $this->appRoot . '/../' . $source;
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
    $filtered = $this->stripField('field_share_with_io', $filtered);

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

}
