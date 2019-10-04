<?php

namespace Drupal\mollo_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MolloEmailSettingsForm extends ConfigFormBase
{


  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'mollo_email_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'mollo_email.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Load Settings
    $config = $this->config('mollo_email.settings');


    // Fieldset General
    // -------------------------------------------------------------
    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
      '#attributes' => ['class' => ['settings-general']],
    ];


    // Fieldset Imap
    // -------------------------------------------------------------
    $form['imap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('IMAP Settings'),
      '#attributes' => ['class' => ['email-settings']],
    ];

    // - Email From
    $form['imap']['imap_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User: (admin@example.com)'),
      '#default_value' => $config->get('imap_user'),
    );

    // - Passwort
    $form['imap']['imap_password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('imap_password'),
    );

    // - IMAP Server
    $form['imap']['imap_server'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Imap Server'),
      '#default_value' => $config->get('imap_server'),
    );





    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {


    // Retrieve the configuration
    $this->configFactory->getEditable('mollo_email.settings')
      //
      //
      // Fieldset IMAP
      // -------------------------------------------------------------
      // - Email From
      ->set('imap_user', $form_state->getValue('imap_user'))
      // - Email to
      ->set('imap_password', $form_state->getValue('imap_password'))
      // - Email Test
      ->set('imap_server', $form_state->getValue('imap_server'))
      //
      //

      ->save();

    //  Twig Templates
    // -------------------------------------------------------------
    $config = $this->configFactory->getEditable('mollo_email.settings');

    $config->save();

    parent::submitForm($form, $form_state);
  }
}
