<?php

namespace Drupal\wri_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\Image;

/**
 * Media source wrapping around an Embed file.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "embed_code",
 *   label = @Translation("Embed code"),
 *   description = @Translation("Add html to this media item to render embedded elements."),
 *   allowed_field_types = {"text_long"},
 *   default_thumbnail_filename = "generic.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\wri_media\Form\EmbedDataForm",
 *   },
 * )
 */
class EmbedText extends Image {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    // Empty out the name, forcing people to think of one.
    if ($name == 'default_name') {
      return '';
    }

    return parent::getMetadata($media, $name);
  }

}
