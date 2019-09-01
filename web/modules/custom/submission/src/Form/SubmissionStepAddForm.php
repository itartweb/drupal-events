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
class SubmissionStepAddForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'submission_entity_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $training = NULL)
  {
    // If the form doesn't have a page_num defined, define it here.
    $service = \Drupal::service('submission.custom_services');
    $number_participants = $service->getAmountMembersByTrainingId($training->id()) ? $service->getAmountMembersByTrainingId($training->id()) : 0;
    $limit = !empty($training->get('field_maximum_participants')->getValue()[0]['value']) ? $training->get('field_maximum_participants')->getValue()[0]['value'] : FALSE;
    $available = (int)$limit - (int)$number_participants;
    $form_state->set('available', $available);

    $with_lunch = $training->get('field_active_webform')->referencedEntities()[0]->id() == '50' ? TRUE : FALSE;
    $page_values = $form_state->get('page_values');
    if (!$form_state->has('step') || !$page_values) {
      $form_state->set('step', 1);
    }
    $step = $form_state->get('step');

    $form['training'] = [
      '#type' => 'value',
      '#value' => $training,
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
          '#default_value' => !empty($page_values['email']) ? $page_values['email'] : NULL,
          '#description' => '',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('e-mail'),
          ],
        ];

        $form['store_department'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Winkel / afdeling'),
          '#default_value' => !empty($page_values['store_department']) ? $page_values['store_department'] : NULL,
          '#description' => '',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('Vul uw winkel / afdeling in'),
          ],
        ];

        $form['store_address'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Winkeladres'),
          '#default_value' => !empty($page_values['store_address']) ? $page_values['store_address'] : NULL,
          '#description' => '',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => t('Vul het adres in van uw winkel of afdeling'),
          ],
        ];
        break;

      case 2:
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

        if (!empty($page_values['field_members'])) {
          if ($volumeFields < count($page_values['field_members'])) {
            $count = count($page_values['field_members']);
            $volumeFields = ($count > 0) ? $count : 1;
          }
        }
        $form_state->set('volume_fields', $volumeFields);

        for ($i = 0; $i < $volumeFields; $i++) {
          $default = [
            'name' => '',
            'lastname' => '',
            'email' => '',
            'lunch' => '',
          ];
          if (!empty($page_values['field_members'][$i])) {
            $default['name'] = !empty($page_values['field_members'][$i]['name']) ? $page_values['field_members'][$i]['name'] : NULL;
            $default['lastname'] = !empty($page_values['field_members'][$i]['lastname']) ? $page_values['field_members'][$i]['lastname'] : NULL;
            $default['email'] = !empty($page_values['field_members'][$i]['email']) ? $page_values['field_members'][$i]['email'] : NULL;
            $default['lunch'] = !empty($page_values['field_members'][$i]['lunch']) ? $page_values['field_members'][$i]['lunch'] : NULL;
          }

          $form['volume_members'][$i] = [
            '#type' => 'container',
            '#tree' => TRUE,
            '#attributes' => [
              'class' => 'wrp_member',
            ],
          ];

          $form['volume_members'][$i]['remove_item'] = [
            '#type' => 'submit',
            '#index' => $i,
            '#value' => $i,
            '#submit' => ['::submissionRemoveSubmit'],
            '#limit_validation_errors' => array(),
            '#action' => 'remove_item',
          ];

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
            '#title' => $this->t('Geef aan of we dinner moeten voorzien voor deze deelnemer'),
            '#default_value' => $default['lunch'],
            '#description' => '',
            '#required' => FALSE,
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
        $items[] = $this->t('E-mail: ') . $page_values['email'];
        $items[] = $this->t('Winkel / afdeling: ') . $page_values['store_department'];
        $items[] = $this->t('Winkel adres: ') . $page_values['store_address'];
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

        if (!empty($page_values['field_members'])) {
          foreach ($page_values['field_members'] as $delta => $item) {
            $form['attendees'][$delta] = [
              '#type' => 'fieldset',
              '#attributes' => ['class' => ['container-inline']],
            ];
            $items = [];
            $items[] = t('Voornaam:') . ' ' . $item['name'];
            $items[] = t('Achternaam:') . ' ' . $item['lastname'];
            $items[] = t('E-mail:') . ' ' . $item['email'];
            if ($with_lunch) {
              $item['lunch'] = $item['lunch'] === 1 ? t('ja') : t('nee');
              $items[] = t('Ik eet mee met de lunch:') . ' ' . $item['lunch'];
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

        $form['info'] = [
          '#type' => 'markup',
          '#prefix' => '<div class="submission-subtitle">',
          '#suffix' => '</div>',
          '#markup' => $this->t('Uw inschrijving is bewaard. U ontvangt spoedig een bevestigingsmail. Hierin vind u ook de nodige informatie om uw inschrijving indien nodig aan te passen.'),
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
    }

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

    if ($volumeFields < $available) {
      $form_state->set('volume_fields', ($volumeFields + 1));
      $form_state->setRebuild();
    } else {
      \Drupal::messenger()->addWarning('You have reached the limit of participants.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submissionRemoveSubmit(&$form, FormStateInterface $form_state)
  {
    $index = $form_state->getTriggeringElement()['#index'];
    $user_input = $form_state->getUserInput();
    $volumeFields = $form_state->get('volume_fields');
    $page_values = $form_state->get('page_values');
    if ($volumeFields > 1) {
      foreach ($user_input['volume_members'] as $k => $v) {
        if ($k !== $index) {
          $new_user_input[] = $v;
        }
      }

      $user_input['volume_members'] = $new_user_input;
      $form_state->setUserInput($user_input);

      if (isset($page_values['field_members'])) {
        foreach ($page_values['field_members'] as $k => $v) {
          if ($k !== $index) {
            $new_page_values[] = $v;
          }
        }
        $page_values['field_members'] = $new_page_values;
        $form_state->set('page_values', $page_values);
      }

      $volumeFields = $volumeFields - 1;
      $form_state->set('volume_fields', $volumeFields);
      $form_state->setRebuild();
      \Drupal::cache('render')->deleteAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // Load the submitted values.
    $page_values = $form_state->get('page_values');
    $submitted_values = $form_state->getValues();

    if ($page_values) {
      // Put all the paged values back into the form_state values.
      foreach ($page_values as $key => $value) {
        $submitted_values[$key] = $value;
      }
    }

    // Save the form_state values for further processing.
    $form_state->setValues($submitted_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $training = $form_state->getValue('training');
    $training_id = $training->id();
    $step = $form_state->getValue('step');
    $volumeFields = $form_state->get('volume_fields');
    $available = $form_state->get('available');

    if (!empty($form_state->get('remove_index'))) {
      $form_state->set('remove_index', NULL);
    }

    // Setup our page values variable.
    if (!$form_state->get('page_values')) {
      $page_values = [];
    }
    else {
      $page_values = $form_state->get('page_values');
    }

    switch ($step) {
      case 1:
        $session = trainings_check_sesssion();
        $country_id = $session['countrylang'];
        $retailer_id = $session['retailer'];

        $page_values['email'] = $form_state->getValue('email');
        $page_values['store_department'] = $form_state->getValue('store_department');
        $page_values['store_address'] = $form_state->getValue('store_address');
        $page_values['training_id'] = $training_id;
        $page_values['country_id'] = $country_id;
        $page_values['retailer_id'] = $retailer_id;
        break;

      case 2:
        $members = $form_state->getValue('volume_members');
        unset($members['add_item']);
        unset($members['remove_item']);
        $page_values['field_members'] = $members;
        break;

      case 3:
        if (!empty($form_state->getTriggeringElement()['#action']) && $form_state->getTriggeringElement()['#action'] == 'next') {
          $submission = \Drupal::service('entity.manager')->getStorage('submission')->create();
          $submission->set('email', $page_values['email']);
          $submission->set('store_department', $page_values['store_department']);
          $submission->set('store_address', $page_values['store_address']);
          $submission->set('training_id', $page_values['training_id']);
          $submission->set('country_id', $page_values['country_id']);
          $submission->set('retailer_id', $page_values['retailer_id']);

          $members = $this->createMembers($page_values['field_members']);
          $submission->set('field_members', $members);

          $submission->save();
        }
        break;
    }

    if (!empty($form_state->getTriggeringElement()['#action']) && $form_state->getTriggeringElement()['#action'] == 'back') {
      $next_step = $form_state->getValue('step') - 1;
      $form_state->set('step', $next_step);
      $parameters = ['training' => $training_id];
      $route_name = 'submission.add_form';
    }
    elseif (!empty($form_state->getTriggeringElement()['#action']) && $form_state->getTriggeringElement()['#action'] == 'next') {
      if ($volumeFields <= $available) {
        $next_step = $form_state->getValue('step') + 1;
        $form_state->set('step', $next_step);
        $parameters = ['training' => $training_id];
        $route_name = 'submission.add_form';
      }
      else {
        \Drupal::messenger()->addWarning('You have reached the limit of participants.');
      }
    }
    if (isset($parameters) && isset($route_name)) {
      $form_state->set('page_values', $page_values);
      $form_state->setRebuild(TRUE);
      $form_state->setRedirect($route_name, $parameters);
    }

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
