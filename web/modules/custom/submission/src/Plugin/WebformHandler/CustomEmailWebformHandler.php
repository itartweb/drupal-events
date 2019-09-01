<?php

namespace Drupal\submission\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "local_email",
 *   label = @Translation("Local email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission to a different email address per language."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class CustomEmailWebformHandler extends EmailWebformHandler {

  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    $time = $webform_submission->created->getValue()[0]['value'];
    $created = \Drupal::service('date.formatter')->format(
      $time, 'custom', 'd.m.Y'
    );
    $training = $webform_submission->getElementData('training');
    $countrylang = $webform_submission->getElementData('countrylang');
    $retailer = $webform_submission->getElementData('retailer');
    $voornaam_en_naam = $webform_submission->getElementData('voornaam_en_naam');
    $e_mail = $webform_submission->getElementData('e_mail');
    $telefoon = $webform_submission->getElementData('telefoon');
    $opmerkingen = $webform_submission->getElementData('opmerkingen');
    $message['body'] = '<p><b>Submitted on: </b>' . $created . '</p>
<p><b>Training: </b>' . $training . '</p>
<p><b>Countrylang: </b>' . $countrylang . '</p>
<p><b>Retailer: </b>' . $retailer . '</p>
<p><b>First name and name: </b>' . $voornaam_en_naam . '</p>
<p><b>E-mail: </b>' . $e_mail . '</p>
<p><b>Phone: </b>' . $telefoon . '</p>
<p><b>Comment: </b>' . $opmerkingen . '</p>';

    $service = \Drupal::service('submission.custom_services');
    $service->sendMail($message['bcc_mail'], $message['body'], $message['subject']);
  }
}
