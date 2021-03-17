<?php

namespace Drupal\wri_io_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of WRI office entities.
 *
 * @ingroup wri_io_content
 */
class WriOfficeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('WRI office ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\wri_io_content\Entity\WriOffice $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.wri_office.edit_form',
      ['wri_office' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
