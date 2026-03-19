<?php

declare(strict_types=1);

namespace Drupal\wri_spoke\Plugin\Action;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Move term to original vocabulary action.
 *
 * @Action(
 *   id = "wri_spoke_move_term_to_original_vocabulary",
 *   label = @Translation("Move term to original vocabulary"),
 *   type = "taxonomy_term",
 *   category = @Translation("Custom"),
 * )
 *
 * @DCG
 * For updating entity fields consider extending FieldUpdateActionBase.
 * @see \Drupal\Core\Field\FieldUpdateActionBase
 *
 * @DCG
 * In order to set up the action through admin interface the plugin has to be
 * configurable.
 * @see https://www.drupal.org/project/drupal/issues/2815301
 * @see https://www.drupal.org/project/drupal/issues/2815297
 *
 * @DCG
 * The whole action API is subject of change.
 * @see https://www.drupal.org/project/drupal/issues/2011038
 */
final class MoveTermToOriginalVocabulary extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($entity, AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface|bool {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $access = $entity->access('update', $account, TRUE)
      ->andIf($entity->get('field_original_vocabulary')->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL): void {
    if ($entity->hasField('field_original_vocabulary') && !$entity->field_original_vocabulary->isEmpty()) {
      // Make sure the value of $entity->field_original_vocabulary is a valid vocabulary.
      $vocabulary_id = $entity->field_original_vocabulary->value;
      $vocabulary_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
      $vocabulary = $vocabulary_storage->load($vocabulary_id);
      if ($vocabulary) {
        $entity->set('vid', $vocabulary_id);
        $entity->save();
      }
    }
  }

}
