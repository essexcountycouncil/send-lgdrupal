<?php

namespace Drupal\localgov_directories;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\localgov_directories\Constants as Directory;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds views display for the directory channel.
 */
class DirectoryExtraFieldDisplay implements ContainerInjectionInterface, TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginBlockManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * DirectoryExtraFieldDisplay constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity Field Manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $plugin_manager_block
   *   Plugin Block Manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form Builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, BlockManagerInterface $plugin_manager_block, FormBuilderInterface $form_builder, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginBlockManager = $plugin_manager_block;
    $this->formBuilder = $form_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.block'),
      $container->get('form_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'removeExposedFilter',
    ];
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $fields = [];
    $fields['node']['localgov_directory']['display']['localgov_directory_view'] = [
      'label' => $this->t('Directory listing'),
      'description' => $this->t("Output from the embedded view for this channel."),
      'weight' => -20,
      'visible' => TRUE,
    ];
    $fields['node']['localgov_directory']['display']['localgov_directory_view_with_search'] = [
      'label' => $this->t('Directory listing (with search box)'),
      'description' => $this->t("Output from the embedded view for this channel. With search exposed filter. Use if not including the search block."),
      'weight' => -20,
      'visible' => FALSE,
    ];
    $fields['node']['localgov_directory']['display']['localgov_directory_facets'] = [
      'label' => $this->t('Directory facets'),
      'description' => $this->t("Output facets block, field alternative to enabling the block."),
      'weight' => -20,
      'visible' => TRUE,
    ];

    foreach ($this->directoryEntryTypes() as $type_id) {
      $fields['node'][$type_id]['display']['localgov_directory_search'] = [
        'label' => $this->t('Directory search'),
        'description' => $this->t("Free text search field for directories the entry is in."),
        'weight' => -20,
        'visible' => TRUE,
      ];
    }

    return $fields;
  }

  /**
   * Get all node bundles that are directory entry types.
   *
   * @return string[]
   *   Bundle IDs.
   */
  public function directoryEntryTypes() {
    $entry_types = [];

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $type_id => $type) {
      $fields = $this->entityFieldManager->getFieldDefinitions('node', $type_id);
      if (isset($fields[Directory::CHANNEL_SELECTION_FIELD])) {
        $entry_types[$type_id] = $type_id;
      }
    }

    return $entry_types;
  }

  /**
   * Adds view with arguments to view render array if required.
   *
   * @see localgov_directories_node_view()
   */
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
    if ($display->getComponent('localgov_directory_view')) {
      $build['localgov_directory_view'] = $this->getViewEmbed($node);
    }
    if ($display->getComponent('localgov_directory_view_with_search')) {
      $build['localgov_directory_view'] = $this->getViewEmbed($node, TRUE);
    }
    if ($display->getComponent('localgov_directory_facets')) {
      $build['localgov_directory_facets'] = $this->getFacetsBlock($node);
    }
    if ($display->getComponent('localgov_directory_search')) {
      $build['localgov_directory_search'] = $this->getSearchBlock($node);
    }
  }

  /**
   * Retrieves view, and sets render array.
   */
  protected function getViewEmbed(NodeInterface $node, $search_filter = FALSE) {
    $view = Views::getView(Directory::CHANNEL_VIEW);
    $views_display = self::determineChannelViewDisplay($node);
    if (!$view || !$view->access($views_display)) {
      return;
    }
    $render = [
      '#type' => 'view',
      '#name' => Directory::CHANNEL_VIEW,
      '#display_id' => $views_display,
      '#arguments' => [$node->id()],
    ];
    if (!$search_filter) {
      $render['#post_render'] = [
        [static::class, 'removeExposedFilter'],
      ];
    }
    return $render;
  }

  /**
   * Retrieves the facets block for a directory.
   */
  protected function getFacetsBlock(NodeInterface $node) {
    // The facet manager build needs the results of the query. Which might not
    // have been run by our nicely lazy loaded views render array.
    $view = Views::getView(Directory::CHANNEL_VIEW);
    $view->setArguments([$node->id()]);
    $views_display = self::determineChannelViewDisplay($node);
    $view->execute($views_display);

    if (!empty($view->result)) {
      $facet_id = self::determineFacetForChannel($node);
      $block = $this->pluginBlockManager->createInstance('facet_block' . PluginBase::DERIVATIVE_SEPARATOR . $facet_id);
      return $block->build();
    }
    else {
      return [];
    }
  }

  /**
   * Retrieves the search blocks from the view for directories.
   */
  protected function getSearchBlock(NodeInterface $node) {
    $forms = $form_list = [];
    foreach ($node->localgov_directory_channels as $delta => $channel) {
      $view = Views::getView(Directory::CHANNEL_VIEW);
      if ($view && ($channel_node = $channel->entity)) {
        $views_display = self::determineChannelViewDisplay($channel_node);
        $view->setDisplay($views_display);
        $view->setArguments([$channel_node->id()]);
        $view->initHandlers();
        $form_state = (new FormState())
          ->setStorage([
            'view' => $view,
            'display' => &$view->display_handler->display,
            'rerender' => NULL,
          ])
          ->setMethod('get')
          ->setAlwaysProcess()
          ->disableRedirect();
        $form = $this->formBuilder->buildForm('\Drupal\views\Form\ViewsExposedForm', $form_state);
        $form['#action'] = $channel_node->toUrl()->toString();
        $form['#attributes']['class'][] = $delta ? 'localgov-search-channel-secondary' : 'localgov-search-channel-primary';
        $channel_label = $this->entityRepository->getTranslationFromContext($channel_node)->label();
        $form['#id'] .= '--' . $channel_node->id();
        $form["#info"]["filter-search_api_fulltext"]["label"] = $this->t('Search <span class="localgov-search-channel" id="@id--channel">@channel</span>', [
          '@id' => $form['#id'],
          '@channel' => $channel_label,
        ]);
        // Can we do this with the form builder?
        // Do we need to deal with date-drupal-selector?
        // Questions for search_api_autocomplete?
        $form_list[$form['#id']] = $channel_label;
        $form['#attached']['library'][] = 'localgov_directories/localgov_directories_search';
        $forms[] = $form;
      }
      $forms['#attached']['drupalSettings']['localgovDirectories']['directoriesSearch'] = $form_list;
    }

    return $forms;
  }

  /**
   * Prepares variables for our bundle grouped facets item list template.
   *
   * Facet bundles are sorted based on their weight.
   *
   * @see templates/facets-item-list--links--localgov-directories-facets.tpl.php
   * @see localgov_directories_preprocess_facets_item_list()
   */
  public function preprocessFacetList(array &$variables) {
    $facet_storage = $this->entityTypeManager
      ->getStorage(Directory::FACET_CONFIG_ENTITY_ID);
    $group_items = [];
    foreach ($variables['items'] as $key => $item) {
      if ($entity = $facet_storage->load($item['value']['#attributes']['data-drupal-facet-item-value'])) {
        assert($entity instanceof LocalgovDirectoriesFacets);
        $group_items[$entity->bundle()]['items'][$key] = $item;
      }
    }

    // This is usually on a channel node. If so remove facets not active on
    // channel.
    $active_facets = NULL;
    if (($channel = $this->routeMatch->getParameter('node'))
      && $channel instanceof NodeInterface
      && $channel->bundle() == 'localgov_directory'
    ) {
      $active_facets = array_column($channel->localgov_directory_facets_enable->getValue(), 'target_id');
    }
    if (!is_null($active_facets)) {
      $group_items = array_intersect_key($group_items, array_flip($active_facets));
    }

    $type_storage = $this->entityTypeManager
      ->getStorage(Directory::FACET_TYPE_CONFIG_ENTITY_ID);
    foreach ($group_items as $bundle => $items) {
      $entity = $type_storage->load($bundle);
      assert($entity instanceof LocalgovDirectoriesFacetsType);
      $group_items[$bundle]['title'] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
      $group_items[$bundle]['weight'] = $entity->get('weight');
    }
    uasort($group_items, 'static::compareFacetBundlesByWeight');
    $variables['items'] = $group_items;
  }

  /**
   * Facet bundle comparison callback for sorting.
   *
   * Bundles are compared by their weights.  When weights are equal, labels take
   * over.
   *
   * @param array $bundle1
   *   Necessary keys: weight, title.
   * @param array $bundle2
   *   Same as $bundle1.
   */
  public static function compareFacetBundlesByWeight(array $bundle1, array $bundle2): int {

    if ($bundle1['weight'] === $bundle2['weight']) {
      return strnatcasecmp($bundle1['title'], $bundle2['title']);
    }

    return $bundle1['weight'] < $bundle2['weight'] ? -1 : 1;
  }

  /**
   * Finds the relevant Views display.
   *
   * Determines if the given directory channel needs the usual Views display or
   * a proximity search display.
   */
  public static function determineChannelViewDisplay(NodeInterface $channel_node): string {

    $has_proximity_search = $channel_node->hasField(Directory::PROXIMITY_SEARCH_CFG_FIELD) && !empty($channel_node->{Directory::PROXIMITY_SEARCH_CFG_FIELD}->value);
    $views_display = $has_proximity_search ? Directory::CHANNEL_VIEW_PROXIMITY_SEARCH_DISPLAY : Directory::CHANNEL_VIEW_DISPLAY;
    return $views_display;
  }

  /**
   * Finds the relevant Facet for a directory channel.
   *
   * Channels use different Views displays depending on whether proximity search
   * is in use or not.  The directory related Facets are attached to these Views
   * displays.  This means the choice of Facet differs depending on the use of
   * proximity search.
   */
  public static function determineFacetForChannel(NodeInterface $channel_node): string {

    $has_proximity_search = $channel_node->hasField(Directory::PROXIMITY_SEARCH_CFG_FIELD) && !empty($channel_node->{Directory::PROXIMITY_SEARCH_CFG_FIELD}->value);
    $facet_id = $has_proximity_search ? Directory::FACET_CONFIG_ENTITY_ID_FOR_PROXIMITY_SEARCH : Directory::FACET_CONFIG_ENTITY_ID;
    return $facet_id;
  }

  /**
   * Post render callback.
   *
   * @see ::getViewEmbed()
   */
  public static function removeExposedFilter(Markup $markup, array $render) {
    // Sure there must be a better way in the pre_render to stop it adding the
    // form, while accepting the parameters. But this does the same later.
    return $markup::create(preg_replace('|<form.*?class="[^"]*views-exposed-form.*?>.*?</form>|s', '', $markup, 1));
  }

}
