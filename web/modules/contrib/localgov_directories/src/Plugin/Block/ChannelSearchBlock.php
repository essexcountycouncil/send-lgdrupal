<?php

declare(strict_types = 1);

namespace Drupal\localgov_directories\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\localgov_directories\Constants as Directory;
use Drupal\localgov_directories\DirectoryExtraFieldDisplay;
use Drupal\node\NodeInterface;
use Drupal\views\Form\ViewsExposedForm;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Views exposed filter block.
 *
 * This Views exposed filter comes from the **localgov_directory_channel**
 * view's **node_embed** display.
 *
 * @Block(
 *   id = "localgov_directories_channel_search_block",
 *   admin_label = @Translation("Directory channel search block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 *
 * @todo Functional test for cache tag invalidation scenario.
 */
class ChannelSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $channel_view = Views::getView(Directory::CHANNEL_VIEW);
    if (empty($channel_view)) {
      return [];
    }

    $host_node = $this->getContextValue('node');
    if (empty($host_node)) {
      return [];
    }

    $is_directory_entry   = $this->isDirEntry($host_node);
    $is_directory_channel = ($host_node->bundle() === Directory::CHANNEL_NODE_BUNDLE);

    if ($is_directory_entry) {
      $build = $this->getDirEntryForm($host_node);
    }
    elseif ($is_directory_channel) {
      $build = $this->prepareDirChannelForm($host_node, $channel_view);
    }
    else {
      $build = [];
    }

    return $build;
  }

  /**
   * Views exposed filter form.
   *
   * This one is for Directory entry pages.
   *
   * This returns a form collection as prepared by
   * Drupal\localgov_directories\DirectoryExtraFieldDisplay::getSearchBlock().
   */
  public function getDirEntryForm(NodeInterface $directory_entry): array {

    $extra_field_wrapper_obj = new class($this->entityRepository, $this->formBuilder) extends DirectoryExtraFieldDisplay {

      /**
       * Wrap protected method with a public one.
       *
       * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
       */
      public function getSearchBlock(NodeInterface $node) {
        // phpcs:enable Generic.CodeAnalysis.UselessOverridingMethod.Found
        return parent::getSearchBlock($node);
      }

      /**
       * Only initialize services used by parent::getSearchBlock().
       */
      public function __construct($entity_repo, $form_builder) {
        $this->entityRepository = $entity_repo;
        $this->formBuilder = $form_builder;
      }

    };

    $build = $extra_field_wrapper_obj->getSearchBlock($directory_entry);
    return $build;
  }

  /**
   * Views exposed filter form.
   *
   * This form is for Directory channel pages.
   */
  public function prepareDirChannelForm(NodeInterface $channel_node, ViewExecutable $channel_view): array {

    $views_display = DirectoryExtraFieldDisplay::determineChannelViewDisplay($channel_node);
    $channel_view->setDisplay($views_display);
    $channel_view->initHandlers();

    $form_state = (new FormState())
      ->setStorage([
        'view' => $channel_view,
        'display' => &$channel_view->display_handler->display,
        'rerender' => NULL,
        'parent-source' => self::class,
      ])
      ->setMethod('get')
      ->setAlwaysProcess()
      ->disableRedirect();

    $views_exposed_filter_form = $this->formBuilder->buildForm(ViewsExposedForm::class, $form_state);
    $views_exposed_filter_form['#id'] .= '-in-a-search-block';
    return $views_exposed_filter_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {

    $host_node = $this->getContextValue('node');
    $channel_view = Views::getView(Directory::CHANNEL_VIEW);

    $cache_tags = Cache::mergeTags(parent::getCacheTags(), $host_node->getCacheTags());
    $cache_tags = Cache::mergeTags($cache_tags, $channel_view->getCacheTags());

    if ($this->isDirEntry($host_node)) {
      $cache_tags_for_dir_channels = $this->getCacheTagsForDirChannels($host_node);
      $cache_tags = Cache::mergeTags($cache_tags, $cache_tags_for_dir_channels);
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    $channel_view = Views::getView(Directory::CHANNEL_VIEW);
    $channel_view->setDisplay(Directory::CHANNEL_VIEW_DISPLAY);
    $contexts = $channel_view->display_handler->getCacheMetadata()->getCacheContexts();
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * Is this node a Directory entry?
   */
  protected function isDirEntry(NodeInterface $node): bool {

    return $node->hasField(Directory::CHANNEL_SELECTION_FIELD);
  }

  /**
   * Cache tags for Directory channel nodes.
   *
   * The given Directory entry belongs to these Directory channel nodes.
   */
  protected function getCacheTagsForDirChannels(NodeInterface $directory_entry): array {

    $directory_channels = array_filter(array_map(function (EntityReferenceItem $ref_item) {
      return $ref_item->entity;
    }, iterator_to_array($directory_entry->{Directory::CHANNEL_SELECTION_FIELD})));

    $cache_tags = array_reduce(
      $directory_channels,
      function (array $carry, NodeInterface $directory_channel): array {
        return Cache::mergeTags($carry, $directory_channel->getCacheTags());
      }, []
    );

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_repo, $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repo;
    $this->formBuilder = $form_builder;
  }

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

}
