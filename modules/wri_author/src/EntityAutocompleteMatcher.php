<?php

namespace Drupal\wri_author;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityAutocompleteMatcher as EntityAutocompleteMatcherOrig;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Extends the Entity Reference autocomplete to add bundle and published status.
 */
class EntityAutocompleteMatcher extends EntityAutocompleteMatcherOrig {

  /**
   * Constructs a EntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Entity\EntityRepository $entity_repository
   *   The Drupal entity.manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity_type.manager service.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, EntityRepository $entity_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->selectionManager = $selection_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {

    $matches = [];

    $options = [
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {

          $entity = $this->entityTypeManager->getStorage($target_type)->load($entity_id);
          $entity = $this->entityRepository->getTranslationFromContext($entity);

          $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
          $status = '';
          if ($entity->getEntityType()->id() == 'node') {
            $status = ($entity->isPublished()) ? ", Published" : ", Unpublished";
          }

          $key = $label . ' (' . $entity_id . ')';
          // Strip things like white spaces, line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $label = $label . ' (' . $entity_id . ') [' . $type . $status . ']';
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
