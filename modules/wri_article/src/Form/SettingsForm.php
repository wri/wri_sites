<?php

declare(strict_types=1);

namespace Drupal\wri_article\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure WRI article settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wri_article_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wri_article.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enable_main_content_b'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Main Content B'),
      '#description' => $this->t('Set all Article content to use Main Content B.'),
      '#default_value' => $this->config('wri_article.settings')->get('enable_main_content_b'),
      '#config_target' => 'wri_article.settings:enable_main_content_b',
    ];

    $form['article_templates'] = [
      '#type' => 'details',
      '#title' => $this->t('Article Templates'),
      '#description' => $this->t('By default, all articles use the <strong>Insights template</strong>. Use the fields below to assign Article Resource Types to a different template instead.'),
      '#open' => TRUE,
    ];

    $form['article_templates']['insights_template_image'] = [
      '#type' => 'details',
      '#title' => $this->t('Insights template (default)'),
      '#open' => TRUE,
    ];

    $form['article_templates']['insights_template_image']['details'] = [
      '#type' => 'html_tag',
      '#tag' => 'figure',
      'link' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => '/profiles/contrib/wri_sites/modules/wri_article/images/insights.png',
          'target' => '_blank',
        ],
        'image' => [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => '/profiles/contrib/wri_sites/modules/wri_article/images/insights.png',
            'alt' => $this->t('Insights template preview'),
            'width' => '100',
          ],
        ],
      ],
      'caption' => [
        '#type' => 'html_tag',
        '#tag' => 'figcaption',
        '#value' => $this->t('Insights template (default)'),
      ],
    ];

    $template_fields = [
      'technical_perspective_template' => $this->t('Technical Perspective template'),
      'update_template' => $this->t('Update template'),
      'news_template' => $this->t('News template'),
    ];
    $template_descriptions = [
      'technical_perspective_template' => $this->t('Top-level Resource Types that use the Technical Perspective Template.'),
      'update_template' => $this->t('Top-level Resource Types that use the Update Template.'),
      'news_template' => $this->t('Top-level Resource Types that use the News Template.'),
    ];

    foreach ($template_fields as $key => $label) {
      $stored_tids = $this->config('wri_article.settings')->get($key) ?? [];
      $stored_child_tids = $this->config('wri_article.settings')->get($key . '_child_field') ?? [];
      $default_terms = !empty($stored_tids) ? array_values($this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($stored_tids)) : [];
      $default_child_terms = !empty($stored_tids) ? array_values($this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($stored_child_tids)) : [];
      $form['article_templates'][$key] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => TRUE,
      ];
      $form['article_templates'][$key]['preview'] = [
        '#type' => 'html_tag',
        '#tag' => 'figure',
        'link' => [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#attributes' => [
            'href' => '/profiles/contrib/wri_sites/modules/wri_article/images/' . $key . '.png',
            'target' => '_blank',
          ],
          'image' => [
            '#type' => 'html_tag',
            '#tag' => 'img',
            '#attributes' => [
              'src' => '/profiles/contrib/wri_sites/modules/wri_article/images/' . $key . '.png',
              'alt' => $label,
              'width' => '100',
            ],
          ],
        ],
        'caption' => [
          '#type' => 'html_tag',
          '#tag' => 'figcaption',
          '#value' => $this->t('Preview'),
        ],
      ];
      $form['article_templates'][$key][$key . '_field'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_term',
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'top_level_resource_types',
            'display_name' => 'entity_reference_1',
          ],
        ],
        '#tags' => TRUE,
        '#title' => $template_descriptions[$key],
        '#default_value' => $default_terms,
        '#config_target' => new ConfigTarget(
          'wri_article.settings',
          $key,
          NULL,
          [self::class, 'termIdsFromAutocomplete'],
        ),
      ];

      $form['article_templates'][$key][$key . '_child_field'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_term',
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'top_level_resource_types',
            'display_name' => 'children',
          ],
        ],
        '#tags' => TRUE,
        '#title' => 'Non-' . $template_descriptions[$key],
        '#default_value' => $stored_child_tids,
        '#config_target' => new ConfigTarget(
          'wri_article.settings',
          $key . '_child_field',
          NULL,
          [self::class, 'termIdsFromAutocomplete'],
        ),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $was_main_content_b = $form["enable_main_content_b"]["#default_value"];
    $enable_main_content_b = $form_state->getValue('enable_main_content_b');

    parent::submitForm($form, $form_state);
    if ($was_main_content_b == $enable_main_content_b) {
      // Nothing changes.
      return;
    }

    $view_mode = $enable_main_content_b ? 'main_content_b' : 'main_content';
    $display_config = $this->configFactory()->getEditable('core.entity_view_display.node.article.full');
    $sections = $display_config->get('third_party_settings.layout_builder.sections');
    $first_component = array_key_first($sections[0]['components']);
    $sections[0]['components'][$first_component]['configuration']['view_mode'] = $view_mode;
    $display_config->set('third_party_settings.layout_builder.sections', $sections)->save();

    $batch = [
      'title' => $this->t('Updating Article nodes to @view_mode...', ['@view_mode' => $view_mode]),
      'operations' => [
        [[self::class, 'batchUpdateArticleNodes'], [$enable_main_content_b]],
      ],
      'finished' => [self::class, 'batchFinished'],
    ];
    batch_set($batch);
  }

  /**
   * ConfigTarget toConfig callback: extract term IDs from entity_autocomplete.
   */
  public static function termIdsFromAutocomplete(mixed $value): array {
    $to_return = [];
    if (is_array($value)) {
      foreach ($value as $term) {
        $to_return[] = $term['target_id'];
      }
    }
    return $to_return;
  }

  /**
   * Batch operation: update article nodes to use main_content_b view mode.
   */
  public static function batchUpdateArticleNodes(bool $enable_main_content_b, array &$context): void {
    $from_view_mode = $enable_main_content_b ? 'main_content' : 'main_content_b';
    $to_view_mode = $enable_main_content_b ? 'main_content_b' : 'main_content';
    if (empty($context['sandbox'])) {
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->exists('layout_builder__layout')
        ->accessCheck(FALSE)
        ->execute();

      if (!$nids) {
        $context['finished'] = 1;
        $context['results']['count'] = 0;
        return;
      }

      $context['sandbox']['nids'] = array_values($nids);
      $context['sandbox']['total'] = count($nids);
      $context['sandbox']['current'] = 0;
      $context['results']['count'] = 0;
    }

    $chunk_size = 25;
    $chunk = array_slice($context['sandbox']['nids'], $context['sandbox']['current'], $chunk_size);

    /** @var \Drupal\node\NodeInterface[] $nodes */
    foreach (Node::loadMultiple($chunk) as $node) {
      $changed = FALSE;
      $layout = $node->get('layout_builder__layout');
      if ($layout->isEmpty()) {
        continue;
      }

      $sections = $layout->getSections();
      if (empty($sections)) {
        continue;
      }

      foreach ($sections as $section) {
        foreach ($section->getComponents() as $component) {
          $config = $component->get('configuration') ?? [];
          if (($config['id'] ?? '') === 'entity_view:node' && $config['view_mode'] === $from_view_mode) {
            $config['view_mode'] = $to_view_mode;
            $component->set('configuration', $config);
            $changed = TRUE;
          }
        }
      }

      if ($changed) {
        $node->set('layout_builder__layout', $sections);
        $node->setNewRevision(FALSE);
        $node->save();
        $context['results']['count']++;
      }
    }

    $context['sandbox']['current'] += count($chunk);
    $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['total'];
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural(
        $results['count'],
        'Updated @count Article node.',
        'Updated @count Article nodes.',
      ));
    }
    else {
      \Drupal::messenger()->addError(t('An error occurred while updating Article nodes.'));
    }
  }

}
