<?php

namespace Drupal\jsonapi_limiter;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jsonapi Limiter Service.
 *
 * @package Drupal\jsonapi_limiter
 */
class JsonapiLimiterService {

  const STATE_KEY = 'jsonapi_limiter.last_request_time.';
  const RETRY_INTERVAL = 2;

  /**
   * Checks if this is an update (POST, PUT and PATCH HTTP methods) request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   Returns TRUE if this is an update request or FALSE.
   */
  public function isUpdate(Request $request) {
    if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if this is a JSON:API request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   Returns TRUE if this is a JSON:API request or FALSE.
   */
  public function isJsonApiRequest(Request $request) {
    // Check if the Uri contains 'jsonapi' substring.
    if (strpos($request->getUri(), 'jsonapi') !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Responds if the request limit is reached.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Json response.
   */
  public function respond(Request $request) {
    $message = [
      'message' => 'Too many requests',
      'method' => $request->getMethod(),
      'uri' => $request->getUri(),
    ];

    $headers = ['Retry-After' => self::RETRY_INTERVAL];

    return new JsonResponse($message, Response::HTTP_TOO_MANY_REQUESTS, $headers);
  }

  /**
   * Saves the current request time.
   *
   * @param string $uri_hash
   *   The Uri hash.
   */
  protected function saveRequestTime($uri_hash) {
    \Drupal::state()->set(self::STATE_KEY . $uri_hash, time());
  }

  /**
   * Checks if the current request should be limited.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   Returns TRUE if the request should be limited, or FALSE.
   */
  public function limit(Request $request) {
    $uri = $request->getUri();
    $uri_hash = md5($uri);
    $last_called = (int) \Drupal::state()->get(self::STATE_KEY . $uri_hash);

    // First request for this Uri, so we are not limiting the request.
    if ($last_called == 0) {
      $this->saveRequestTime($uri_hash);
      return FALSE;
    }

    // RETRY_INTERVAL number of seconds have passed, so we are not limiting the
    // request.
    if (time() - $last_called >= self::RETRY_INTERVAL) {
      $this->saveRequestTime($uri_hash);
      return FALSE;
    }

    return TRUE;
  }

}
