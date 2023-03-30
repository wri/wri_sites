<?php

namespace Drupal\wri_media\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;

/**
 * Creates a form to create media entities from html snippets.
 *
 * @internal
 *   Form classes are internal.
 */
class EmbedDataForm extends AddFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_embed_data';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add some code'),
      '#description' => $this->t('All values will be treated as pure html.'),
      '#required' => TRUE,
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('code')], $form, $form_state);
  }

}
