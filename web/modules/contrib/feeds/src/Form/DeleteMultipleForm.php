<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a feed deletion confirmation form.
 */
class DeleteMultipleForm extends ActionMultipleForm {

  const ACTION = 'feeds_feed_multiple_delete_confirm';

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->feedIds), 'Are you sure you want to delete this feed?', 'Are you sure you want to delete these feeds?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->feedIds)) {
      $feeds = $this->storage->loadMultiple($this->feedIds);
      $feeds_to_delete = [];
      $inaccessible_feeds = [];
      foreach ($this->storage->loadMultiple($this->feedIds) as $feed) {
        if ($feed->access('delete')) {
          $feeds_to_delete[] = $feed;
        }
        else {
          $inaccessible_feeds[] = $feed;
        }
      }
      $this->storage->delete($feeds_to_delete);
      $this->tempStoreFactory->get(static::ACTION)->delete($this->currentUser->id() . ':feeds_feed');
      $count = count($feeds_to_delete);
      $this->logger('feeds')->notice('Deleted @count feeds.', ['@count' => $count]);
      $this->messenger()->addMessage($this->formatPlural($count, 'Deleted 1 feed.', 'Deleted @count feeds.'));
    }

    if ($inaccessible_feeds) {
      $this->messenger->addWarning($this->getInaccessibleMessage(count($inaccessible_feeds)));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Returns the message to show the user when a feed has not been deleted.
   *
   * @param int $count
   *   Number of feeds that were not deleted.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The item inaccessible message.
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count feed has not been deleted because you do not have the necessary permissions.", "@count feeds have not been deleted because you do not have the necessary permissions.");
  }

}
