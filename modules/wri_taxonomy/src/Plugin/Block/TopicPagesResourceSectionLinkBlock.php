<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a topic pages resource section link block.
 *
 * @Block(
 *   id = "wri_taxonomy_topic_pages_resource_section_link",
 *   admin_label = @Translation("Topic pages resource section link"),
 *   category = @Translation("WRI block"),
 * )
 */
final class TopicPagesResourceSectionLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'example' => $this->t('Hello world!'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['example'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Example'),
      '#default_value' => $this->configuration['example'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['example'] = $form_state->getValue('example');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#markup' => $this->t('It works!'),
    ];
    return $build;
  }

}
