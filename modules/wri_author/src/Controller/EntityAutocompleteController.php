<?php

namespace Drupal\wri_author\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\wri_author\EntityAutocompleteMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Controller\EntityAutocompleteController as EntityAutocompleteControllerOrig;

/**
 * Adds custom autocomplete functionality.
 */
class EntityAutocompleteController extends EntityAutocompleteControllerOrig {

  /**
   * The autocomplete matcher for entity references.
   *
   * @var \Drupal\wri_author\EntityAutocompleteMatcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('wri_author.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
