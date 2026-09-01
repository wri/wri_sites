<?php

namespace Drupal\wri_megamenu_2026\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the WRI Megamenu 2026 swap.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The path the 2026 blocks are currently shown on / old blocks hidden on.
   */
  const TESTING_2026_PATH = '/testing-2026-menu';

  /**
   * The path the old blocks are shown on / 2026 blocks hidden on, once
   * "Enable everywhere" is checked.
   */
  const TESTING_LEGACY_PATH = '/testing-legacy-menu';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SettingsForm.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $typedConfigManager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wri_megamenu_2026.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wri_megamenu_2026_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enable_everywhere'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable everywhere'),
      '#description' => $this->t('Blocks in the active theme that only show on <code>@testing2026</code> will show everywhere except <code>@legacy</code>, and blocks that are hidden on <code>@testing2026</code> will only show on <code>@legacy</code> instead. Uncheck to reverse this.', [
        '@testing2026' => static::TESTING_2026_PATH,
        '@legacy' => static::TESTING_LEGACY_PATH,
      ]),
      '#default_value' => $this->config('wri_megamenu_2026.settings')->get('enable_everywhere'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('wri_megamenu_2026.settings');
    $was_enabled = (bool) $config->get('enable_everywhere');
    $enable_everywhere = (bool) $form_state->getValue('enable_everywhere');

    if ($enable_everywhere && !$was_enabled) {
      $this->applyEnableEverywhere($config);
    }
    elseif (!$enable_everywhere && $was_enabled) {
      $this->revertEnableEverywhere($config);
    }

    $config->set('enable_everywhere', $enable_everywhere)->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Swaps every affected block's testing-page visibility for the legacy one.
   *
   * Snapshots each affected block's original negate/pages into config
   * first, so revertEnableEverywhere() can restore them exactly.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The module's settings config, edited (not saved) in place.
   */
  protected function applyEnableEverywhere($config) {
    $overrides = [];

    foreach ($this->getActiveThemeBlocks() as $block) {
      if (!$block->getVisibilityConditions()->has('request_path')) {
        continue;
      }
      $configuration = $block->getVisibilityCondition('request_path')->getConfiguration();
      $pages = $this->pagesLines($configuration['pages'] ?? '');
      if (!in_array(static::TESTING_2026_PATH, $pages, TRUE)) {
        continue;
      }

      $overrides[] = [
        'id' => $block->id(),
        'negate' => !empty($configuration['negate']),
        'pages' => $configuration['pages'] ?? '',
      ];

      // Shown only on /testing-2026-menu -> shown everywhere except
      // /testing-legacy-menu. Hidden on /testing-2026-menu -> shown only
      // on /testing-legacy-menu.
      $configuration['negate'] = empty($configuration['negate']);
      $configuration['pages'] = static::TESTING_LEGACY_PATH;
      $block->setVisibilityConfig('request_path', $configuration);
      $block->save();
    }

    $config->set('block_overrides', $overrides);
  }

  /**
   * Restores every block snapshotted by applyEnableEverywhere().
   *
   * @param \Drupal\Core\Config\Config $config
   *   The module's settings config, edited (not saved) in place.
   */
  protected function revertEnableEverywhere($config) {
    $storage = $this->entityTypeManager->getStorage('block');
    foreach ((array) $config->get('block_overrides') as $override) {
      $block = $storage->load($override['id']);
      if (!$block || !$block->getVisibilityConditions()->has('request_path')) {
        continue;
      }
      $configuration = $block->getVisibilityCondition('request_path')->getConfiguration();
      $configuration['negate'] = $override['negate'];
      $configuration['pages'] = $override['pages'];
      $block->setVisibilityConfig('request_path', $configuration);
      $block->save();
    }

    $config->set('block_overrides', []);
  }

  /**
   * Loads every block placed in the site's active frontend theme.
   *
   * @return \Drupal\block\BlockInterface[]
   *   The blocks, keyed by block ID.
   */
  protected function getActiveThemeBlocks() {
    $default_theme = $this->config('system.theme')->get('default');
    $storage = $this->entityTypeManager->getStorage('block');
    $ids = $storage->getQuery()
      ->condition('theme', $default_theme)
      ->accessCheck(FALSE)
      ->execute();
    return $storage->loadMultiple($ids);
  }

  /**
   * Splits a request_path condition's "pages" value into trimmed lines.
   *
   * @param string $pages
   *   The raw "pages" configuration value (one path per line).
   *
   * @return string[]
   *   The non-empty, trimmed lines.
   */
  protected function pagesLines($pages) {
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $pages))));
  }

}
