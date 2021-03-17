<?php

namespace Drupal\wri_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'AddressBlock' block.
 *
 * @Block(
 *  id = "address_block",
 *  admin_label = @Translation("Address block"),
 * )
 */
class AddressBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['address'] = [
      '#type'   => 'text_format',
      '#title' => $this->t('Address'),
      '#description' => $this->t('This field supports html.'),
      '#default_value' => isset($this->configuration['address']) ? $this->configuration['address'] :
      '<p>WRI United States</br>
        10 G Street NE Suite 800</br>
        Washington DC, 20002</br>
        United States</p>',
      '#weight' => '1',
      '#format' => 'basic_html',
      '#allowed_formats' => ['basic_html'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['address'] = $form_state->getValue('address');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $address = $this->configuration['address']['value'];
    $build['address_block']['#markup'] = $address;
    return $build;
  }

}
