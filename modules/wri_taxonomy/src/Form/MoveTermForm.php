<?php

namespace Drupal\wri_taxonomy\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to move terms from one vocabulary to another.
 */
class MoveTermForm extends FormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MoveTermForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'move_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    foreach ($vocabularies as $machine_name => $vocabulary) {
      $options[$machine_name] = $vocabulary->label();
    }

    $form['term_to_move'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#title' => $this->t('Term to move'),
      '#description' => $this->t('What term would you like to move to another vocabulary? You can enter multiple terms, separated by a comman.'),
      '#weight' => '0',
      '#tags' => TRUE,
    ];
    $form['move_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Move to'),
      '#description' => $this->t('Which vocabulary would you move to?'),
      '#options' => $options,
      '#size' => 1,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('term_to_move') as $term_target) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_target['target_id']);
      $term->set('vid', $form_state->getValue('move_to'));
      $term->save();
    }
  }

}
