<?php

namespace Drupal\wri_author\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Author entity.
 *
 * @ingroup wri_author
 *
 * @ContentEntityType(
 *   id = "wri_author",
 *   label = @Translation("Author"),
 *   bundle_label = @Translation("Author type"),
 *   handlers = {
 *     "storage" = "Drupal\wri_author\WRIAuthorStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wri_author\WRIAuthorListBuilder",
 *     "views_data" = "Drupal\wri_author\Entity\WRIAuthorViewsData",
 *     "translation" = "Drupal\wri_author\WRIAuthorTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\wri_author\Form\WRIAuthorForm",
 *       "add" = "Drupal\wri_author\Form\WRIAuthorForm",
 *       "edit" = "Drupal\wri_author\Form\WRIAuthorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wri_author\WRIAuthorHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\wri_author\WRIAuthorAccessControlHandler",
 *   },
 *   base_table = "wri_author",
 *   data_table = "wri_author_field_data",
 *   revision_table = "wri_author_revision",
 *   revision_data_table = "wri_author_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer author entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/wri_author/{wri_author}",
 *     "add-page" = "/admin/content/wri_author/add",
 *     "add-form" = "/admin/content/wri_author/add/{wri_author_type}",
 *     "edit-form" = "/admin/content/wri_author/{wri_author}/edit",
 *     "delete-form" = "/admin/content/wri_author/{wri_author}/delete",
 *     "version-history" = "/admin/content/wri_author/{wri_author}/revisions",
 *     "revision" = "/admin/content/wri_author/{wri_author}/revisions/{wri_author_revision}/view",
 *     "revision_revert" = "/admin/content/wri_author/{wri_author}/revisions/{wri_author_revision}/revert",
 *     "revision_delete" = "/admin/content/wri_author/{wri_author}/revisions/{wri_author_revision}/delete",
 *     "translation_revert" = "/admin/content/wri_author/{wri_author}/revisions/{wri_author_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/wri_author",
 *   },
 *   bundle_entity_type = "wri_author_type",
 *   field_ui_base_route = "entity.wri_author_type.edit_form"
 * )
 */
class WRIAuthor extends EditorialContentEntityBase implements WRIAuthorInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Author entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Author is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
