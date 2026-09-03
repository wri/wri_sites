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
      'link_url' => '',
      'link_title' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link url'),
      '#default_value' => $this->configuration['link_url'],
    ];
    $form['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
      '#default_value' => $this->configuration['link_title'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['link_url'] = $form_state->getValue('link_url');
    $this->configuration['link_title'] = $form_state->getValue('link_title');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // A link to '#resources'
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#attributes' => ['href' => $this->configuration['link_url'], 'class' => 'button white download'],
      '#value' => $this->configuration['link_title'],
    ];
    return $build;
  }

}
