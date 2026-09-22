<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument from the term on the URL, or a field on that term.
 *
 * With no field name configured, this returns whichever topic the visitor
 * has actively selected via the "All Topics" exposed facet (identifier
 * "topic"), e.g. on a region page that has no topic of its own; failing
 * that, it falls back to the current term's own ID, e.g. on a topic's own
 * page (matching core's "Taxonomy term ID from URL"). With a field name
 * configured, it instead returns the target ID of that entity reference
 * field on the term (e.g. field_primary_topic).
 *
 * Uses wri_taxonomy_get_current_term() so this also works when Views rebuilds
 * the display via /views/ajax (e.g. an exposed filter autosubmit), which has
 * no {taxonomy_term} route parameter of its own.
 */
#[ViewsArgumentDefault(
  id: 'wri_taxonomy_term_field_target_id',
  title: new TranslatableMarkup("Term ID (or a field's target ID) from URL, AJAX-safe"),
)]
class TermFieldTargetId extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * Exposed filter identifier for the "All Topics" facet (facets.facet.all_topics).
   */
  const TOPIC_FACET_IDENTIFIER = 'topic';

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
      '#description' => $this->t('Optional. The machine name of an entity reference field on the taxonomy term from the current page (e.g. field_primary_topic), whose target ID is used as the argument. Leave blank to use the selected "All Topics" facet, falling back to the term\'s own ID.'),
      '#default_value' => $this->options['field_name'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $field_name = $this->options['field_name'];

    if (empty($field_name)) {
      // The "topic" facet is single-value, submitted as a plain scalar
      // (topic=53). InputBag::all() throws on a scalar value, so use get().
      $selected_topic = \Drupal::request()->query->get(static::TOPIC_FACET_IDENTIFIER);
      if (is_scalar($selected_topic) && $selected_topic !== '') {
        return $selected_topic;
      }

      $term = wri_taxonomy_get_current_term();
      return $term instanceof TermInterface ? $term->id() : NULL;
    }

    $term = wri_taxonomy_get_current_term();
    if (!$term instanceof TermInterface || !$term->hasField($field_name)) {
      return NULL;
    }

    return $term->get($field_name)->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url', 'url.query_args:' . static::TOPIC_FACET_IDENTIFIER];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
