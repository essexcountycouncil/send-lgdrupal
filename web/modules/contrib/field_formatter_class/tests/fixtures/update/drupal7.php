<?php

/**
 * @file
 * Contains SQL necessary to add field formatter class to third party settings.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

/**
 * Updates field formatter data.
 */
$update_data = [
  [
    "field_name" => "field_tags",
    "entity_type" => "node",
    "bundle" => "article",
    "class" => "classtest1",
  ],
  [
    "field_name" => "field_image",
    "entity_type" => "node",
    "bundle" => "article",
    "class" => "classtest1 classtest2",
  ],
  [
    "field_name" => "comment_body",
    "entity_type" => "comment",
    "bundle" => "comment_node_book",
    "class" => "class test",
  ],
];

foreach ($update_data as $update) {
  $config = $connection->select('field_config_instance', 'fci')
    ->fields('fci')
    ->condition('field_name', $update['field_name'])
    ->condition('entity_type', $update['entity_type'])
    ->condition('bundle', $update['bundle'])
    ->execute()
    ->fetchAssoc();

  $data = unserialize($config['data']);

  $data['display']['default']['settings']["field_formatter_class"] = $update['class'];

  $connection->update('field_config_instance')
    ->fields(['data' => serialize($data)])
    ->condition('field_name', $update['field_name'])
    ->condition('entity_type', $update['entity_type'])
    ->condition('bundle', $update['bundle'])
    ->execute();
}

/**
 * Enable field_formatter_class module.
 */
$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'sites/all/modules/contrib/field_formatter_class/field_formatter_class.module',
    'name' => 'field_formatter_class',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '0',
    'weight' => '0',
    'info' => 'a:10:{s:4:"name";s:21:"Field Formatter Class";s:11:"description";s:48:"Allows custom HTML classes for field formatters.";s:4:"core";s:3:"7.x";s:7:"package";s:6:"Fields";s:12:"dependencies";a:2:{i:0;s:5:"field";i:1;s:24:"field_formatter_settings";}s:5:"mtime";i:1617867192;s:7:"version";N;s:3:"php";s:5:"5.2.4";s:5:"files";a:0:{}s:9:"bootstrap";i:0;}',
  ])
  ->execute();
