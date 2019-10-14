<?php

namespace Drupal\disclaimer\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'DisclaimerBlock' block.
 *
 * @Block(
 *  id = "disclaimer_block",
 *  admin_label = @Translation("Disclaimer block"),
 * )
 */
class DisclaimerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected $disclaimerService;


  /**
   * Overrides Drupal\Core\BlockBase::__construct().
   *
   * Creates a DisclaimerBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PathValidatorInterface $path_validator, $disclaimer_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->pathValidator = $path_validator;
    $this->disclaimerService = $disclaimer_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.validator'),
      $container->get('disclaimer.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'cookies:disclaimer_' . $this->configuration['machine_name'],
      'url.path',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->configuration['max_age'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'machine_name' => 'disclaimer_block_' . time(),
      'disclaimer' => NULL,
      'max_age' => 86400,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $disclaimers = $this->disclaimerService->getDisclaimerList();
//    $storage = \Drupal::entityTypeManager()->getStorage('disclaimer');
//    $disclaimer = $storage->load($this->configuration['disclaimer']);
//    kint($disclaimer->get('agree')->first()->getValue()['value']);die;



    $form['disclaimer'] = [
      '#type' => 'select',
      '#title' => $this->t('Disclaimer'),
      '#options' => $disclaimers,
      '#default_value' => $this->configuration['disclaimer'],
      '#required' => TRUE,
    ];

    $form['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max-age'),
      '#description' => $this->t('The time in seconds the user is confirmed. Set to 0 for no expiry. (86400 seconds = 24 hours)'),
      '#default_value' => $this->configuration['max_age'],
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if (!preg_match('/^[0-9]+$/', $form_state->getValue('max_age'))) {
      $form_state->setErrorByName('max_age', $this->t('Max-age must be integer.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Form\SubformStateInterface $form_state */
    $this->configuration['machine_name'] = $form_state->getCompleteFormState()->getValue('id');
    $this->configuration['max_age'] = $form_state->getValue('max_age');
    $this->configuration['disclaimer'] = $form_state->getValue('disclaimer');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $storage = \Drupal::entityTypeManager()->getStorage('disclaimer');
    $disclaimer = $storage->load($this->configuration['disclaimer']);

    if (!empty($disclaimer)) {
      $redirect = !empty($disclaimer->get('redirect')->first())
        ? $disclaimer->get('redirect')->first()->getValue()['value'] : NULL;
      $agree = !empty($disclaimer->get('agree')->first())
        ? $disclaimer->get('agree')->first()->getValue()['value']: NULL;
      $disagree = !empty($disclaimer->get('disagree')->first())
        ? $disclaimer->get('disagree')->first()->getValue()['value'] : NULL;
      $challenge = !empty($disclaimer->get('challenge')->first())
        ? $disclaimer->get('challenge')->first()->getValue()['value']: NULL;
      $text = !empty($disclaimer->get('disclaimer')->first())
        ? $disclaimer->get('disclaimer')->first()->getValue()['value'] : NULL;

      $disclaimer_id = 'disclaimer_' . Html::escape($this->configuration['machine_name']);

      // Identify block by class with machine name.
      $build = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            $disclaimer_id,
            'disclaimer__noscript',
          ],
        ],
      ];

      // Include JS to handle popup and hiding.
      $build['#attached']['library'][] = 'disclaimer/disclaimer';
      // Pass settings to JS.
      $build['#attached']['drupalSettings']['disclaimer'][$disclaimer_id] = [
        'redirect' => $redirect,
        'max_age' => Html::escape($this->configuration['max_age']),
        'agree' => Html::escape($agree),
        'disagree' => Html::escape($disagree),
      ];

      // Render disclaimer.
      $build['disclaimer_block_disclaimer'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'disclaimer__disclaimer',
          ],
        ],
        '#markup' => $text,
      ];

      // Render popup HTML.
      $build['disclaimer_block_challenge'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'disclaimer__challenge',
            'hidden',
          ],
          'title' => [
            Html::escape($this->label()),
          ],
        ],
        '#markup' => $challenge,
      ];
    }

    return $build;
  }

}
