<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting imported items.
 */
class ClearMultipleForm extends ActionMultipleForm {

  const ACTION = 'feeds_feed_multiple_clear_confirm';

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->feedIds), 'Are you sure you want to delete all imported items of the selected feed?', 'Are you sure you want to delete all imported items of the selected feeds?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete items');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->feedIds)) {
      $count = 0;
      $inaccessible_feeds = [];
      foreach ($this->storage->loadMultiple($this->feedIds) as $feed) {
        if ($feed->access('clear')) {
          $count++;
          $feed->startBatchClear();
        }
        else {
          $inaccessible_feeds[] = $feed;
        }
      }

      $this->tempStoreFactory->get(static::ACTION)->delete($this->currentUser->id() . ':feeds_feed');
      $this->logger('feeds')->notice('Deleted imported items of @count feeds.', ['@count' => $count]);
      $this->messenger()->addMessage($this->formatPlural($count, 'Deleted items of 1 feed.', 'Deleted items of @count feeds.'));
    }

    if ($inaccessible_feeds) {
      $this->messenger->addWarning($this->getInaccessibleMessage(count($inaccessible_feeds)));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Returns the message to show the user when a feed has not been cleared.
   *
   * @param int $count
   *   Number of feeds that were not cleared.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The item inaccessible message.
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count feed has not been cleared because you do not have the necessary permissions.", "@count feeds have not been cleared because you do not have the necessary permissions.");
  }

}
