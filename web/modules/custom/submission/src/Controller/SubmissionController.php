<?php

namespace Drupal\submission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;

class SubmissionController extends ControllerBase
{

  /**
   * {@inheritdoc}
   */
  public function addSubmission($training)
  {
    $form = \Drupal::formBuilder()->getForm('Drupal\submission\Form\SubmissionStepAddForm', $training);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function editSubmission($training, $submission, $step)
  {
    $service = \Drupal::service('submission.custom_services');
    $number_participants = $service->getAmountMembersByTrainingId($training->id()) ? $service->getAmountMembersByTrainingId($training->id()) : 0;
    $limit = !empty($training->get('field_maximum_participants')->getValue()[0]['value']) ? $training->get('field_maximum_participants')->getValue()[0]['value'] : 0;
    $already_exist_members = !empty($submission->get('field_members')->referencedEntities()) ? count($submission->get('field_members')->referencedEntities()) : 0;
    $available = (int) $limit - (int) $number_participants;
    $cardinality = $already_exist_members + $available;
    $with_lunch = $training->get('field_active_webform')->referencedEntities()[0]->id() == '50' ? TRUE : FALSE;

    $form_state_additions = [
      'training' => $training,
      'submission' => $submission,
      'step' => $step,
      'already_exist_members' => $already_exist_members,
      'available' => $available,
      'with_lunch' => $with_lunch,
    ];
//    $form = \Drupal::formBuilder()->getForm('Drupal\submission\Form\SubmissionStepForm', $training, $submission, $step);
    $form = \Drupal::service('entity.form_builder')->getForm($submission, 'edit', $form_state_additions);
    $form['#cache'] = ['max-age' => 0];
//    $form['field_email_reminder']['#access'] = FALSE;
//    $form['field_email_after_creation']['#access'] = FALSE;
    $form['retailer_id']['#access'] = FALSE;
    $form['training_id']['#access'] = FALSE;
    $form['country_id']['#access'] = FALSE;
    $form['field_members']['#attributes']['data-limit-members'] = $cardinality;
    $form['actions']['delete']['#title'] = t('Annuleer mijn inschrijving');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSubmission($training, $submission)
  {
    $form = \Drupal::formBuilder()->getForm('Drupal\submission\Form\SubmissionStepDeleteForm', $training, $submission);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess($operation, EntityInterface $training = NULL, EntityInterface $submission = NULL)
  {
    $account = \Drupal::currentUser();
    switch ($operation) {
      case 'view':
        if ($this->checkCode($submission) == TRUE) {
          return AccessResult::allowed();
        } else {
          return AccessResult::allowedIfHasPermission($account, 'view submission entity');
        }
        break;

      case 'edit':
        if ($this->checkCode($submission) == TRUE) {
          return AccessResult::allowed();
        } else {
          return AccessResult::allowedIfHasPermission($account, 'edit submission entity');
        }
        break;

      case 'delete':
        if ($this->checkCode($submission) == TRUE) {
          return AccessResult::allowed();
        } else {
          return AccessResult::allowedIfHasPermission($account, 'delete submission entity');
        }
        break;

      case 'code':
      case 'create':
        return AccessResult::allowed();
        break;
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCode($submission)
  {
    $request = \Drupal::request();
    $session = $request->getSession();
    if (\Drupal::currentUser()->isAnonymous()) {
      if (!empty($submission->get('code')->getValue()[0]['value'])) {
        $code = $submission->get('code')->getValue()[0]['value'];
        if ($code == $session->get('unique_code')) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
