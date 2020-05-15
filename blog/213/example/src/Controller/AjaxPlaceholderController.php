<?php

namespace Drupal\example\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\example\Render\Placeholder\AjaxPlaceholderStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides controller implementations for Ajax Placeholder strategy.
 */
final class AjaxPlaceholderController implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer|object|null
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AjaxPlaceholderController {
    $instance = new static();
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Handles request with no JS enabled client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function noJsCookie(Request $request): Response {
    if ($request->cookies->has(AjaxPlaceholderStrategy::NOJS_COOKIE)) {
      throw new AccessDeniedException();
    }

    if (!$request->query->has('destination')) {
      throw new HttpException(Response::HTTP_BAD_REQUEST, 'The original location is missing.');
    }

    $response = new LocalRedirectResponse($request->query->get('destination'));
    // Set cookie without httpOnly, so that JavaScript can delete it.
    $response->headers->setCookie(new Cookie(AjaxPlaceholderStrategy::NOJS_COOKIE, TRUE, 0, '/', NULL, FALSE, FALSE, FALSE, NULL));
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . AjaxPlaceholderStrategy::NOJS_COOKIE]));
    return $response;
  }

  /**
   * Handles request from AJAX and returns result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The AJAX request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The AJAX response.
   */
  public function process(Request $request): Response {
    $json = $request->getContent();
    $info = Json::decode($json);
    $callback = $info['callback'];
    $args = $info['args'];
    $token = $info['token'];

    // @see \Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor
    $response = new AjaxResponse();
    if ($this->validateToken($callback, $args, $token)) {
      // @see \Drupal\Core\Render\Renderer::doCallback
      $render_array = [
        '#lazy_builder' => [$callback, $args],
        '#create_placeholder' => FALSE,
      ];
      $html = $this->renderer->renderRoot($render_array);
      $response->setAttachments($render_array['#attached']);

      // The placeholder will be replaced only if there is a result. If result
      // is empty (callback returns nothing or rendering doesn't provide HTML)
      // then we remove placeholder from the page.
      if (!empty($html)) {
        $response->addCommand(new ReplaceCommand(NULL, $html));
      }
      else {
        $response->addCommand(new RemoveCommand(NULL));
      }
    }
    else {
      $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    return $response;
  }

  /**
   * Validates that provided token in payload is valid.
   *
   * Since this controller response for every POST request and execute code,
   * we must reduce possible thread income. The very first and simple solution
   * is to validate token from placeholder with what is actually expected.
   *
   * The token uses site 'salt' and can't be compromise if 'salt' is not leaked.
   * By this token we only allows callbacks that we expect. If callback or any
   * argument will be different from what we expect, the token will be
   * different.
   *
   * @param string $callback
   *   The callback function.
   * @param array $args
   *   The callback arguments.
   * @param string $provided_token
   *   The payload token.
   *
   * @return bool
   *   Whether token is valid and data is valid.
   */
  private function validateToken(string $callback, array $args, string $provided_token): bool {
    return AjaxPlaceholderStrategy::generateToken($callback, $args) == $provided_token;
  }

}
