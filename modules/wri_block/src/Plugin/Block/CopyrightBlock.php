<?php

namespace Drupal\wri_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CopyrightBlock' block.
 *
 * @Block(
 *  id = "copyright_block",
 *  admin_label = @Translation("Copyright block"),
 * )
 */
class CopyrightBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
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
    $form['copyright_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright Message'),
      '#description' => $this->t('This field supports tokens, including the current year ([current-date:html_year]), and links.'),
      '#default_value' => $this->configuration['copyright_message'] ?? '&copy; ' . date("Y") . ' World Resources Institute',
      '#maxlength' => 256,
      '#size' => 64,
      '#weight' => '1',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['copyright_message'] = $form_state->getValue('copyright_message');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['copyright_block_copyright_message']['#markup'] = '<p>' . Xss::filter(($this->token->replace($this->configuration['copyright_message'], [])), ['a']) . '</p>';
    return $build;
  }

}
