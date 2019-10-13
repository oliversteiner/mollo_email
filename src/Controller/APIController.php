<?php

namespace Drupal\mollo_email\Controller;

use Drupal;
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
    $version = '1.0.9';
    $dir = DRUPAL_ROOT.'/mails/';

    //  $addresses = ['info@test.com', 'example@example.com'];

    // Get list form Config
    // Build Settings Name
    $addresses_config = [];

    $config = Drupal::service('config.factory')->getEditable('smmg_newsletter.settings');
    $config_invalid_email_string = $config->get('invalid_email');
    $list = explode(',', $config_invalid_email_string);
    foreach ($list as $mail) {
      $addresses_config[] = trim($mail);
    }

    $addresses_IMAP = self::getAllInvalidAddresses();
    [$addresses_FIELES, $exclude_list] = self::getEmailsFromDirectory($dir);
    $_addresses =array_merge($addresses_FIELES, $addresses_IMAP, $addresses_config);
    $addresses = array_unique($_addresses);
    sort($addresses);


    // Save new List to Config
    $config->set('invalid_email', implode(', ',$addresses));
    $config->save();


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
