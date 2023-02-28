<?php

namespace Drupal\localgov_openreferral\EventSubscriber;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\serialization\EventSubscriber\DefaultExceptionSubscriber as SerializationDefaultExceptionSubscriber;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Serializes exceptions with correct headers for Open Referral.
 */
class DefaultExceptionSubscriber extends SerializationDefaultExceptionSubscriber {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return parent::getPriority() + 25;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['openreferral_json'];
  }

  /**
   * {@inheritdoc}
   */
  public function onException(ExceptionEvent $event) {
    if (!$this->isOpenreferralExceptionEvent($event)) {
      return;
    }
    if (($exception = $event->getThrowable()) && !$exception instanceof HttpException) {
      $exception = new HttpException(500, $exception->getMessage(), $exception);
      $event->setThrowable($exception);
    }

    $this->setEventResponse($event, $exception->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  protected function setEventResponse(ExceptionEvent $event, $status) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpException $exception */
    $exception = $event->getThrowable();
    $content = ['message' => $exception->getMessage()];
    $encoded_content = $this->serializer->serialize($content, 'json');
    $headers = $exception->getHeaders();

    // Add the MIME type from the request to send back in the header.
    $headers['Content-Type'] = 'application/json';

    // If the exception is cacheable, generate a cacheable response.
    if ($exception instanceof CacheableDependencyInterface) {
      $response = new CacheableResponse($encoded_content, $exception->getStatusCode(), $headers);
      $response->addCacheableDependency($exception);
    }
    else {
      $response = new Response($encoded_content, $exception->getStatusCode(), $headers);
    }

    $event->setResponse($response);
  }

  /**
   * Check if the error is openreferral json.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $exception_event
   *   The exception event.
   *
   * @return bool
   *   TRUE if it needs to be formatted using JSON:API. FALSE otherwise.
   */
  protected function isOpenreferralExceptionEvent(ExceptionEvent $exception_event) {
    // Jsonapi also checks the route for anything matching.
    // $parameters = $request->attributes->all();
    // || (bool) Routes::getResourceTypeNameFromParameters($parameters);
    $request = $exception_event->getRequest();
    return $request->getRequestFormat() === 'openreferral_json';
  }

}
