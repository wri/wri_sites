<?php

namespace Drupal\wri_menus\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_block\Plugin\Block\MenuBlock as OriginalMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an extended Menu block that allows showing the parent.
 *
 * @Block(
 *   id = "parent_menu_block",
 *   admin_label = @Translation("Menu block (show parent)"),
 *   category = @Translation("Menus"),
 *   deriver = "Drupal\wri_menus\Plugin\Derivative\MenuBlock",
 *   forms = {
 *     "settings_tray" = "\Drupal\system\Form\SystemMenuOffCanvasForm",
 *   },
 * )
 */
class ParentMenuBlock extends OriginalMenuBlock {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    // Overwriting the menu tree with our own instance so links that the same
    // url can be added to a menu multiple times.
    $instance->menuTree = $container->get('wri_menus.link_tree');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    // The MenuBlock module only wants to allow showing parent if you are
    // deeper than the first level. We want to allow it always.
    $form['advanced']['render_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Render parent item</strong>'),
      '#default_value' => $config['render_parent'],
      '#description' => $this->t('If the <strong>Initial visibility level</strong> is greater than 1, or a <strong>Fixed parent item</strong> is chosen, only the children of that item will be displayed by default. Enable this option to <strong>always</strong> render the parent item in the menu.'),
    ];

    return $form;
  }

}
