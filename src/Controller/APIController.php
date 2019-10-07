<?php

namespace Drupal\mollo_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mollo_email\Utility\ImapTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class APIController.
 */
class APIController extends ControllerBase
{

  use ImapTrait;

  public function list():JsonResponse{
    $name = 'list';
    $base = '/api/email/';
    $version = '1.0.0';

    $emails = self::listFromImap();

    $response = [
      'name' => $base.$name,
      'version' => $version,
      'emails' => $emails,
    ];

    return new JsonResponse($response);

  }


  /**
   * @return JsonResponse
   */
  public function invalid(): JsonResponse
  {
    $name = 'invalid';
    $base = '/api/email/';
    $version = '1.0.5';
    $dir = DRUPAL_ROOT.'/mails/';

    //  $addresses = ['info@test.com', 'example@example.com'];

    $addresses_IMAP = self::getAllInvalidAddresses();
    [$addresses_FIELES, $exclude_list] = self::getEmailsFromDirectory($dir);
    $addresses =array_merge($addresses_FIELES, $addresses_IMAP);

    $response = [
      'name' => $base.$name,
      'version' => $version,
      'directory' => $dir,
      'exclude' => $exclude_list,
      'addresses' => $addresses,
    ];

    return new JsonResponse($response);
  }

}
