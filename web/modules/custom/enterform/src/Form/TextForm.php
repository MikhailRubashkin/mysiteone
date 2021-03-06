<?php

namespace Drupal\enterform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\Plugin\Mail;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailManager;



class TextForm extends ConfigFormBase {


    public $properties = [];
    protected $firstName;
    protected $lastName;
    protected $subject;
    protected $message;
    protected $email;



  protected function getEditableConfigNames() {
   return [
     'textform.adminsettings',
   ];
 }

  public function getFormId() {
    return 'enter_text_form';
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('textform.adminsettings');


    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please enter the First Name, Last Name, Subject,
      Message end Email the terms of use of the site.'),
    ];

    $form['firstName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $config->get('firstName'),
      '#required' => TRUE,
    ];

    $form['lastName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $config->get('lastName'),
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Please note that the subject line must be no more than 100 characters long.'),
      '#default_value' => $config->get('subject'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Note that the message must be at least 10 characters in length.'),
      '#default_value' => $config->get('message'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Please note that the email must be no more than 80 characters.'),
      '#default_value' => $config->get('email'),
      '#required' => TRUE,
    ];

   $form['accept'] = [
      '#type' => 'checkbox',
      '#firstName' => $this
        ->t('I accept the terms of use of the site'),
      '#description' => $this->t('Please read and accept the terms of use'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }



  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $firstName = $form_state->getValue('firstName');
    $lastName = $form_state->getValue('lastName');
    $subject = $form_state->getValue('subject');
    $message = $form_state->getValue('message');
    $email = $form_state->getValue('email');
    $accept = $form_state->getValue('accept');


    if (strlen($firstName) > 30)   {
      $form_state->setErrorByName('firstName', $this->t('The First Name must be no more than 30 characters..'));
    }

    if (strlen($lastName) > 30)   {
      $form_state->setErrorByName('lastName', $this->t('The Last Name must be no more than 30 characters.'));
    }

    if (strlen($subject) > 100)   {
      $form_state->setErrorByName('subject', $this->t('The Subject must be no more than 30 characters.'));
    }

    if (strlen($message) < 10)   {
      $form_state->setErrorByName('message', $this->t('The Message must be at least 10 characters long.'));
    }

    if (strlen($email) > 80)   {
      $form_state->setErrorByName('email', $this->t('The Email must be no more than 30 characters.'));
    }

    if (!$form_state->getValue('email') || !filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('email', $this->t('Votre adresse e-mail semble invalide.'));
    }

    if (empty($accept)) {
      $form_state->setErrorByName('accept', $this->t('You must accept the terms of use to continue'));
    }
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {

                    $config = \Drupal::config('textform.adminsettings');
                    $firstName = $form_state->getValue('firstName');
                    $lastName = $form_state->getValue('lastName');
                    $subject = $form_state->getValue('$subject');
                    $message = $form_state->getValue('message');
                    $email = $form_state->getValue('email');

                    $module = 'admin@mail.ru';
                    $key = 'contact_submit';
                    $to = \Drupal::config('system.site')->get('mail');
                    $params['subject'] = $subject;
                    $params['message'] = $message;
                    $langcode = \Drupal::currentUser()->getPreferredLangcode();

                    $result = \Drupal::service('plugin.manager.mail')
                    ->mail($module, $key, $to, $langcode, $params, $reply = NULL, $send = TRUE);

                    if ($result['send'] !== true) {
                      drupal_set_message($this->t('There was a problem sending your message or your email is not correct.'), 'error');
                    }
                    else {
                      drupal_set_message(t('You will receive a confirmation email shortly.'));
                      $this->config('textform.adminsettings')
                      ->set('firstName', $form_state->getValue('firstName'))
                      ->set('lastName', $form_state->getValue('lastName'))
                      ->set('subject', $form_state->getValue('subject'))
                      ->set('message', $form_state->getValue('message'))
                      ->set('email', $form_state->getValue('email'))
                      ->save();
                      drupal_set_message(t('Added message log entry.'));
                      $query = \Drupal::database()->insert('enterform');
                      $query->fields([
                      'firstName' => $firstName,
                      'lastName' => $lastName,
                      'subject' => $subject,
                      'message' => $message,
                      'email' => $email,
                      ]);
                      $query->execute();
                      parent::submitForm($form, $form_state);
                    }

                    function module_mail($key, &$message, $params) {

                        $options = [
                          'langcode' => $message['langcode']
                        ];

                        switch($key) {
                            case 'contact_submit':
                                $message['from'] = \Drupal::currentUser()->getEmail();
                                $message['subject'] = t('Enquiry from: @subject', ['@subject' => $params['subject']], $options);
                                $message['body'][] = $params['message'];
                                break;
                        }
                    }


            $data = array(
              'properties' => [
                [
                  'property' => 'firstName',
                  'value' => $firstName
                ],
                [
                  'property' => 'lastName',
                  'value' => $lastName
                ],
                [
                  'property' => 'subject',
                  'value' => $subject
                ],
                [
                  'property' => 'message',
                  'value' => $message
                ],
                [
                  'property' => 'email',
                  'value' => $email
                ]
              ]
            );


                    $opts = array('http' =>
                      array(
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json; charset=utf-8\r\n",
                        'content' => json_encode($data,TRUE)
                      )
                    );

                    $context  = stream_context_create($opts);
                    $url = 'https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/'.$email.'/?hapikey=d4abb0f3-ee51-4670-80ca-************';
                    $res = file_get_contents($url, NULL, $context);
                    if($res) {
                    drupal_set_message('Contact successfully created on Hubspot !!!!');
                     }else {
                       drupal_set_message($this->t('There was a problem sending hubspot message'), 'error');
                     }
    $form_state->setRedirect('<front>');
  }
}
