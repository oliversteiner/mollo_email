<?php

namespace Drupal\mollo_email\Utility;

use Drupal;
use http\Exception\RuntimeException;

trait ImapTrait
{


  /**
   * @param $input
   * @return array
   *
   * search for SMTP Error codes
   * search for string 'SMTP error' and next some 3digit number (like 550)
   */
  private static function extractReason($input, $action): array
  {
    $error_code = 0;

    // Search for SMTP Error Codes
    $pattern = '/(SMTP error[\s\S]*?)(\b[1-5]\d{2}\b)/';
    if (preg_match($pattern, $input, $matches_code, PREG_OFFSET_CAPTURE)) {
      $error_code = $matches_code[2][0];
    }

    $pattern_status = '/550 5.1.1/';
    preg_match($pattern_status, $input, $matches);
    if ($matches) {
      $error_code = 550;
    }

    // Yahoo,
    $pattern_addresses_failed = '/following address.*?failed/';
    if (preg_match($pattern_addresses_failed, $input)) {
      $error_code = 550;
    }

    // Mailbox is full
    $pattern_mailbox_full = '/mailbox is full/i';
    if (preg_match($pattern_mailbox_full, $input)) {
      $error_code = 552;
    }

    // No Error Code: Search for Strings
    $pattern_no_smtp_service = '/no SMPT service/i'; // invalid domain
    if (preg_match($pattern_no_smtp_service, $input)) {
      $error_code = 512;
    }

    // Unrouteable address
    $pattern_unrouteable_address = '/Unrouteable address/';
    if (preg_match($pattern_unrouteable_address, $input)) {
      $error_code = 550;
    }

    // Bluewin:
    $pattern_bluewin = '/following recipient address/';
    if (preg_match($pattern_bluewin, $input)) {
      $error_code = 550;
    }

    $pattern_address_unknown = '/address unknown/';
    if (preg_match($pattern_address_unknown, $input)) {
      $error_code = 550;
    }

    if ($action === 'delayed') {
      $error_code = 451;
    }

    if ($error_code === 0 && $action === 'failed') {
      $error_code = 600;
    }

    switch ($error_code) {
      case 451:
        $reason = 'Requested action delayed';
        break;

      case 510:
      case 511:
        $reason = 'Bad email address';
        break;

      case 512:
        $reason = 'Address type is incorrect, Check Domain Name';
        break;

      case 513:
        $reason = 'Address type is incorrect';
        break;

      case 541:
      case 554:
        $reason = 'Email is spam, or IP has been blacklisted';
        break;

      case 550:
        $reason = 'Non-existent email address';
        break;

      case 552:
        $reason = 'Mailbox full';
        break;

      case 571:
        $reason = 'Delivery not authorized message refused';
        break;

      case 600:
        $reason = 'Unknown Error';
        break;

      default:
        $reason = 'No error dedected';
        break;
    }

    $code = $error_code;

    return [$code, $reason];
  }

  private static function extractAction($input): string
  {
    $action = 'none';
    $pattern_action = '/Action:(.+)/';
    preg_match($pattern_action, $input, $matches);
    if ($matches) {
      $action = trim($matches[1]);
    }
    return $action;
  }

  private static function extractEmailAddresses($input, $exclude_list): array
  {
    $addresses = [];

    $pattern_email = '/(\S+@\S+\.\S+)/';
    preg_match_all($pattern_email, $input, $matches);

    foreach ($matches[0] as $match) {
      // remove 'rfc822' and ';'
      $address = str_replace(
        array(
          'rfc822;',
          ';',
          '&lt',
          '&gt',
          '(',
          ')',
          '[',
          ']',
          ':',
          ';',
          '"',
          '\''
        ),
        '',
        $match
      );

      // remove admin addresses
      if (!in_array($address, $exclude_list, false)) {
        $addresses[] = $address;
      }
    }

    $addresses = array_unique($addresses);

    return $addresses;
  }

  /**
   * @param string $input
   * @param $exclude_list
   * @return array
   */
  public static function checkForDeliveryFailed(
    $input = '',
    $exclude_list = []
  ): array {
    if (!$input) {
      $message = 'Input Empty';
      throw new \RuntimeException($message);
    }

    $text = strip_tags($input);
    $addresses = self::extractEmailAddresses($text, $exclude_list);
    $action = self::extractAction($text);
    $reason = self::extractReason($text, $action);

    $result = [
      'addresses' => $addresses,
      'code' => $reason[0],
      'reason' => $reason[1],
      'action' => $action
    ];

    return $result;
  }

