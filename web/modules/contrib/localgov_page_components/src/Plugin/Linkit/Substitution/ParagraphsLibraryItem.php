<?php

declare(strict_types = 1);

namespace Drupal\localgov_page_components\Plugin\Linkit\Substitution;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\linkit\SubstitutionInterface;

/**
 * Linkit Substitution plugin for the Paragraphs library item entity.
 *
 * The "Canonical" substitution plugin returns the URL of the entity mentioned
 * in a Drupal field.  For Paragraphs library item entities, this results in
 * URLs like /admin/content/paragraphs/N which is of not much use.  These
 * Paragraphs library item entities always refer to a Paragraph entity.  This
 * Paragraph entity may have a field containing a URL value.  That URL is what
 * we are after.
 *
 * The Paragraph field type and field name containing the URL is different from
 * bundle to bundle.  So we have to process them individually.  Ideally this
 * field information will come from a configuration form, but Linkit's
 * Substitution plugin doesn't seem to support such forms.  So had to hardcode
 * the Paragraph bundle and field names :(
 *
 * For now, URL is extracted from the following Paragraph bundle and fields:
 * - Contact:localgov_contact_url
 * - Link:localgov_url
 * Paragraph library items referring to other Paragraph bundles get their own
 * entity URL.
 *
 * @Substitution(
 *   id = "paragraphs_library_item_localgovdrupal",
 *   label = @Translation("Page components"),
 * )
 */
class ParagraphsLibraryItem extends PluginBase implements SubstitutionInterface {

  const TARGET_ENTITY_TYPE = 'paragraphs_library_item';

  /**
   * Paragraph bundle names and their URL fields.
   *
   * @var array
   */
  const PARAGRAPH_TO_URL_FIELD_MAPPING = [
    'localgov_contact' => 'localgov_contact_url',
    'localgov_link' => 'localgov_url',
  ];

  /**
   * {@inheritdoc}
   *
   * We know how to get URLs out of string and link type fields.  For other
   * field types, we just return the URL of the given entity.
   */
  public function getUrl(EntityInterface $paragraphs_library_item) {

    $uri        = NULL;
    $empty_uri  = (new GeneratedUrl)->setGeneratedUrl('');
    $entity_uri = $paragraphs_library_item->toUrl('canonical')->toString(TRUE);

    $paragraph_bundle = $paragraphs_library_item->paragraphs->entity->getType();
    $is_unknown_bundle = !array_key_exists($paragraph_bundle, self::PARAGRAPH_TO_URL_FIELD_MAPPING);
    if ($is_unknown_bundle) {
      return $entity_uri;
    }

    $fieldname = self::PARAGRAPH_TO_URL_FIELD_MAPPING[$paragraph_bundle];
    $fieldtype = $paragraphs_library_item->paragraphs->entity->{$fieldname}->getFieldDefinition()->getType();
    $has_field_no_value = ($paragraphs_library_item->paragraphs->entity->{$fieldname}->count() === 0);

    if ($has_field_no_value) {
      $uri = $empty_uri;
    }
    elseif ($fieldtype === 'link') {
      $uri = $paragraphs_library_item->paragraphs->entity->{$fieldname}[0]->getUrl()->toString(TRUE);
    }
    elseif ($fieldtype === 'string') {
      $uri = (new GeneratedUrl)->setGeneratedUrl($paragraphs_library_item->paragraphs->entity->{$fieldname}->value);
    }
    else {
      $uri = $entity_uri;
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {

    $entity_type_name = $entity_type->id();
    $is_paragraphs_library_item = ($entity_type_name === self::TARGET_ENTITY_TYPE);

    return $is_paragraphs_library_item;
  }

}
