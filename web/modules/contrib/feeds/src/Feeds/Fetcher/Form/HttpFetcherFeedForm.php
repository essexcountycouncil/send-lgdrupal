<?php

namespace Drupal\feeds\Feeds\Fetcher\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form on the feed edit page for the HttpFetcher.
 */
class HttpFetcherFeedForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs an HttpFeedForm object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $form['source'] = [
      '#title' => $this->t('Feed URL'),
      '#type' => 'url',
      '#default_value' => $feed->getSource(),
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    try {
      $url = Feed::translateSchemes($form_state->getValue('source'));
    }
    catch (\InvalidArgumentException $e) {
      $form_state->setError($form['source'], $this->t("The url's scheme is not supported. Supported schemes are: @supported.", [
        '@supported' => implode(', ', Feed::getSupportedSchemes()),
      ]));
      // If the source doesn't have a valid scheme the rest of the validation
      // isn't helpful. Break out early.
      return;
    }
    $form_state->setValue('source', $url);

    if (!$this->plugin->getConfiguration('auto_detect_feeds')) {
      return;
    }

    try {
      $response = $this->client->get($url);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      $form_state->setError($form['source'], $this->t('The feed from %site seems to be broken because of error "%error".', $args));

      return;
    }

    if ($url = Feed::getCommonSyndication($form_state->getValue('source'), (string) $response->getBody())) {
      $form_state->setValue('source', $url);
    }
    else {
      $form_state->setError($form['source'], $this->t('Invalid feed URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setSource($form_state->getValue('source'));
  }

  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   */
  protected function get($url) {
    try {
      $response = $this->client->get(Feed::translateSchemes($url));
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    return $response;
  }

}
