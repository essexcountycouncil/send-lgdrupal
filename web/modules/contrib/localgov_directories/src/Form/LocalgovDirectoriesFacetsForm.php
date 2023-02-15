<?php

namespace Drupal\localgov_directories\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the directory facets entity edit forms.
 */
class LocalgovDirectoriesFacetsForm extends ContentEntityForm {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);

    $form->renderer = $container->get('renderer');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $this->renderer->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New directory facets %label has been created.', $message_arguments));
      $this->logger('localgov_directories')->notice('Created new directory facets %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The directory facets %label has been updated.', $message_arguments));
      $this->logger('localgov_directories')->notice('Updated new directory facets %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.localgov_directories_facets.collection');
  }

}
