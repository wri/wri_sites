<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a topic pages resource section link block.
 *
 * @Block(
 *   id = "wri_taxonomy_topic_pages_resource_section_link",
 *   admin_label = @Translation("Topic pages resource section link"),
 *   category = @Translation("WRI block"),
 * )
 */
final class TopicPagesResourceSectionLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a TopicPagesResourceSectionLinkBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected Token $token,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'link_title' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
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
    $this->configuration['link_title'] = $form_state->getValue('link_title');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#attributes' => [
        'href' => $this->token->replace('[wri_tokens:resources_anchor]', [], ['clear' => TRUE]),
        'class' => 'button white download',
      ],
      '#value' => $this->configuration['link_title'],
    ];
    return $build;
  }

}
