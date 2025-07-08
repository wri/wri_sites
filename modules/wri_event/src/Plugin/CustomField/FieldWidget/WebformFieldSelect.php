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
  id: 'select_webform_field',
  label: new TranslatableMarkup('Select webform field'),
  category: new TranslatableMarkup('Lists'),
  field_types: [
    'string',
  ],
)]
class WebformFieldSelect extends SelectWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $defaults = parent::defaultSettings();
    // For compatibility with inherited functions, a valid value is needed for
    // allowed_values:
    $defaults['settings']['allowed_values'] = [];
    $defaults['settings']['default_webform'] = NULL;
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

    $form['settings']['default_webform'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Webform'),
      '#options' => $options,
      '#default_value' => $this->getSetting('default_webform') ?? NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $options = [];
    if (isset($settings["default_webform"])) {
      $webform_elements = $this->entityTypeManager->getStorage('webform')
        ->load($settings["default_webform"])
        ->getElementsDecodedAndFlattened();
      foreach ($webform_elements as $id => $webform_element) {
        if ($webform_element['#type'] != 'hidden' && isset($webform_element['#title']) && empty($webform_element['#required'])) {
          $title = $webform_element['#title'];
          if (strlen($title) > 30) {
            $title = substr($title, 0, 30) . '...';
          }
          $options[$id] = $title;
        }
      }
    }
    // Add our widget type and additional properties and return.
    $element['#type'] = 'select';
    $element['#options'] = $options;
    $element['#empty_option'] = $settings['empty_option'];
    return $element;
  }

}