  /**
   * @return array
   */
  public static function listFromImap(): array
  {
    $output = [];
    $module = 'mollo_email';

    $config = Drupal::config($module . '.settings');

    $email_from = $config->get('imap_user');

    // TODO move to SettingsPage
    $user_name = $config->get('imap_user');
    $password = $config->get('imap_password');
    $imap_server = $config->get('imap_server');
    $mailbox = '{' . $imap_server . ':993/imap/ssl/novalidate-cert}INBOX';

    $mbox = imap_open($mailbox, $user_name, $password);

    //If the imap_open function returns a boolean FALSE value,
    //then we failed to connect.
    if ($mbox === false) {
      //If it failed, throw an exception that contains
      //the last imap error.
      throw new RuntimeException(imap_last_error());
    }

    //If we get to this point, it means that we have successfully
    //connected to our mailbox via IMAP.

    //Lets get all emails that were received since a given date.
    $date = date('d M Y', strToTime('-7 days'));
    $sortresults = imap_sort($mbox, SORTARRIVAL, 1);

    $keyword = 'Mail delivery failed';
    $searchresults = imap_search(
      $mbox,
      'SUBJECT "' . $keyword . '"',
      SE_FREE,
      'UTF-8'
    );
    // $searchresults = imap_search($mbox, "SINCE \"$date\"", SE_UID);

    $sorted_search_results = order_search($searchresults, $sortresults);

    //If the $emails variable is not a boolean FALSE value or
    //an empty array.
    if (!empty($sorted_search_results)) {
      //Loop through the emails.
      foreach ($sorted_search_results as $uid) {
        //Fetch an overview of the email.
        $overview = imap_fetch_overview($mbox, $uid);
        $overview = $overview[0];

        //Get the body of the email.
        $mailbody = imap_fetchbody($mbox, $uid, 1, FT_PEEK);
        $exclude_list = [];
        $result = self::checkForDeliveryFailed($mailbody, $exclude_list);

        $mail = $result;
        $mail['uid'] = $uid;
        $mail['subject'] = htmlentities($overview->subject);
        //Print out the sender's email address / from email address.
        $mail['From'] = imap_utf8($overview->from);
        $mail['Date'] = $overview->date;
        $mail['body'] = $mailbody;

        $output[] = $mail;
      }
    }

    return $output;
  }

  /**
   * @return array
   */
  public static function getAllInvalidAddresses(): array
  {
    $addresses = [];
    $emails_from_IMAP = self::listFromImap();
    foreach ($emails_from_IMAP as $email) {
      if (isset($email['addresses'])) {
        foreach ($email['addresses'] as $address) {
          $addresses[] = $address;
        }
      }
    }

    // remove duplicates
    array_unique($addresses);

    return $addresses;
  }
}

// Local Testing
// --------------------------------------------------------------------------------

/**
 * @param $dir
 * @return array
 */
function getEmailsFromDirectory($dir)
{
  $messages = [];

  // is $dir defined?
  if (!$dir) {
    $error = 'void $dir';
    printf("\n\033[31m" . $error . "\n");
    throw new \RuntimeException($error);
  }

  // is $dir valid directory?
  if (!is_dir($dir)) {
    $error = 'Directory not found';
    printf("\n\033[31m" . $error . "\n");
    throw new \RuntimeException($error);
  }

  // read all files in $dir
  if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
      if ($file === '.' || $file === '..') {
        continue;
      }

      $message['name'] = $file;
      $message['content'] = file_get_contents($dir . $file);
      $messages[] = $message;
    }
    closedir($dh);
  }
  return $messages;
}

/**
 * @param $result
 */
function cliOutput($result): void
{
  switch ($result['code']) {
    case 510:
    case 571:
    case 554:
    case 513:
    case 512:
    case 541:
    case 511:
      $color = "\033[37m";
      break;

    case 451:
      $color = "\033[36m";
      break;

    case 550:
      $color = "\033[32m";
      break;

    case 552:
      $color = "\033[34m";
      break;

    case 600:
      $color = "\033[31m";
      break;

    default:
      $color = "\033[1;37m";
      break;
  }

  printf("\n\t" . $color . $result['reason']);
  printf("\n\033[0m \t-----------------------------\n");
  $i = 0;
  foreach ($result['addresses'] as $address) {
    if ($i === 0) {
      printf("\033[0m\tAddress\t \033[33m %s\n", $address);
    } else {
      printf("\033[33m\t\t\t\t%s\n", $address);
    }
    $i++;
  }

  printf("\033[0m\tCode\t \033[35m %s\n", $result['code']);
  printf("\033[0m\tReason\t \033[35m %s\n", $result['reason']);
  printf("\033[0m\tAction\t \033[34m %s\n", $result['action']);
  printf("\n\n");
}

function order_search($searchresults, $sortresults)
{
  return array_values(array_intersect($sortresults, $searchresults));
}

// --------------------------------------------------------------------------------

/*$exclude_list = ['admin@example.com'];
$dir = '~/test_email/';

$messages = getEmailsFromDirectory($dir);

foreach ($messages as $message) {
  $result = checkForDeliveryFailed($message['content'], $exclude_list);

  echo "\e[0m\n" . $message['name'] . "\n";
  cliOutput($result);
}

echo "\033[37m\n\n-------------- Statistic ----------------\n";

echo 'Emails: ' . count($messages) . "\n";

echo "----------------------------------------\n";*/
