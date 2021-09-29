<?php

/**
 * @file
 * Test to ensure theme compatibility with managed files.
 */

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function ts_wrin_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  $form["logo"]["settings"]["logo_upload"]["#upload_validators"] = [
    'file_validate_extensions' => ['jpg jpeg gif png svg'],
  ];
  $form['white_logo'] = [
    '#type' => 'details',
    '#title' => t('White Logo image'),
    '#open' => TRUE,
  ];
  $form['white_logo']['settings'] = [
    '#type' => 'container',
    '#states' => [
        // Hide the logo settings when using the default logo.
      'invisible' => [
        'input[name="white_default_logo"]' => ['checked' => TRUE],
      ],
    ],
  ];
  $form['white_logo']['settings']['white_logo_path'] = [
    '#type' => 'textfield',
    '#title' => t('Path to custom logo'),
    '#default_value' => theme_get_setting('white_logo.path'),
  ];
  $form['white_logo']['settings']['white_logo_upload'] = [
    '#type' => 'file',
    '#title' => t('Upload logo image'),
    '#maxlength' => 40,
    '#description' => t("If you don't have direct file access to the server, use this field to upload your logo."),
    '#upload_validators' => [
      'file_validate_extensions' => ['jpg jpeg gif png svg'],
    ],
  ];
  $form['#validate'][] = 'ts_wrin_settings_form_system_theme_settings_validate';
  $form['#submit'][] = 'ts_wrin_settings_form_system_theme_settings_submit';
}

/**
 * Test theme form settings submission handler.
 */
function ts_wrin_settings_form_system_theme_settings_validate(&$form, FormStateInterface $form_state) {

  // Check for a new uploaded logo.
  if (isset($form['white_logo'])) {
    $file = _file_save_upload_from_form($form['white_logo']['settings']['white_logo_upload'], $form_state, 0);
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state->setValue('white_logo_upload', $file);
    }
  }

  // If the user provided a path for a logo or favicon file, make sure a file
  // exists at that path.
  if ($form_state->getValue('white_logo_path')) {
    $values['white_logo_path'] = ts_wrin_settings_form_validate_path($form_state->getValue('white_logo_path'));
    if (!$values['white_logo_path']) {
      $form_state->setErrorByName('white_logo_path', t('The custom logo path is invalid.'));
    }
  }
}

/**
 * Test theme form settings submission handler.
 */
function ts_wrin_settings_form_system_theme_settings_submit(&$form, FormStateInterface $form_state) {
  $values = $form_state->getValues();
  $config = \Drupal::service('config.factory')->getEditable('ts_wrin.settings');
  $file_system = \Drupal::service('file_system');
  $default_scheme = 'public';
  try {
    if (!empty($values['white_logo_upload'])) {
      $filename = $file_system->copy($values['white_logo_upload']->getFileUri(), $default_scheme . '://');
      $values['white_logo_path'] = $filename;
    }
  }
  catch (FileException $e) {
    // Ignore.
  }
  $form_state->unsetValue('white_logo_upload');
  // If the user entered a path relative to the system files directory for
  // a logo or favicon, store a public:// URI so the theme system can handle it.
  if (!empty($values['white_logo_path'])) {
    $values['white_logo_path'] = ts_wrin_settings_form_validate_path($values['white_logo_path']);
  }

  if ($values['white_logo_path']) {
    $config->set('white_logo.path', $values['white_logo_path']);
  }
  $form_state->unsetValue('white_logo_path');
}

/**
 * Validate the path.
 *
 * @see \Drupal\system\Form\ThemeSettingsForm::validatePath()
 */
function ts_wrin_settings_form_validate_path($path) {
  $file_system = \Drupal::service('file_system');
  // Absolute local file paths are invalid.
  if ($file_system->realpath($path) == $path) {
    return FALSE;
  }
  // A path relative to the Drupal root or a fully qualified URI is valid.
  if (is_file($path)) {
    return $path;
  }
  // Prepend 'public://' for relative file paths within public filesystem.
  if (StreamWrapperManager::getScheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}
