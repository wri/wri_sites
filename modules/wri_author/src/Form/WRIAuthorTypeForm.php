<?php

namespace Drupal\wri_author\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for the Author entity bundle.
 */
class WRIAuthorTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wri_author_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wri_author_type->label(),
      '#description' => $this->t("Label for the Author type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wri_author_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wri_author\Entity\WRIAuthorType::load',
      ],
      '#disabled' => !$wri_author_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wri_author_type = $this->entity;
    $status = $wri_author_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Author type.', [
          '%label' => $wri_author_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Author type.', [
          '%label' => $wri_author_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($wri_author_type->toUrl('collection'));
  }

}
