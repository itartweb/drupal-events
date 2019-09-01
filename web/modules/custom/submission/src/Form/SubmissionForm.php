<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Form controller for the submission entity add forms.
 *
 * @ingroup event
 */
class SubmissionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'submission_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $event = NULL) {
    $form['#prefix'] = '<div id="submission-wrapper">';
    $form['#suffix'] = '</div>';

    $destination = \Drupal::request()->query->get('destination');
    if ($destination) {
      $redirect = $destination;
    }
    else {
      $redirect = '/event/' . $event->id();
    }
    $form['redirect'] = [
      '#type' => 'value',
      '#value' => $redirect,
    ];

    $form['event_id'] = [
      '#type' => 'value',
      '#value' => $event->id(),
    ];

    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $form_state->getValue('first_name'),
      '#description' => '',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('First name'),
      ],
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $form_state->getValue('last_name'),
      '#description' => '',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Last name'),
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#default_value' => $form_state->getValue('email'),
      '#description' => '',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('e-mail'),
      ],
    ];

    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => $form_state->getValue('phone'),
      '#description' => '',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('phone'),
      ],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#action' => 'submit',
      '#ajax' => [
        'callback' => [$this, 'submitAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $email = $form_state->getValue('email');
    $event_id = $form_state->getValue('event_id');
    if (\Drupal::service('submission.submission_services')->submissionEventExistsByEmail($event_id, $email)) {
      $form_state->setErrorByName('email', $this->t('Your submission email is already exists.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission = \Drupal::service('entity.manager')->getStorage('submission')->create();
    $submission->set('firstname', $form_state->getValue('first_name'));
    $submission->set('lastname', $form_state->getValue('last_name'));
    $submission->set('email', $form_state->getValue('email'));
    $submission->set('phone', $form_state->getValue('phone'));
    $submission->set('event_id', $form_state->getValue('event_id'));
    $status = $submission->save();

    if ($status == SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Your submission has been added.'));
    }
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response->addCommand(new ReplaceCommand('#submission-wrapper', $form));
    }
    else {
      $response->addCommand(new CloseModalDialogCommand());
      $redirect = $form_state->getValue('redirect');
      $response->addCommand(new RedirectCommand($redirect));
    }

    $form_state->setResponse($response);

    return $response;
  }

}
