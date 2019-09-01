<?php

namespace Drupal\submission\Services;

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class CustomService.
 */
class CustomService
{

  /**
   * Constructs a new CustomService object.
   */
  public function __construct(){}

  /**
   * {@inheritdoc}
   */
  public function buildMail($submission_id) {
    $submission_data = $this->getSubmissionData($submission_id);
    if ($submission_data && (int) $submission_data['amount_members'] > 0) {
      $submission = \Drupal::entityTypeManager()->getStorage('submission')->load($submission_id);

      if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
        $subject = "Je unieke nummer voor de inschrijving " . $submission_data["brands"] . " " . $submission_data["title_training"];
      }
      else {
        $subject = "Votre numéro unique pour l'inscription " . $submission_data["brands"] . " " . $submission_data["title_training"];
      }

      $message = $this->getMessageBody($submission_data, 'add');
      $attachments = $this->getAttachments($submission_data['country_id']);
      $this->sendMail($submission_data['store_email'], $message, $subject, $attachments);

      foreach ($submission_data['members'] as $member) {
        if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
          $subject = 'Inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        else {
          $subject = 'Inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        $submission_data['first_name'] = $member->field_name->value;
        $submission_data['last_name'] = $member->field_lastname->value;
        $submission_data['is_member'] = TRUE;
        $email = $member->field_email->value;
        $message = $this->getMessageBody($submission_data, 'add');
        $attachments = $this->getAttachments($submission_data['country_id']);
        $this->sendMail($email, $message, $subject, $attachments);
      }

      foreach ($submission_data['users'] as $user) {
        $user->getEmail();
        $message = 'There was a new subscription for ' . $submission_data["brands"] . ' ' . $submission_data["title_training"] . ', followed by the details.';
        $this->sendMail($user->getEmail(), $message, $subject);
      }

      $submission->set('field_email_after_creation', '1');
      $submission->save();
    }
  }


  /**
   * {@inheritdoc}
   */
  public function buildMaileReminder($submission_id) {
    $submission_data = $this->getSubmissionData($submission_id);
    if ($submission_data) {
      $submission = \Drupal::entityTypeManager()->getStorage('submission')->load($submission_id);

      if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
        $subject = "Een herinnering - Je unieke nummer voor de inschrijving " . $submission_data["brands"] . " " . $submission_data["title_training"];
      }
      else {
        $subject = "Un rappel - Votre numéro unique pour l'inscription " . $submission_data["brands"] . " " . $submission_data["title_training"];
      }

      $message = $this->getMessageBody($submission_data, 'add');
      $attachments = $this->getAttachments($submission_data['country_id']);
      $this->sendMail($submission_data['store_email'], $message, $subject, $attachments);

      foreach ($submission_data['members'] as $member) {
        if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
          $subject = 'Een herinnering - Inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        else {
          $subject = 'Un rappel - Inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        $submission_data['first_name'] = $member->field_name->value;
        $submission_data['last_name'] = $member->field_lastname->value;
        $submission_data['is_member'] = TRUE;
        $email = $member->field_email->value;
        $message = $this->getMessageBody($submission_data, 'add');
        $attachments = $this->getAttachments($submission_data['country_id']);
        $this->sendMail($email, $message, $subject, $attachments);
      }

      foreach ($submission_data['users'] as $user) {
        $user->getEmail();
        $message = 'There was a new subscription for ' . $submission_data["brands"] . ' ' . $submission_data["title_training"] . ', followed by the details.';
        $this->sendMail($user->getEmail(), $message, $subject);
      }

      $submission->set('field_email_reminder', '1');
      $submission->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildMailUpdate($submission_id, $diffs, $edit=NULL, $new_data=NULL) {

    $submission_data = $this->getSubmissionData($submission_id);

    if (isset($diffs['added'])) {
      $submission_data['amount_members'] = (int) $submission_data['amount_members'] + count($diffs['added']);
    }
    if (isset($diffs['removed'])) {
      $submission_data['amount_members'] = (int) $submission_data['amount_members'] - count($diffs['removed']);
    }

    $submission_data['was_updated'] = TRUE;
    if ($submission_data) {

      if (!$edit) {
        if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
          $subject = 'Wijziging inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        else {
          $subject = 'Changement de l’inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }

        $message = $this->getMessageBody($submission_data, 'add');
        $attachments = $this->getAttachments($submission_data['country_id']);
        $this->sendMail($submission_data['store_email'], $message, $subject, $attachments);

        foreach ($submission_data['users'] as $user) {
          $user->getEmail();
          $message = 'A subscription for ' . $submission_data["brands"] . ' ' . $submission_data["title_training"] . ' was modified, followed by the details';
          $this->sendMail($user->getEmail(), $message, $subject);
        }

      }

      if (isset($diffs['removed']) && !empty($diffs['removed'])) {
        if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
          $subject = 'Annulering inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        else {
          $subject = 'Annulation de l’inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        foreach ($diffs['removed'] as $r) {
          $p = Paragraph::load($r);
          $submission_data['first_name'] = $p->field_name->value;
          $submission_data['last_name'] = $p->field_lastname->value;
          $submission_data['is_member'] = TRUE;
          $email = $p->field_email->value;
          $message = $this->getMessageBody($submission_data, 'remove');
          $this->sendMail($email, $message, $subject);
        }
      }
      if (isset($diffs['added']) && !empty($diffs['added'])) {
        if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
          $subject = 'Inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        else {
          $subject = 'Inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        foreach ($diffs['added'] as $a) {
          $p = Paragraph::load($a);
          $submission_data['first_name'] = $p->field_name->value;
          $submission_data['last_name'] = $p->field_lastname->value;
          $submission_data['is_member'] = TRUE;
          $email = $p->field_email->value;
          $message = $this->getMessageBody($submission_data, 'add');
          $attachments = $this->getAttachments($submission_data['country_id']);
          $this->sendMail($email, $message, $subject, $attachments);
        }
      }
      if (!empty($edit)) {
        if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
          $subject = 'Inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        else {
          $subject = 'Inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
        }
        $p = Paragraph::load($edit);
        $submission_data['first_name'] = !empty($new_data) ? $new_data->get('field_name')->getValue()[0]['value'] : $p->field_name->value;
        $submission_data['last_name'] = !empty($new_data) ? $new_data->get('field_lastname')->getValue()[0]['value'] : $p->field_lastname->value;
        $submission_data['is_member'] = TRUE;
        $email = !empty($new_data) ? $new_data->get('field_email')->getValue()[0]['value'] : $p->field_email->value;
        $message = $this->getMessageBody($submission_data, 'add');
        $attachments = $this->getAttachments($submission_data['country_id']);
        $this->sendMail($email, $message, $subject, $attachments);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildMailDelete($submission_id) {

    $submission_data = $this->getSubmissionData($submission_id);
    if ($submission_data) {

      if ($submission_data['country_id'] == '1' || $submission_data['country_id'] == '2') {
        $subject = 'Annulering inschrijving ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
      }
      else {
        $subject = 'Annulation de l’inscription ' . $submission_data["brands"] . ' ' . $submission_data["title_training"];
      }

      $message = $this->getMessageBody($submission_data, 'remove');
      $this->sendMail($submission_data['store_email'], $message, $subject);

      foreach ($submission_data['users'] as $user) {
        $user->getEmail();
        $message = 'A subscription for ' . $submission_data["brands"] . ' ' . $submission_data["title_training"] . ' was cancelled, followed by the details';
        $this->sendMail($user->getEmail(), $message, $subject);
      }

      foreach ($submission_data['members'] as $member) {
        $submission_data['first_name'] = $member->field_name->value;
        $submission_data['last_name'] = $member->field_lastname->value;
        $submission_data['is_member'] = TRUE;
        $email = $member->field_email->value;
        $message = $this->getMessageBody($submission_data, 'remove');
        $this->sendMail($email, $message, $subject);
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function getSubmissionData($submission_id) {
    $mail_variables = [];

    if ($submission_id) {
      $submission = \Drupal::entityTypeManager()->getStorage('submission')->load($submission_id);
      $mail_variables['unique_code'] = $submission->code->value;
      $mail_variables['country_id'] = $submission->country_id->referencedEntities()[0]->id();
      $mail_variables['retailer_id'] = $submission->retailer_id->value;
      $mail_variables['is_member'] = FALSE;
      $mail_variables['was_updated'] = FALSE;
      $mail_variables['members'] = $submission->field_members->referencedEntities();
      $mail_variables['amount_members'] = count($mail_variables['members']);
      $mail_variables['store_email'] = $submission->email->value;
      $mail_variables['store_address'] = $submission->store_address->value;
      $mail_variables['store_department'] = $submission->store_department->value;
      $training_id = $submission->training_id->referencedEntities()[0]->id();

      if ($training_id) {

        $training = $this->getTrainingById($training_id);
        $mail_variables['users'] = $training->field_follow_up_by->referencedEntities();
        $mail_variables['title_training'] = $training->field_public_title->value;
        $brands = $training->field_brand->referencedEntities();
        $brads_list = '';
        foreach ($brands as $brand) {
          $brads_list .= $brads_list !== '' ? ', ' . $brand->getName() : $brand->getName();
        }
        $mail_variables['brands'] = $brads_list;
        $start_time = $training->field_timeslot->start_date;
        $mail_variables['start_date'] = \Drupal::service('date.formatter')->format(
          $start_time->getTimestamp(), 'custom', 'D d.m.Y'
        );
        $mail_variables['start_time'] = \Drupal::service('date.formatter')->format(
          $start_time->getTimestamp(), 'custom', 'H:i A'
        );
        $end_time = $training->field_timeslot->end_date;
        $mail_variables['end_time'] = \Drupal::service('date.formatter')->format(
          $end_time->getTimestamp(), 'custom', 'H:i A'
        );
        $mail_variables['address'] = strip_tags($training->field_location_reference->referencedEntities()[0]->body->value, '<p>');
      }
    }

    return $mail_variables;
  }


  /**
   * {@inheritdoc}
   */
  public function getMessageBody($mail_variables, $type) {
    $message = FALSE;
    switch ($type) {
      case 'add':
        $r = [
          '#theme' => 'bf_mail',
          '#mail_variables' => $mail_variables,
        ];
        $message = \Drupal::service('renderer')->render($r);
        break;

      case 'remove':
        $r = [
          '#theme' => 'bf_mail_remove',
          '#mail_variables' => $mail_variables,
        ];
        $message = \Drupal::service('renderer')->render($r);
        break;
    }
    return !empty($message) ? $message : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments($language_id) {
    $attachment = FALSE;
    switch ($language_id) {
      case '1':
        $attachment['filecontent'] = file_get_contents('public://pdf/Routebeschrijving_BE-NL.pdf');
        $attachment['filename'] = 'Routebeschrijving.pdf';
        $attachment['filemime'] = 'application/pdf';
        break;

      case '2':
        $attachment['filecontent'] = file_get_contents('public://pdf/Routebeschrijving_NL.pdf');
        $attachment['filename'] = 'Routebeschrijving.pdf';
        $attachment['filemime'] = 'application/pdf';
        break;

      case '3':
        $attachment['filecontent'] = file_get_contents('public://pdf/Itineraire_BE-FR.pdf');
        $attachment['filename'] = 'Itinéraire.pdf';
        $attachment['filemime'] = 'application/pdf';
        break;

      case '4':
        $attachment['filecontent'] = file_get_contents('public://pdf/Itineraire_BE-FR.pdf');
        $attachment['filename'] = 'Itinéraire.pdf';
        $attachment['filemime'] = 'application/pdf';
        break;
    }
    return ($attachment) ? $attachment : NULL;
  }


  /**
   * {@inheritdoc}
   */
  public function sendMail($email, $message, $subject, $attachments=NULL) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'submission';
    $key = 'rem';
    $to = $email;
    $params['message'] = $message;
    $params['subject'] = $subject;
    $params['attachments'][] = $attachments;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);

    if ($result['result'] !== TRUE) {
      \Drupal::logger('submission')->error('The message for ' . $email . ' was not send.');
    }
    else {
      \Drupal::logger('submission')->info('The message for ' . $email . ' was send.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTrainingById($training_id) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($training_id);
    return !empty($node) ? $node : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllSubmissions() {
//    $query = \Drupal::database()->select('submission__field_email_reminder', 's');
//    $query->fields('s', array('entity_id'));
//    $query->condition('s.field_email_reminder_value', '0');
//    $ids = $query->execute()->fetchAll();

    $query = \Drupal::database()->select('submission__field_email_reminder', 'sfr');
    $query->join('submission', 's', 's.submission_id = sfr.entity_id');
    $query
      ->fields('s', array('training_id'))
      ->fields('sfr', array('entity_id'))
      ->condition('sfr.field_email_reminder_value', '0');
    $ids = $query->execute()->fetchAll();

    return !empty($ids) ? $ids : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmountMembersByTrainingId($training_id) {
    $query = \Drupal::database()->select('submission', 's');
    $query->fields('s', array('submission_id'));
    $query->join('submission__field_members', 'sfm', 's.submission_id = sfm.entity_id');
    $query->condition('s.training_id', $training_id);
    $amount_members = count($query->execute()->fetchAll());
    return !empty($amount_members) ? $amount_members : 0;
  }

  /**
   * Generate unique code.
   */
  public function getUniqueCode($limit) {
    return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
  }

}
