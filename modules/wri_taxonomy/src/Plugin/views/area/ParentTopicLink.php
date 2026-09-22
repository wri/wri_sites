<?php

declare(strict_types=1);

namespace Drupal\wri_taxonomy\Plugin\views\area;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Attribute\ViewsArea;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Links to a related term of the current page's term, with a static fallback.
 *
 * Renders "All {name}", linking to the target term's page with
 * [wri_tokens:resources_anchor] appended, using
 * wri_taxonomy_get_current_term() to find the current term and a
 * configurable field (e.g. "parent", or field_primary_topic) to find the
 * term to link to. If that field is empty, renders the configured fallback
 * link instead (e.g. "All Topics" linking to /resources).
 */
#[ViewsArea('wri_taxonomy_parent_topic_link')]
class ParentTopicLink extends AreaPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_name'] = ['default' => 'parent'];
    $options['fallback_url'] = ['default' => ''];
    $options['fallback_label'] = ['default' => ''];
    $options['link_class'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#description' => $this->t('The machine name of an entity reference field on the current term whose target term to link to, e.g. "parent" for the term hierarchy parent, or field_primary_topic.'),
      '#default_value' => $this->options['field_name'],
    ];
    $form['fallback_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback URL'),
      '#description' => $this->t('Used when the field above is empty on the current term. Internal paths only, e.g. /resources.'),
      '#default_value' => $this->options['fallback_url'],
    ];
    $form['fallback_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback label'),
      '#default_value' => $this->options['fallback_label'],
    ];
    $form['link_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link class'),
      '#default_value' => $this->options['link_class'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($empty && empty($this->options['empty'])) {
      return [];
    }

    $anchor = \Drupal::config('wri_taxonomy.settings')->get('resources_anchor') ?: 'resources';
    $field_name = $this->options['field_name'] ?: 'parent';
    $term = wri_taxonomy_get_current_term();
    $target_id = ($term instanceof TermInterface && $term->hasField($field_name)) ? $term->get($field_name)->target_id : NULL;
    $target = $target_id ? \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($target_id) : NULL;

    if ($target instanceof TermInterface) {
      $url = $target->toUrl();
      $url->setOption('fragment', $anchor);
      $title = $this->t('All @name', ['@name' => $target->label()]);
      $cache_tags = Cache::mergeTags($target->getCacheTags(), ['config:wri_taxonomy.settings']);
    }
    else {
      $url = Url::fromUserInput($this->options['fallback_url'] ?: '/');
      $title = $this->options['fallback_label'];
      $cache_tags = ['config:wri_taxonomy.settings'];
    }

    $build = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $cache_tags,
      ],
    ];
    if ($this->options['link_class']) {
      $build['#attributes']['class'][] = $this->options['link_class'];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // wri_taxonomy_get_current_term() falls back to a hidden exposed-filter
    // value (WRI_TAXONOMY_CURRENT_TERM_PARAM) when there's no route context.
    return ['route', 'url.query_args:' . WRI_TAXONOMY_CURRENT_TERM_PARAM];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['config:wri_taxonomy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
