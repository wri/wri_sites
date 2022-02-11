<?php

namespace Drupal\wri_author\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Author type entity.
 *
 * @ConfigEntityType(
 *   id = "wri_author_type",
 *   label = @Translation("Author type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wri_author\WRIAuthorTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wri_author\Form\WRIAuthorTypeForm",
 *       "edit" = "Drupal\wri_author\Form\WRIAuthorTypeForm",
 *       "delete" = "Drupal\wri_author\Form\WRIAuthorTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wri_author\WRIAuthorTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "wri_author_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "wri_author",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/wri_author_type/{wri_author_type}",
 *     "add-form" = "/admin/structure/wri_author_type/add",
 *     "edit-form" = "/admin/structure/wri_author_type/{wri_author_type}/edit",
 *     "delete-form" = "/admin/structure/wri_author_type/{wri_author_type}/delete",
 *     "collection" = "/admin/structure/wri_author_type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   }
 * )
 */
class WRIAuthorType extends ConfigEntityBundleBase implements WRIAuthorTypeInterface {

  /**
   * The Author type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Author type label.
   *
   * @var string
   */
  protected $label;

}
