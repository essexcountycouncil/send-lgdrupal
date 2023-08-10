<?php

namespace Drupal\localgov_news;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeForm;
use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * News views, and search, blocks.
 */
class NewsExtraFieldDisplay implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface|null
   */
  protected $moderationInformation;

  /**
   * EntityChildRelationshipUi constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block plugin manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface|null $moderation_information
   *   The moderation information service.
   */
  public function __construct(BlockManagerInterface $block_manager, ModerationInformationInterface $moderation_information = NULL) {
    $this->blockManager = $block_manager;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->has('content_moderation.moderation_information') ? $container->get('content_moderation.moderation_information') : NULL
    );
  }

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see localgov_news_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $fields = [];

    $fields['node']['localgov_newsroom']['display']['localgov_newsroom_all_view'] = [
      'label' => $this->t('All other news listing'),
      'description' => $this->t("Output facets the embedded view for all other news in newsroom."),
      'weight' => -20,
      'visible' => TRUE,
    ];
    $fields['node']['localgov_newsroom']['display']['localgov_news_search'] = [
      'label' => $this->t('News search'),
      'description' => $this->t("Free text search block for news."),
      'weight' => -20,
      'visible' => TRUE,
    ];
    $fields['node']['localgov_newsroom']['display']['localgov_news_facets'] = [
      'label' => $this->t('News facets'),
      'description' => $this->t("Output facets block, field alternative to enabling the block."),
      'weight' => -20,
      'visible' => TRUE,
    ];

    $fields['node']['localgov_news_article']['form']['localgov_news_newsroom_promote'] = [
      'label' => $this->t('Promote on newsroom'),
      'description' => $this->t("Add to directly to promoted news list in the newsroom."),
      'weight' => 1,
      'visible' => TRUE,
    ];
    return $fields;
  }

  /**
   * Adds view with arguments to view render array if required.
   *
   * @see localgov_news_node_view()
   */
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
    // Add view if enabled.
    if ($display->getComponent('localgov_newsroom_all_view')) {
      $build['localgov_newsroom_all_view'] = $this->getViewEmbed($node, 'all_news');
    }
    if ($display->getComponent('localgov_news_search')) {
      $build['localgov_news_search'] = $this->getSearchBlock();
    }
    if ($display->getComponent('localgov_news_facets')) {
      $build['localgov_news_facets'] = $this->getFacetsBlock();
    }
  }

  /**
   * Adds promote form field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see localgov_news_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $node_form = ($form_id == 'node_localgov_news_article_form' || $form_id == 'node_localgov_news_article_edit_form' ?? FALSE);
    if (
      ($node_form) &&
      ($form_display = $form_state->get('form_display')) &&
      ($form_display->getComponent('localgov_news_newsroom_promote'))
    ) {

      $form_object = $form_state->getFormObject();
      $node = $form_object->getEntity();
      if (empty($this->moderationInformation) || !$this->moderationInformation->isModeratedEntity($node)) {
        $visible = [
          ":input[name='status[value]']" => [
            'checked' => TRUE,
          ],
        ];
      }
      else {
        $workflow = $this->moderationInformation->getWorkflowForEntity($node);
        $type_plugin = $workflow->getTypePlugin();
        $transitions = $type_plugin->getTransitions();
        foreach ($transitions as $transition) {
          $state = $transition->to();
          if ($state->isPublishedState()) {
            $published[] = [":input[name='moderation_state[0][state]']" => ['value' => $state->id()]];
            $published[] = 'or';
          }
        }
        array_pop($published);
        $visible = [$published];
      }

      $form['localgov_news_newsroom_promote'] = [
        '#title' => $this->t('Promote on newsroom'),
        '#type' => 'checkbox',
        '#description' => $this->t("Add to promoted news in the newsroom. If there is already the maximum number of promoted news items the last will be removed to make space."),
        '#default_value' => self::articlePromotedStatus($form_object),
        '#states' => [
          'visible' => $visible,
        ],
      ];
      $form['actions']['submit']['#submit'][] = [self::class, 'articleSubmit'];
    }
  }

  /**
   * Submission handler node submit with promote extra field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see ::formAlter()
   */
  public static function articleSubmit(array $form, FormStateInterface $form_state) {
    if (
      $form_state->getValue('status') &&
      ($form_object = $form_state->getFormObject()) &&
      ($form_object instanceof NodeForm) &&
      ($article = $form_object->getEntity()) &&
      ($newsroom = $article->localgov_newsroom->entity)
    ) {
      $to_promote = $form_state->getValue('localgov_news_newsroom_promote');
      $is_promoted = self::articlePromotedStatus($form_object);
      if ($to_promote != $is_promoted) {
        if ($to_promote) {
          self::articleSetNewsroomPromote($newsroom, $article);
        }
        else {
          self::articleUnsetNewsroomPromote($newsroom, $article);
        }
      }
    }
  }

  /**
   * Check for NodeForm Entity article and if it is promoted in Newsroom.
   *
   * @param \Drupal\node\NodeForm $form_object
   *   Node form object.
   *
   * @return bool
   *   TRUE if there is an article and it is promoted on newsroom.
   */
  public static function articlePromotedStatus(NodeForm $form_object) {
    if (
      ($article = $form_object->getEntity()) &&
      ($newsroom = $article->localgov_newsroom->entity)
    ) {
      $featured_nids = array_column($newsroom->localgov_newsroom_featured->getValue(), 'target_id');
      return in_array($article->id(), $featured_nids);
    }

    return FALSE;
  }

  /**
   * Add article to promoted in newsroom.
   *
   * @param \Drupal\node\NodeInterface $newsroom
   *   Newsroom node.
   * @param \Drupal\node\NodeInterface $article
   *   Article node.
   */
  public static function articleSetNewsroomPromote(NodeInterface $newsroom, NodeInterface $article) {
    $references = $newsroom->localgov_newsroom_featured->getValue();
    array_unshift($references, ['target_id' => $article->id()]);
    $newsroom->localgov_newsroom_featured->setValue($references);
    $newsroom->save();
  }

  /**
   * Remove article from promoted in newsroom.
   *
   * @param \Drupal\node\NodeInterface $newsroom
   *   Newsroom node.
   * @param \Drupal\node\NodeInterface $article
   *   Article node.
   */
  public static function articleUnsetNewsroomPromote(NodeInterface $newsroom, NodeInterface $article) {
    $references = $newsroom->localgov_newsroom_featured->getValue();
    $position = array_search(['target_id' => $article->id()], $references);
    $newsroom->localgov_newsroom_featured->removeItem($position);
    $newsroom->save();
  }

  /**
   * Retrieves view, and sets render array.
   */
  protected function getViewEmbed(NodeInterface $node, string $display_id) {
    $view = Views::getView('localgov_news_list');
    if (!$view || !$view->access($display_id)) {
      return;
    }
    return [
      '#type' => 'view',
      '#name' => 'localgov_news_list',
      '#display_id' => $display_id,
      '#arguments' => [$node->id()],
      '#attached' => [
        'library' => ['localgov_news/localgov-newsroom'],
      ],
    ];
  }

  /**
   * Retrieves the news search block.
   *
   * This presently is a sitewide news search.
   */
  protected function getSearchBlock() {
    $block = $this->blockManager->createInstance('views_exposed_filter_block:localgov_news_search-page_search_news');
    return $block->build();
  }

  /**
   * Retrieves the news facets blocks.
   */
  protected function getFacetsBlock() {
    $blocks = [];

    $block = $this->blockManager->createInstance('facet_block' . PluginBase::DERIVATIVE_SEPARATOR . 'localgov_news_category');
    if ($block) {
      $blocks[] = $block->build();
    }
    $block = $this->blockManager->createInstance('facet_block' . PluginBase::DERIVATIVE_SEPARATOR . 'localgov_news_date');
    if ($block) {
      $blocks[] = $block->build();
    }

    return $blocks;
  }

}
