<?php

namespace Drupal\textfield_counter\Plugin\Field\FieldWidget;

use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;

/**
 * Plugin implementation of the 'text_textarea_with_counter' widget.
 *
 * @FieldWidget(
 *   id = "text_textarea_with_counter",
 *   label = @Translation("Deprecated Textarea (multiple rows) with counter"),
 *   field_types = {
 *     "text_long"
 *   }
 * )
 */
class TextareaWithCounterWidget extends TextareaWidget {}
