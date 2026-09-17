<?php

declare(strict_types=1);

namespace Drupal\wri_megamenu_2026\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'menu_item_reference' field type.
 *
 * An entity reference field permanently locked to menu_link_content, so
 * fields of this type just reference one menu item.
 *
 * @FieldType(
 *   id = "menu_item_reference",
 *   label = @Translation("Menu item reference"),
 *   description = @Translation("This field stores a reference to a menu link item."),
 *   category = "reference",
 *   default_widget = "menu_item_reference_select",
 *   default_formatter = "menu_item_reference_label",
 * )
 */
class MenuItemReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
      'target_type' => 'menu_link_content',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
      'target_menu' => '',
      'handler' => 'default:menu_link_content',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // The target type is always menu_link_content; nothing to configure.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach (\Drupal::entityTypeManager()->getStorage('menu')->loadMultiple() as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    asort($options);

    $element['target_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => $options,
      '#default_value' => $this->getSetting('target_menu'),
      '#required' => TRUE,
      '#description' => $this->t('Only items from this menu can be referenced. The widget lists them in the order they are displayed on the menu edit page.'),
    ];

    return $element;
  }

}
