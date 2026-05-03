<?php

declare(strict_types=1);

namespace Drupal\wri_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'wri_media_with_caption' formatter.
 *
 * @FieldFormatter(
 *   id = "wri_media_with_caption",
 *   label = @Translation("Media with caption"),
 *   field_types = {"entity_reference"},
 * )
 */
final class WriMediaWithCaptionFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $setting = ['caption_field' => ''];
    return $setting + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);
    $elements['caption_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption field'),
      '#default_value' => $this->getSetting('caption_field'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Caption field: @caption_field', ['@caption_field' => $this->getSetting('caption_field')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = parent::viewElements($items, $langcode);
    if (isset($this->settings["caption_field"])) {
      $parent_item = $items->getParent()->getEntity();
      if ($parent_item->hasTranslation($langcode)) {
        $parent_item = $parent_item->getTranslation($langcode);
      }
      $caption_value = $parent_item->get($this->settings["caption_field"])->getValue() ?? '';
      foreach ($elements as $delta => $element) {
        $elements[$delta]['#media']->caption = $caption_value;
      }
      $elements['#cache']['contexts'][] = 'url';
    }



    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media items.
    return ($field_definition->getFieldStorageDefinition()
      ->getSetting('target_type') == 'media');
  }

}
