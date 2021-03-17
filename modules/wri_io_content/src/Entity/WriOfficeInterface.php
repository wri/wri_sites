<?php

namespace Drupal\wri_io_content\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining WRI office entities.
 *
 * @ingroup wri_io_content
 */
interface WriOfficeInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the WRI office name.
   *
   * @return string
   *   Name of the WRI office.
   */
  public function getName();

  /**
   * Sets the WRI office name.
   *
   * @param string $name
   *   The WRI office name.
   *
   * @return \Drupal\wri_io_content\Entity\WriOfficeInterface
   *   The called WRI office entity.
   */
  public function setName($name);

  /**
   * Gets the WRI office creation timestamp.
   *
   * @return int
   *   Creation timestamp of the WRI office.
   */
  public function getCreatedTime();

  /**
   * Sets the WRI office creation timestamp.
   *
   * @param int $timestamp
   *   The WRI office creation timestamp.
   *
   * @return \Drupal\wri_io_content\Entity\WriOfficeInterface
   *   The called WRI office entity.
   */
  public function setCreatedTime($timestamp);

}
