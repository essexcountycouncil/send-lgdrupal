<?php

namespace Drupal\localgov_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;
use Drupal\views\Form\ViewsExposedForm;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SitewideSearchBlock' block.
 *
 * @Block(
 *  id = "localgov_sitewide_search_block",
 *  admin_label = @Translation("Sitewide search block"),
 * )
 */
class SitewideSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * ID of the view to get the exposed form from.
   *
   * @var string
   */
  protected $viewId = 'localgov_sitewide_search';

  /**
   * ID of the display on the view to get the exposed form from.
   *
   * @var string
   */
  protected $displayId = 'sitewide_search_page';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * Sitewide search block constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = [];

    $index = Index::load('localgov_sitewide_search');
    if (!$index->status()) {
      $form[] = [
        '#markup' => $this->t(
          'The sitewide search index requires a search backend. <a href=":modules_page">Enabling the Sitewide Search Database module</a> will provide one.',
          [
            ':modules_page' => Url::fromRoute('system.modules_list', [], ['fragment' => 'module-localgov-search-db'])->toString(),
          ]
        ),
      ];
    }
    else {
      // Add sitewide search view filters to block.
      // Adapted from: https://blog.werk21.de/en/2017/03/08/programmatically-render-exposed-filter-form
      $view = Views::getView($this->viewId);

      if ($view) {
        $view->setDisplay($this->displayId);
        $view->initHandlers();
        $form_state = (new FormState())->setStorage([
          'view' => $view,
          'display' => &$view->display_handler->display,
          'rerender' => TRUE,
        ])
          ->setMethod('get')
          ->setAlwaysProcess()
          ->disableRedirect();
        $form_state->set('rerender', NULL);
        $form = $this->formBuilder->buildForm(ViewsExposedForm::class, $form_state);
        $form['#id'] .= '-block';
        $form['s']['#attributes']['placeholder'] = 'Search';
        $form['s']['#required'] = TRUE;
        $form['#cache']['contexts'] = ['url.query_args:s'];
      }
    }

    return $form;
  }

}
