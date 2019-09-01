<?php

namespace Drupal\submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Form controller for the submission entity edit forms.
 *
 * @ingroup submission
 */
class SubmissionStepForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'submission_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $training = NULL, $submission = NULL, $step = NULL)
  {
    if ($submission == NULL) {
      $submission = \Drupal::service('entity.manager')->getStorage('submission')->create();
    }

    $service = \Drupal::service('submission.custom_services');
    $number_participants = $service->getAmountMembersByTrainingId($training->id()) ? $service->getAmountMembersByTrainingId($training->id()) : 0;
    $limit = !empty($training->get('field_maximum_participants')->getValue()[0]['value']) ? $training->get('field_maximum_participants')->getValue()[0]['value'] : 0;
    $already_exist_members = !empty($submission->get('field_members')->referencedEntities()) ? count($submission->get('field_members')->referencedEntities()) : 0;
    $available = (int) $limit - (int) $number_participants;
    $form_state->set('available', $available);
    $form_state->set('exist_members', $already_exist_members);

    $submission_id = $submission->id();
    $with_lunch = $training->get('field_active_webform')->referencedEntities()[0]->id() == '50' ? TRUE : FALSE;

    $form['#cache'] = ['max-age' => 0];
    $form_state->setCached(FALSE);
    $form['training'] = [
      '#type' => 'value',
      '#value' => $training,
    ];

    $form['submission'] = [
      '#type' => 'value',
      '#value' => $submission,
    ];

    $form['step'] = [
      '#type' => 'value',
      '#value' => $step,
    ];

    switch ($step) {
      case 1:
        $form['progress'] = [
          '#type' => 'markup',
          '#prefix' => '<ul class="submission-progress">',
          '#suffix' => '</ul>',
          '#markup' => $this->builbProgressBar($step),
        ];

        $form['title'] = [
          '#type' => 'markup',
          '#prefix' => '<h2 class="submission-title">',
          '#suffix' => '</h2>',
          '#markup' => $this->t('Algemene info'),
        ];
        $form['info'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="submission-subtitle">',
          '#suffix' => '</div>',
          '#markup' => $this->t("Vul hier uw gegevens in. In de volgende stap kan u ook de gegevens van uw collega's toevoegen."),
        ];

        $form['email'] = [
          '#type' => 'email',
          '#title' => $this->t('E-mail'),
          '#default_value' => !empty($submission->get('email')->getValue()[0]['value']) ? $submission->get('email')->getValue()[0]['value'] : NULL,
          '#description' => '',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('e-mail'),
          ],
        ];

        $form['store_department'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Winkel / afdeling'),
          '#default_value' => !empty($submission->get('store_department')->getValue()[0]['value']) ? $submission->get('store_department')->getValue()[0]['value'] : NULL,
          '#description' => '',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('Vul uw winkel / afdeling in'),
          ],
        ];

        $form['store_address'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Winkeladres'),
          '#default_value' => !empty($submission->get('store_address')->getValue()[0]['value']) ? $submission->get('store_address')->getValue()[0]['value'] : NULL,
          '#description' => '',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('Vul het adres in van uw winkel of afdeling'),
          ],
        ];
        break;

      case 2:
          $submission_form = \Drupal::service('entity.form_builder')->getForm($submission, 'default');
          dump($submission_form);
          //kint($submission_form);die;

