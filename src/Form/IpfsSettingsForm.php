<?php

namespace Drupal\ipfs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure IPFS settings for this site.
 *
 * @internal
 */
class IpfsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipfs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ipfs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ipfs.settings');

    $form['ipfs_gateway_type'] = [
      '#type' => 'radios',
      '#title' => t('IPFS Gateway Type'),
      '#default_value' => $config->get('ipfs_gateway_type'),
      '#options' => ['fission' => $this->t('Fission'), 'ipfs' => $this->t('IPFS')],
      '#description' => $this->t('Which type of IPFS Gateway to use.'),
    ];

    $form['ipfs_host'] = [
      '#type' => 'url',
      '#title' => $this->t("IPFS API Server"),
      '#description' => $this->t("The URL of the IPFS API server, for example: http://127.0.0.1:5001."),
      '#required' => FALSE,
      '#default_value' => $config->get('ipfs_host'),
      '#states' => [
        'visible' => [
          ':input[name=ipfs_gateway_type]' => ['value' => 'ipfs'],
        ],
      ],
    ];

    $form['fission'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fission settings'),
      '#states' => [
        'visible' => [
          ':input[name=ipfs_gateway_type]' => ['value' => 'fission'],
        ],
      ],
    ];

    $form['fission']['fission_gateway'] = [
      '#type' => 'url',
      '#title' => $this->t("Fission Gateway"),
      '#description' => $this->t("The URL of the Fission Gateway, for example: https://ipfs.runfission.com."),
      '#default_value' => $config->get('fission_gateway')
    ];
    $form['fission']['fission_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Username"),
      '#description' => $this->t("Your Fission username."),
      '#default_value' => $config->get('fission_username')
    ];
    $form['fission']['fission_password'] = [
      '#type' => 'password',
      '#title' => $this->t("Password"),
      '#description' => $this->t("Your Fission password."),
      '#default_value' => $config->get('fission_password')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ipfs.settings')
      ->set('ipfs_gateway_type', $form_state->getValue('ipfs_gateway_type'))
      ->set('ipfs_host', $form_state->getValue('ipfs_host'))
      ->set('fission_gateway', $form_state->getValue('fission_gateway'))
      ->set('fission_username', $form_state->getValue('fission_username'));

    if ($form_state->getValue('fission_password')) {
      $config->set('fission_password', $form_state->getValue('fission_password'));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
