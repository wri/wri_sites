<?php

namespace Drupal\wri_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'HowYouCanHelp' block.
 *
 * @Block(
 *  id = "how_you_can_help",
 *  admin_label = @Translation("How You Can Help Block"),
 * )
 */
class HowYouCanHelpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The path alias manager.
   *
   * @var Drupal\Core\Block\BlockManager
   */
  protected $pluginManagerBlock;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManager $plugin_manager_block) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManagerBlock = $plugin_manager_block;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '0',
    ];

    $form['menu_link'] = [
      '#type' => 'item',
      '#title' => $this->t('Menu settings'),
      '#markup' => $this->t('The menu links can be managed at <a href="@link" target="_blank">The How You Can Help menu</a>.', ['@link' => '/admin/structure/menu/manage/how-you-can-help']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['description'] = $form_state->getValue('description');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['title']['#markup'] = '<h3 class="h4 white">' . $this->configuration['title'] . '</h3>';
    $build['description']['#markup'] = '<p>' . $this->configuration['description'] . '</p>';

    // You can hard code configuration or you load from settings.
    $config = [
      'label_display' => '0',
    ];
    $plugin_block = $this->pluginManagerBlock->createInstance('system_menu_block:how-you-can-help', $config);
    $build['menu'] = $plugin_block->build();

    return $build;
  }

}
