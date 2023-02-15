<?php

declare(strict_types = 1);

namespace Drupal\localgov_page_components\Plugin\Linkit\Matcher;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Linkit matcher plugin for the paragraphs_library_item entity type.
 *
 * The EntityMatcher class enables us to filter suggested entites based on
 * the related entity type's bundles.  This plugin provides suggestions for
 * Paragraphs library item entities.  But that entity type has no bundle,
 * instead it refers to various bundles of the Paragraph entity type.  We want
 * to filter the suggestions based on those Paragraph bundle names.  To achieve
 * this, we pretend that we are working on the Paragraph entity.
 *
 * ## Example
 * We have four Paragraph bundles available: A, B, C, and D.
 * We want to filter the suggestions based on A and B bundles only.
 * The search for a string has brought up four Paragraphs library item entities:
 * - entity1 (refers to Paragraph bundle A).
 * - entity2 (refers to Paragraph bundle B).
 * - entity3 (refers to Paragraph bundle C).
 * - entity4 (refers to Paragraph bundle A).
 * This plugin will filter the above suggestions and keep entity1, entity2, and
 * entity4 only as they *refer* to Paragraph entities of bundle A or B.
 *
 * @Matcher(
 *   id = "entity:paragraphs_library_item",
 *   label = @Translation("Page components"),
 *   target_entity = "paragraphs_library_item",
 *   provider = "paragraphs_library"
 * )
 */
class PageComponentMatcher extends EntityMatcher {

  const PARAGRAPH_ENTITY_TYPE = 'paragraph';

  /**
   * {@inheritdoc}
   *
   * Pretend that we are dealing with Paragraph entity type to include the
   * Paragraph bundle information in the summary.
   */
  public function getSummary() {

    $origTargetType = $this->targetType;
    $this->targetType = self::PARAGRAPH_ENTITY_TYPE;
    $summary = parent::getSummary();
    $this->targetType = $origTargetType;

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * Pretend that we are dealing with Paragraph entity type to include bundle
   * configuration fields in the config form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    // Prepare the form again. This time pretend that we are working with the
    // Paragraph entity type rather than the Paragraphs library item.  This
    // will include the Paragraph bundles in the form's bundle config fields.
    $origTargetType = $this->targetType;
    $this->targetType = self::PARAGRAPH_ENTITY_TYPE;
    $form_for_paragraph = parent::buildConfigurationForm($form, $form_state);
    $this->targetType = $origTargetType;

    // When it comes to bundle configration, use Paragraph bundles.
    $form['bundle_restrictions'] = $form_for_paragraph['bundle_restrictions'];
    $form['bundle_grouping'] = $form_for_paragraph['bundle_grouping'];
    $form['bundle_restrictions']['bundles']['#title'] = $this->t('Restrict suggestions to the selected Paragraph bundles who form Page components');

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Create group names based on Paragraph bundle names.
   */
  protected function buildGroup(EntityInterface $entity) {

    $group = $entity->getEntityType()->getLabel();

    if ($this->configuration['group_by_bundle']) {
      $paragraph_bundle = $entity->paragraphs->entity->bundle();
      $group .= ' - ' . $paragraph_bundle;
    }

    return $group;
  }

  /**
   * {@inheritdoc}
   *
   * Very much based on EntityMatcher::execute().  The only difference is, when
   * filtering the suggestions, we are using Paragraph bundles instead of
   * Paragraphs library item bundles.
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();
    $query = $this->buildEntityQuery($string);

    // Paragraphs library item entity has no bundle.  So avoid adding bundle
    // filtering in the entity query.  Note that the selected bundle names in
    // the plugin config all belong to the Paragraph entity type and *not* the
    // Paragraphs library item entity type.
    $orig_bundle_config = $this->configuration['bundles'];
    $this->configuration['bundles'] = [];
    $query_result = $query->execute();
    $this->configuration['bundles'] = $orig_bundle_config;

    $url_results = $this->findEntityIdByUrl($string);
    $result = array_merge($query_result, $url_results);

    // If no results, return an empty suggestion collection.
    if (empty($result)) {
      return $suggestions;
    }

    $entities = $this->entityTypeManager->getStorage($this->targetType)->loadMultiple($result);

    // Filter based on preconfigured Paragraph bundles.
    $has_bundle_filter = !empty($this->configuration['bundles']);
    if ($has_bundle_filter) {
      $entities = array_filter($entities, [$this, 'isTargetParagraphBundle']);
    }

    foreach ($entities as $entity) {
      // Check the access against the defined entity access handler.
      /** @var \Drupal\Core\Access\AccessResultInterface $access */
      $access = $entity->access('view', $this->currentUser, TRUE);

      if (!$access->isAllowed()) {
        continue;
      }

      $entity = $this->entityRepository->getTranslationFromContext($entity);
      $suggestion = $this->createSuggestion($entity);
      $suggestions->addSuggestion($suggestion);
    }

    return $suggestions;
  }

  /**
   * Bundle filter.
   *
   * Does the given Paragraph library item entity refer to a Paragraph bundle
   * which belongs to one of our preconfigured Paragraph bundle list?
   */
  protected function isTargetParagraphBundle(EntityInterface $paragraphs_library_item): bool {

    $paragraph_bundle = isset($paragraphs_library_item->paragraphs->entity) ? $paragraphs_library_item->paragraphs->entity->bundle() : '';
    $has_no_paragraph_bundle = empty($paragraph_bundle);
    if ($has_no_paragraph_bundle) {
      return FALSE;
    }

    $is_a_target_paragraph_bundle = in_array($paragraph_bundle, $this->configuration['bundles']);
    return $is_a_target_paragraph_bundle;
  }

}