//          $form['field_test'] = $submission_form['field_members'];

        $form['progress'] = [
          '#type' => 'markup',
          '#prefix' => '<ul class="submission-progress">',
          '#suffix' => '</ul>',
          '#markup' => $this->builbProgressBar($step),
        ];

        $form['title'] = [
          '#type' => 'markup',
          '#prefix' => '<h2 class="submission-title">',
          '#suffix' => '</h2>',
          '#markup' => $this->t('Deelnemers'),
        ];
        $form['info'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="submission-subtitle">',
          '#suffix' => '</div>',
          '#markup' => '<p>' . t("Hier kan u zich inschrijven en geïnteresseerde collega’s toevoegen. Indien er wijzigingen (of annulaties) aan de inschrijving dienen te gebeuren, bent u hier verantwoordelijk voor. Uw collega’s zullen ook een bevestigingsmail en eventueel bijkomende info over de training ontvangen.") . '</p><p>' . t("Uw gegevens") . '</p>',
        ];

        $form['volume_members'] = [
          '#type' => 'container',
          '#tree' => TRUE,
          '#title' => t('Members'),
          '#prefix' => '<div id="volume-members-wrapper">',
          '#suffix' => '</div>',
          '#weight' => 99,
        ];

        $volumeFields = $form_state->get('volume_fields');
        if (empty($volumeFields)) {
          $volumeFields = 1;
        }

        if (!empty($submission->get('field_members')->getValue())) {
          if ($volumeFields < count($submission->get('field_members')->getValue())) {
            $count = count($submission->get('field_members')->getValue());
            $volumeFields = ($count > 0) ? $count : 1;
          }
        }
        $form_state->set('volume_fields', $volumeFields);
        for ($i = 0; $i < $volumeFields; $i++) {
          $button = FALSE;
          $default = [
            'name' => '',
            'lastname' => '',
            'email' => '',
            'lunch' => '',
            'about_diet' => '',
            'about_member' => '',
          ];
          if (!empty($submission->get('field_members')->getValue()[$i])) {
            $paragraph = \Drupal::service('entity.manager')->getStorage('paragraph')->load($submission->get('field_members')->getValue()[$i]['target_id']);
            if (!empty($paragraph)) {
              $button = TRUE;
              $default['name'] = !empty($paragraph->get('field_name')->getValue()[0]['value']) ? $paragraph->get('field_name')->getValue()[0]['value'] : NULL;
              $default['lastname'] = !empty($paragraph->get('field_lastname')->getValue()[0]['value']) ? $paragraph->get('field_lastname')->getValue()[0]['value'] : NULL;
              $default['email'] = !empty($paragraph->get('field_email')->getValue()[0]['value']) ? $paragraph->get('field_email')->getValue()[0]['value'] : NULL;
              $default['lunch'] = !empty($paragraph->get('field_geef_aan_of_we_lunch_moete')->getValue()[0]['value']) ? $paragraph->get('field_geef_aan_of_we_lunch_moete')->getValue()[0]['value'] : NULL;
              $default['about_diet'] = !empty($paragraph->get('field_comment')->getValue()[0]['value']) ? $paragraph->get('field_comment')->getValue()[0]['value'] : NULL;
              $default['about_member'] = !empty($paragraph->get('field_about_member')->getValue()[0]['value']) ? $paragraph->get('field_about_member')->getValue()[0]['value'] : NULL;
            }
          }

          $form['volume_members'][$i] = [
            '#type' => 'container',
            '#tree' => TRUE,
            '#attributes' => [
              'class' => 'wrp_member',
            ],
          ];
//          dump($paragraph);
          if ($button) {
            $form['volume_members'][$i]['remove_item'] = [
              '#type' => 'submit',
              '#index' => !empty($submission->get('field_members')->getValue()[$i]['target_id']) ? $submission->get('field_members')->getValue()[$i]['target_id'] : NULL,
              '#submission' => $submission_id,
              '#value' => !empty($submission->get('field_members')->getValue()[$i]['target_id']) ? $submission->get('field_members')->getValue()[$i]['target_id'] : NULL,
              '#submit' => ['::submissionRemoveSubmit'],
              '#limit_validation_errors' => array(),
              '#action' => 'remove_item',
//              '#ajax' => [
//                'callback' => [$this, 'submissionValidateCallback'],
//                'wrapper' => 'volume-members-wrapper',
//              ],
            ];
          }
          else {
            $form['volume_members'][$i]['remove_item'] = [
              '#type' => 'submit',
              '#value' => t('Remove item'),
              '#submit' => ['::submissionDel'],
              '#limit_validation_errors' => array(),
//              '#ajax' => [
//                'callback' => [$this, 'submissionValidateCallback'],
//                'wrapper' => 'volume-members-wrapper',
//              ],
            ];
          }


          $form['volume_members'][$i]['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#default_value' => $default['name'],
            '#description' => '',
            '#required' => TRUE,
            '#attributes' => [
              'placeholder' => t('Vul voornaam deelnemer in'),
            ],
          ];
          $form['volume_members'][$i]['lastname'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last Name'),
            '#default_value' => $default['lastname'],
            '#description' => '',
            '#required' => TRUE,
            '#attributes' => [
              'placeholder' => t('Vul achternaam deelnemer in'),
            ],
          ];
          $form['volume_members'][$i]['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email address'),
            '#default_value' => $default['email'],
            '#description' => '',
            '#required' => TRUE,
            '#attributes' => [
              'placeholder' => t('e-mail'),
            ],
          ];
          $form['volume_members'][$i]['lunch'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Geef aan of we lunch moeten voorzien voor deze deelnemer'),
            '#default_value' => $default['lunch'],
            '#description' => '',
            '#required' => FALSE,
            '#access' => $with_lunch,
          ];
          $form['volume_members'][$i]['about_diet'] = [
            '#type' => 'textarea',
            '#title' => $this->t('About diet'),
            '#default_value' => $default['about_diet'],
            '#description' => '',
            '#required' => FALSE,
            '#attributes' => [
              'placeholder' => t('Moeten we rekening houden met allergieën, intoleranties,...?'),
            ],
            '#access' => $with_lunch,
          ];
          $form['volume_members'][$i]['about_member'] = [
            '#type' => 'textarea',
            '#title' => $this->t('About member'),
            '#default_value' => $default['about_member'],
            '#description' => '',
            '#required' => FALSE,
            '#attributes' => [
              'placeholder' => t('Indien er nog informatie is die relevant is voor deze deelnemer, dan kan u deze hier meegeven'),
            ],
            '#access' => $with_lunch,
          ];
        }

        $form['volume_members']['add_item'] = [
          '#type' => 'submit',
          '#value' => t('Deelnemer toevoegen'),
          '#submit' => ['::submissionSubmit'],
          '#ajax' => [
            'callback' => [$this, 'submissionValidateCallback'],
            'wrapper' => 'volume-members-wrapper',
          ],
        ];

        break;

      case 3 :
        $form['progress'] = [
          '#type' => 'markup',
          '#prefix' => '<ul class="submission-progress">',
          '#suffix' => '</ul>',
          '#markup' => $this->builbProgressBar($step),
        ];

        $renderer = \Drupal::service('renderer');

        $form['general'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Algemene info'),
          '#attributes' => ['class' => ['container-inline']],
        ];
        $items = [];
        $items[] = $this->t('E-mail: ') . $submission->get('email')->getValue()[0]['value'];
        $items[] = $this->t('Winkel / afdeling: ') . $submission->get('store_department')->getValue()[0]['value'];
        $items[] = $this->t('Winkel adres: ') . $submission->get('store_address')->getValue()[0]['value'];
        $general = [
          '#theme' => 'item_list',
          '#items' => $items,
        ];
        $form['general']['info'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="submission-details">',
          '#suffix' => '</div>',
          '#markup' => $renderer->render($general),
        ];

        $form['attendees'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Deelnemers'),
          '#attributes' => ['class' => ['container-inline']],
        ];

        $form['attendees']['info'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="submission-subtitle">',
          '#suffix' => '</div>',
          '#markup' => $this->t("Uw gegevens"),
        ];

        if (!empty($submission->get('field_members')->getValue())) {
          foreach ($submission->get('field_members')->getValue() as $delta => $item) {
            $form['attendees'][$delta] = [
              '#type' => 'fieldset',
//              '#title' => $this->t('Attendee'),
              '#attributes' => ['class' => ['container-inline']],
            ];

            $items = [];
            $paragraph = \Drupal::service('entity.manager')->getStorage('paragraph')->load($item['target_id']);
            $items[] = !empty($paragraph->get('field_name')->getValue()[0]['value']) ? t('Voornaam:') . ' ' . $paragraph->get('field_name')->getValue()[0]['value'] : NULL;
            $items[] = !empty($paragraph->get('field_lastname')->getValue()[0]['value']) ? t('Achternaam:') . ' ' . $paragraph->get('field_lastname')->getValue()[0]['value'] : NULL;
            $items[] = !empty($paragraph->get('field_email')->getValue()[0]['value']) ? t('E-mail:') . ' ' . $paragraph->get('field_email')->getValue()[0]['value'] : NULL;
            if ($with_lunch) {
              $items[] = !empty($paragraph->get('field_geef_aan_of_we_lunch_moete')->getValue()[0]['value']) ? t('Ik eet mee met de lunch:') . ' ' . $paragraph->get('field_geef_aan_of_we_lunch_moete')->getValue()[0]['value'] : NULL;
              $items[] = !empty($paragraph->get('field_comment')->getValue()[0]['value']) ? t('Opmerkingen over dieet:') . ' ' . $paragraph->get('field_comment')->getValue()[0]['value'] : NULL;
              $items[] = !empty($paragraph->get('field_about_member')->getValue()[0]['value']) ? t('Algemene opmerkingen:') . ' ' . $paragraph->get('field_about_member')->getValue()[0]['value'] : NULL;
            }
            $general = [
              '#theme' => 'item_list',
              '#items' => $items,
            ];
            $form['attendees'][$delta]['info'] = [
              '#type' => 'markup',
              '#prefix' => '<div class="submission-details">',
              '#suffix' => '</div>',
              '#markup' => $renderer->render($general),
            ];
          }
        }
        break;

      case 4:
        $form['progress'] = [
          '#type' => 'markup',
          '#prefix' => '<ul class="submission-progress">',
          '#suffix' => '</ul>',
          '#markup' => $this->builbProgressBar($step),
        ];

        $form['title'] = [
          '#type' => 'markup',
          '#prefix' => '<h2 class="submission-title">',
          '#suffix' => '</h2>',
          '#markup' => $this->t('Registered'),
        ];
        $form['info'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="submission-subtitle">',
          '#suffix' => '</div>',
          '#markup' => $this->t('Your registration has been saved. You will receive a confirmation email soon. You will also find the necessary information to adjust your registration if necessary.'),
        ];
        break;
    }

    $form['actions']['#type'] = 'actions';

    if ($step > 0 && $step < 4) {
      if ($step > 1) {
        $form['actions']['back'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Back'),
          '#button_type' => 'primary',
          '#action' => 'back',
        );
      }
      if ($step == 1) {
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Uw gegevens'),
          '#button_type' => 'primary',
          '#action' => 'next',
        );
      } elseif ($step == 2) {
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Valideren'),
          '#button_type' => 'primary',
          '#action' => 'next',
        );
      } else {
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Volgende'),
          '#button_type' => 'primary',
          '#action' => 'next',
        );
      }
      if ($submission_id != NULL) {
        $form['actions']['delete'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Annuleer mijn inschrijving'),
          '#button_type' => 'primary',
          '#action' => 'delete',
//          '#limit_validation_errors' => array(),
        );
      }
    }
    \Drupal::service('page_cache_kill_switch')->trigger();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submissionValidateCallback(&$form, FormStateInterface $form_state)
  {
    return $form['volume_members'];
  }

  /**
   * {@inheritdoc}
   */
  public function submissionSubmit(&$form, FormStateInterface $form_state)
  {
    $volumeFields = $form_state->get('volume_fields');
    $available = $form_state->get('available');
    $already_exist_members = $form_state->get('exist_members');
    if (($volumeFields - $already_exist_members) < $available) {
      $form_state->set('volume_fields', ($volumeFields + 1));
      $form_state->setRebuild();
    } else {
      \Drupal::messenger()->addWarning('You have reached the limit of participants.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submissionDel(&$form, FormStateInterface $form_state)
  {
    $volumeFields = $form_state->get('volume_fields');
    dump($volumeFields);
    $form_state->set('volume_fields', ($volumeFields - 1));
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submissionRemoveSubmit(&$form, FormStateInterface $form_state)
  {
    $index = $form_state->getTriggeringElement()['#index'];
    $submission_id = $form_state->getTriggeringElement()['#submission'];
    $values = $form_state->getValue(['volume_members']);

    foreach ($values as $value) {
      if (isset($value['target_id']) && $value['target_id'] === $index) {
        unset($value);
      }
    }
    $form_state->setValues(['volume_members'], array_values($values));

    $submission = \Drupal::service('entity.manager')->getStorage('submission')->load($submission_id);
    $members = $submission->get('field_members')->getValue();
    foreach ($members as $member) {
      if ($member['target_id'] === $index) {
        $entity = \Drupal::entityTypeManager()->getStorage('paragraph')->load($index);
        if ($entity) {
          $entity->delete();
        }
      }
      else {
        $new_members[] = $member;
      }
    }
    if (isset($new_members)) {
      $submission->set('field_members', $new_members);
      $submission->save();
    }

    $volumeFields = $form_state->get('volume_fields');
    if ($volumeFields > 1) {
      $volumeFields = $volumeFields - 1;
      $form_state->set('volume_fields', $volumeFields);
    }
      $form_state->setRebuild();
      \Drupal::cache('render')->deleteAll();

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $training = $form_state->getValue('training');
    $training_id = $training->id();
    $step = $form_state->getValue('step');
    $submission = $form_state->getValue('submission');

    if (!empty($form_state->get('remove_index'))) {
      $form_state->set('remove_index', NULL);
    }

    switch ($step) {
      case 1:
        $session = trainings_check_sesssion();
        $country_id = $session['countrylang'];
        $retailer_id = $session['retailer'];

        $submission->set('email', $form_state->getValue('email'));
        $submission->set('store_department', $form_state->getValue('store_department'));
        $submission->set('store_address', $form_state->getValue('store_address'));
        $submission->set('training_id', $training_id);
        $submission->set('country_id', $country_id);
        $submission->set('retailer_id', $retailer_id);
        $submission->save();
        break;

      case 2:
        $members = $this->createMembers($form_state->getValue('volume_members'));
        $submission->set('field_members', $members);
        $submission->save();
        break;
    }

    $submission_id = $submission->id();

    if (!empty($form_state->getTriggeringElement()['#action']) && $form_state->getTriggeringElement()['#action'] == 'back') {
      $next_step = $form_state->getValue('step') - 1;
      $parameters = ['training' => $training_id, 'submission' => $submission_id, 'step' => $next_step];
      $route_name = ($submission_id == NULL) ? 'submission.add_form' : 'submission.edit_form';
    } elseif (!empty($form_state->getTriggeringElement()['#action']) && $form_state->getTriggeringElement()['#action'] == 'next') {
      $next_step = $form_state->getValue('step') + 1;
      $parameters = ['training' => $training_id, 'submission' => $submission_id, 'step' => $next_step];
      $route_name = ($submission_id == NULL) ? 'submission.add_form' : 'submission.edit_form';
    } elseif (!empty($form_state->getTriggeringElement()['#action']) && $form_state->getTriggeringElement()['#action'] == 'delete') {
      // @todo create delete submission code here.
      $parameters = ['training' => $training_id, 'submission' => $submission_id];
      $route_name = 'submission.delete_form';
    }

    $form_state->setRedirect($route_name, $parameters);
  }

  /**
   * {@inheritdoc}
   */
  private function createMembers($volume_members = NULL)
  {
    $members = [];

    if ($volume_members) {
      foreach ($volume_members as $item) {
        if (is_array($item)) {
          $paragraph = Paragraph::create([
            'type' => 'member',
            'field_name' => [
              'value' => $item['name'],
            ],
            'field_lastname' => [
              'value' => $item['lastname'],
            ],
            'field_email' => [
              'value' => $item['email'],
            ],
            'field_geef_aan_of_we_lunch_moete' => [
              'value' => $item['lunch'] != 0 ? $item['lunch'] : NULL,
            ],
            'field_comment' => [
              'value' => $item['about_diet'],
            ],
            'field_about_member' => [
              'value' => $item['about_member'],
            ],
          ]);
          $paragraph->isNew();
          $paragraph->save();

          $members[] = array(
            'target_id' => $paragraph->id(),
            'target_revision_id' => $paragraph->getRevisionId(),
          );
        }
      }
    }

    return $members;
  }

  /**
   * {@inheritdoc}
   */
  private function builbProgressBar($step = NULL)
  {
    $step_1 = '';
    $step_2 = '';
    $step_3 = '';
    $step_4 = '';

    switch ($step) {
      case 1:
        $step_1 = 'active';
        break;
      case 2:
        $step_2 = 'active';
        break;
      case 3:
        $step_3 = 'active';
        break;
      case 4:
        $step_4 = 'active';
        break;
    }

    $progress_bar = '	
      <li class="progress-step ' . $step_1 . '">
        <span class="progress-marker">1</span>
        <span class="progress-text">' . t('Algemene info') . '</span>
      </li>
      <li class="progress-step ' . $step_2 . '">
        <span class="progress-marker">2</span>
        <span class="progress-text">' . t('Deelnemers') . '</span>
      </li>
      <li class="progress-step ' . $step_3 . '">
        <span class="progress-marker">3</span>
        <span class="progress-text">' . t('Valideren') . '</span>
      </li>
      <li class="progress-step ' . $step_4 . '">
        <span class="progress-marker">4</span>
        <span class="progress-text">' . t('Bevestiging van inschrijving') . '</span>
      </li>';

    return $progress_bar;
  }

}
