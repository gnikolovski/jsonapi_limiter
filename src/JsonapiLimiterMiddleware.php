<?php

namespace Drupal\jsonapi_limiter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Jsonapi Limiter Middleware.
 *
 * @package Drupal\jsonapi_limiter
 */
class JsonapiLimiterMiddleware implements HttpKernelInterface {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $app;

  /**
   * The Jsonapi Limiter service.
   *
   * @var \Drupal\jsonapi_limiter\JsonapiLimiterService
   */
  protected $service;

  /**
   * Constructs Jsonapi Limiter Middleware.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $app
   *   The HTTP kernel.
   * @param \Drupal\jsonapi_limiter\JsonapiLimiterService $service
   *   The Jsonapi Limiter service.
   */
  public function __construct(HttpKernelInterface $app, JsonapiLimiterService $service) {
    $this->app = $app;
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $is_update = $this->service->isUpdate($request);
    $is_jsonapi_request = $this->service->isJsonApiRequest($request);

    // Check limits only for POST, PUT and PATCH HTTP methods and only if the
    // current request is a JSON:API request.
    if ($is_update && $is_jsonapi_request) {
      $limit = $this->service->limit($request);
      if ($limit) {
        return $this->service->respond($request);
      }
    }

    // Continue as if nothing happened.
    return $this->app->handle($request, self::MASTER_REQUEST, TRUE);
  }

}
