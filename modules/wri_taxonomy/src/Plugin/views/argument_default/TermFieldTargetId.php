<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument from an entity reference field on the term from the URL.
 *
 * Unlike core's "Taxonomy term ID from URL", this returns the target ID of a
 * field ON that term (e.g. field_primary_topic), not the term's own ID.
 */
#[ViewsArgumentDefault(
  id: 'wri_taxonomy_term_field_target_id',
  title: new TranslatableMarkup("Target ID of a field on the term from URL"),
)]
class TermFieldTargetId extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new TermFieldTargetId instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_name'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#description' => $this->t('The machine name of an entity reference field on the taxonomy term from the current page (e.g. field_primary_topic). That field\'s target ID is used as the argument.'),
      '#default_value' => $this->options['field_name'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $field_name = $this->options['field_name'];
    if (empty($field_name)) {
      return NULL;
    }

    $term = $this->routeMatch->getParameter('taxonomy_term');
    if (!$term instanceof TermInterface || !$term->hasField($field_name)) {
      return NULL;
    }

    return $term->get($field_name)->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
