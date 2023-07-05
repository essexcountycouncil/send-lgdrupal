<?php

/**
 * @file
 * Post update hooks for LocalGov Paragraphs Layout.
 */

/**
 * Update, or create, Layout Paragraphs settings if they aren't yet set.
 */
function localgov_paragraphs_layout_post_update_fix_missing_layout_paragraphs_setting() {
  $config = \Drupal::configFactory()->getEditable('layout_paragraphs.modal_settings');
  $settings = $config->getRawData();
  // Unlike config entities we're fine creating new config from getEditable,
  // so just checking if these settings aren't set for whatever reason is safe.
  if (empty($settings['width']) && empty($settings['height']) && empty($settings['autoresize'])) {
    $config->set('width', '90%');
    $config->set('height', 'auto');
    $config->set('autoresize', TRUE);
    $config->save();
  }
}
