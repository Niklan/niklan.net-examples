<?php

namespace Drupal\example\Mail;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Notifies user about successful authentication.
 */
final class UserLoginMail {

  /**
   * The mail handler.
   *
   * @var \Drupal\example\Mail\MailHandler
   */
  protected $mailHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new UserLoginEmail object.
   *
   * @param \Drupal\example\Mail\MailHandler $mail_handler
   *   The mail handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MailHandler $mail_handler, RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    $this->mailHandler = $mail_handler;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * Sends email to user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account which authenticated.
   *
   * @return bool
   *   The message status.
   */
  public function send(UserInterface $account): bool {
    $to = $account->getEmail();
    $user_agent = $this->requestStack->getCurrentRequest()->headers->get('User-Agent');
    $subject = new TranslatableMarkup('Logged in to your @site account from @user_agent', [
      '@site' => $this->configFactory->get('system.site')->get('name'),
      '@user_agent' => $user_agent,
    ]);

    $body = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => new TranslatableMarkup('We detected that your @account_name account has been logged in. Was it you?', [
          '@account_name' => $account->getAccountName(),
        ]),
      ],
      'device' => [
        '#markup' => new TranslatableMarkup('Device: @user_agent', [
          '@user_agent' => $user_agent,
        ]),
      ],
    ];

    $params = [
      'id' => 'user_login',
      'langcode' => $account->getPreferredLangcode(),
    ];

    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

}
