<?php

/**
 * @file
 * Contains \Drupal\field_permissions\Form\FieldPermissionsConfigEditForm.
 */

namespace Drupal\field_permissions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\field_ui\FieldUI;

/**
 * Provides a form for the field settings form.
 */
class FieldPermissionsConfigEditForm extends FieldConfigEditForm {

  /**
    * {@inheritdoc}.
    */
   public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
     dpm("aaa");
    // $this->targetEntityTypeId = $entity_type_id;
    $form = parent::buildForm($form, $form_state);
    // objetc_log("aaa","Form alter");
    /*
    // Change replace_pattern to avoid undesired dots.
    $form['id']['#machine_name']['replace_pattern'] = '[^a-z0-9_]+';
    $definition = $this->entityManager->getDefinition($this->targetEntityTypeId);
    $form['#title'] = $this->t('Add new %label @entity-type', array('%label' => $definition->getLabel(), '@entity-type' => $this->entityType->getLowercaseLabel()));
    */
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    dpm("TEST!");
    // $field_storage = $this->entity->getFieldStorageDefinition();
    // dpm("aaa");
    /*
    $bundles = $this->entityManager->getBundleInfo($this->entity->getTargetEntityTypeId());

    $form_title = $this->t('%field settings for %bundle', array(
      '%field' => $this->entity->getLabel(),
      '%bundle' => $bundles[$this->entity->getTargetBundle()]['label'],
    ));
    $form['#title'] = $form_title;

    if ($field_storage->isLocked()) {
      $form['locked'] = array(
        '#markup' => $this->t('The field %field is locked and cannot be edited.', array('%field' => $this->entity->getLabel())),
      );
      return $form;
    }

    // Build the configurable field values.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->getLabel() ?: $field_storage->getName(),
      '#required' => TRUE,
      '#weight' => -20,
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $this->entity->getDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => FieldFilteredMarkup::displayAllowedTags())) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    );

    $form['required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->entity->isRequired(),
      '#weight' => -5,
    );

    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) array(
      'entity_type' => $this->entity->getTargetEntityTypeId(),
      'bundle' => $this->entity->getTargetBundle(),
      'entity_id' => NULL
    );
    $form['#entity'] = _field_create_entity_from_ids($ids);
    $items = $form['#entity']->get($this->entity->getName());
    $item = $items->first() ?: $items->appendItem();

    // Add field settings for the field type and a container for third party
    // settings that modules can add to via hook_form_FORM_ID_alter().
    $form['settings'] = array(
      '#tree' => TRUE,
      '#weight' => 10,
    );
    $form['settings'] += $item->fieldSettingsForm($form, $form_state);
    $form['third_party_settings'] = array(
      '#tree' => TRUE,
      '#weight' => 11,
    );

    // Add handling for default value.
    if ($element = $items->defaultValuesForm($form, $form_state)) {
      $element = array_merge($element , array(
        '#type' => 'details',
        '#title' => $this->t('Default value'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#description' => $this->t('The default value for this field, used when creating new content.'),
      ));

      $form['default_value'] = $element;
    }
    */
    return $form;
  }

}
