<?php

declare(strict_types=1);

namespace Drupal\wri_event\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\FieldWidget\SelectWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'select' widget.
 */
#[CustomFieldWidget(
  id: 'select_webform_options',
  label: new TranslatableMarkup('Select webform options set'),
  category: new TranslatableMarkup('Lists'),
  field_types: [
    'string',
  ],
)]
class WebformOptionsSelect extends SelectWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $defaults = parent::defaultSettings();
    // For compatibility with inherited functions, a valid value is needed for
    // allowed_values:
    $defaults['settings']['allowed_values'] = [];
    return $defaults;
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $form = parent::widgetSettingsForm($form_state, $field);
    unset($form['settings']['allowed_values']);
    unset($form["settings"]["add_row"]);

    $options = [];
    $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    foreach ($webforms as $id => $webform) {
      $options[$id] = $webform->label();
    }

    return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $webform_option_sets = $this->entityTypeManager->getStorage('webform_options')->loadByProperties(['category' => 'Sector Options']);
    $options = [];
    foreach ($webform_option_sets as $id => $option_set) {
      $options[$id] = $option_set->label();
    }
    // Add our widget type and additional properties and return.
    $element['#type'] = 'select';
    $element['#options'] = $options;
    $element['#empty_option'] = $settings['empty_option'];
    return $element;
  }

}
