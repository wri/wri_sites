<?php

declare(strict_types=1);

namespace Drupal\wri_event_callout\Plugin\Field\FieldFormatter;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Event callout behavior' formatter.
 *
 * Controls how the Upcoming Event block decides which event to render.
 *
 * @FieldFormatter(
 *   id = "wri_event_callout_event_callout_behavior",
 *   label = @Translation("Event callout behavior"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
final class EventCalloutBehaviorFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The time service.
   */
  protected TimeInterface $time;

  /**
   * Constructs a new EventCalloutBehaviorFormatter.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager,
    TimeInterface $time,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'view_id' => 'event_callout',
      'display_id' => 'block_1',
      'event_date_field' => 'field_date_time',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['view_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View ID'),
      '#default_value' => $this->getSetting('view_id'),
      '#description' => $this->t('Machine name of the View used to fetch the next upcoming event by topic.'),
      '#required' => TRUE,
    ];

    $elements['display_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View display ID'),
      '#default_value' => $this->getSetting('display_id'),
      '#description' => $this->t('Display ID (for example "block_1") of the View.'),
      '#required' => TRUE,
    ];

    $elements['event_date_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event date field'),
      '#default_value' => $this->getSetting('event_date_field'),
      '#description' => $this->t('Machine name of the date or daterange field used to decide if the event is upcoming.'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('View: @view (@display)', [
      '@view' => $this->getSetting('view_id'),
      '@display' => $this->getSetting('display_id'),
    ]);

    $summary[] = $this->t('Event date field: @field', [
      '@field' => $this->getSetting('event_date_field'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    $entity = $items->getEntity();
    if (!$entity instanceof BlockContentInterface || $entity->bundle() !== 'upcoming_event') {
      return $elements;
    }

    // Behavior value:
    // - show_next_event
    // - link_to_recording
    // - hide_block
    // Defaults to show_next_event (matches field default).
    $behavior = $items->isEmpty() ? 'show_next_event' : (string) $items->first()->value;

    /** @var \Drupal\node\NodeInterface|null $event */
    $event = NULL;
    if ($entity->hasField('field_event_to_feature') && !$entity->get('field_event_to_feature')->isEmpty()) {
      $event = $entity->get('field_event_to_feature')->entity;
    }

    // Case 1: no explicit event chosen, always use "next upcoming by topic".
    if (!$event instanceof NodeInterface) {
      $build = $this->buildUpcomingByTopic($entity);
      if (!empty($build)) {
        $elements[0] = $build;
      }
      return $elements;
    }

    // Case 2: explicit event exists and has not ended yet, always show it.
    if ($this->isEventNotEnded($event)) {
      $elements[0] = $this->buildEventCallout($event);
      return $elements;
    }

    // From here on, the event is over.
    switch ($behavior) {
      case 'link_to_recording':
        // Show the chosen (past) event in Callout view mode.
        if ($event->field_body_contains_recording->value == TRUE) {
          $elements[0] = $this->buildEventCallout($event);
        }
        return $elements;

      case 'show_next_event':
        // Show the next upcoming event in the chosen topic.
        $build = $this->buildUpcomingByTopic($entity);
        if (!empty($build)) {
          $elements[0] = $build;
        }
        return $elements;

      case 'hide_block':
      default:
        // Hide block, or fallback when everything else fails.
        return $elements;
    }
  }

  /**
   * Returns TRUE if the event has not ended yet.
   *
   * Smart Date range stores:
   * - start in ->value
   * - end in ->end_value.
   */
  protected function isEventNotEnded(NodeInterface $event): bool {
    $date_field = $this->getSetting('event_date_field');

    if (!$event->hasField($date_field) || $event->get($date_field)->isEmpty()) {
      return FALSE;
    }

    $item = $event->get($date_field)->first();

    // Prefer end timestamp; fall back to start if end is missing.
    $end = $item->end_value ?? NULL;
    $start = $item->value ?? NULL;

    $timestamp = $end ?: $start;
    if (!$timestamp) {
      return FALSE;
    }

    $now = $this->time->getRequestTime();
    return (int) $timestamp >= $now;
  }

  /**
   * Render the event in the Callout view mode.
   */
  protected function buildEventCallout(NodeInterface $event): array {
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    return $view_builder->view($event, 'callout');
  }

  /**
   * Render the next upcoming event for this block's topic via a View.
   */
  protected function buildUpcomingByTopic(BlockContentInterface $block): array {
    if (!$block->hasField('field_topic') || $block->get('field_topic')->isEmpty()) {
      return [];
    }

    $topic = $block->get('field_topic')->entity;
    if (!$topic) {
      return [];
    }

    $tid = $topic->id();
    $view_id = $this->getSetting('view_id');
    $display_id = $this->getSetting('display_id');

    $view = Views::getView($view_id);
    if (!$view || !$view->access($display_id)) {
      return [];
    }

    // Pass TID as argument to the view.
    return $view->buildRenderable($display_id, [$tid]);
  }

}
