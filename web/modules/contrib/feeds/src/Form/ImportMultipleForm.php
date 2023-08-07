<?php

namespace Drupal\feeds\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a feed import confirmation form.
 */
class ImportMultipleForm extends ActionMultipleForm {

  const ACTION = 'feeds_feed_multiple_import_confirm';

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->feedIds), 'Are you sure you want to import the selected feed?', 'Are you sure you want to import the selected feeds?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Import');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->feedIds)) {
      $count = 0;
      $inaccessible_feeds = [];
      foreach ($this->storage->loadMultiple($this->feedIds) as $feed) {
        if ($feed->access('import')) {
          $count++;
          $feed->startBatchImport();
        }
        else {
          $inaccessible_feeds[] = $feed;
        }
      }

      $this->tempStoreFactory->get(static::ACTION)->delete($this->currentUser->id() . ':feeds_feed');
      $this->logger('feeds')->notice('Imported @count feeds.', ['@count' => $count]);
      $this->messenger()->addMessage($this->formatPlural($count, 'Imported 1 feed.', 'Imported @count feeds.'));
    }

    if ($inaccessible_feeds) {
      $this->messenger->addWarning($this->getInaccessibleMessage(count($inaccessible_feeds)));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Returns the message to show the user when a feed has not been imported.
   *
   * @param int $count
   *   Number of feeds that were not imported.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The item inaccessible message.
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count feed has not been imported because you do not have the necessary permissions.", "@count feeds have not been imported because you do not have the necessary permissions.");
  }

}
