<?php

/**
 * @file
 * Contains \Drupal\scheduler\Form\SchedulerAdminForm.
 */

namespace Drupal\scheduler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Main administration form for the Scheduler module.
 */
class SchedulerAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduler_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['scheduler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $now = t('Example: %date', ['%date' => format_date(REQUEST_TIME, 'custom', \Drupal::config('scheduler.settings')->get('date_format'))]);
    $form['date_format'] = [
      '#type' => 'textfield',
      '#title' => t('Date format'),
      '#default_value' => \Drupal::config('scheduler.settings')->get('date_format'),
      '#size' => 20,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#field_suffix' => ' <small>' . $now . '</small>',
      '#description' => t('The format for entering scheduled dates and times. For the date use the letters !date_letters and for the time use !time_letters. See !url for more details.', [
        '!date_letters' => SCHEDULER_DATE_LETTERS,
        '!time_letters' => SCHEDULER_TIME_LETTERS,
        '!url' => l(t('the PHP date() function'), 'http://www.php.net/manual/en/function.date.php')
      ]),
    ];

    $form['field_type'] = [
      '#type' => 'radios',
      '#title' => t('Field type'),
      '#default_value' => \Drupal::config('scheduler.settings')->get('field_type'),
      '#options' => [
        'textfield' => t('Standard text field'),
        'date_popup' => t('Date Popup calendar'),
      ],
      '#description' => t('Date Popup is enabled. See the !date_popup_config for details.', ['!date_popup_config' => l(t('configuration page'), 'admin/config/date/date_popup')]),
    ];

    if (!\Drupal::moduleHandler()->moduleExists('date_popup')) {
      $form['field_type']['#default_value'] = 'textfield';
      $form['field_type']['#disabled'] = TRUE;
      $form['field_type']['#description'] = t('To use the calendar you need to enable Date, Date API and Date Popup. Download the module from the !url.', ['!url' => l(t('Date project page'), 'http://drupal.org/project/date')]);
    }

    // Variable 'date_popup_timepicker' holds the type of timepicker selected.
    $timepicker_enabled = (variable_get('date_popup_timepicker', '') != 'none');
    $options = ['@date_popup_config' => url('admin/config/date/date_popup')];
    $description = t('Restrict the time entry to specific minute increments.') . ' '
      . ($timepicker_enabled
      ? t('The timepicker type can be selected via the <a href="@date_popup_config">Date Popup configuration page</a>.', $options)
      : t('The timepicker is not enabled - turn it on via the <a href="@date_popup_config">Date Popup configuration page</a>.', $options));
    $form['date_popup_minute_increment'] = [
      '#type' => 'textfield',
      '#title' => t('Date Popup minute increment'),
      '#description' => $description,
      '#field_suffix' => t('minutes'),
      '#size' => 2,
      '#maxlength' => 2,
      '#disabled' => !$timepicker_enabled,
      '#default_value' => \Drupal::config('scheduler.settings')->get('date_popup_minute_increment'),
      '#element_validate' => ['element_validate_integer_positive'],
      '#states' => [
        'visible' => [
          ':input[name="scheduler_field_type"]' => ['value' => 'date_popup'],
        ],
      ],
    ];

    // Options for setting date-only with default time.
    $form['date_only_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Date only'),
      '#collapsible' => FALSE,
    ];
    $form['date_only_fieldset']['allow_date_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to enter only a date and provide a default time.'),
      '#default_value' => \Drupal::config('scheduler.settings')->get('allow_date_only'),
      '#description' => t('When only a date is entered the time will default to a specified value, but the user can change this if required.'),
    ];
    $form['date_only_fieldset']['default_time'] = [
      '#type' => 'textfield',
      '#title' => t('Default time'),
      '#default_value' => \Drupal::config('scheduler.settings')->get('default_time'),
      '#size' => 20,
      '#maxlength' => 20,
      '#description' => t('This is the time that will be used if the user does not enter a value. Format: HH:MM:SS.'),
      '#states' => [
        'visible' => [
          ':input[name="scheduler_allow_date_only"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['extra_info'] = [
      '#type' => 'textarea',
      '#title' => t('Extra Info'),
      '#default_value' => \Drupal::config('scheduler.settings')->get('extra_info'),
      '#description' => t('The text entered into this field will be displayed above the scheduling fields in the node edit form.'),
    ];

    // Add a submit handler function.
    $form['#submit'][] = 'admin_submit';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Replace all contiguous whitespaces (including tabs and newlines) with a
    // single plain space.
    $form_state->setValue(['date_format'], trim(preg_replace('/\s+/', ' ', $form_state->getValue(['date_format']))));

    // Validate the letters used in the scheduler date format. All punctuation
    // is accepted, so remove everything except word characters then check that
    // there is nothing else which is not in the list of acceptable date/time
    // letters.
    $no_punctuation = preg_replace('/[^\w+]/', '', $form_state->getValue(['date_format']));
    if (preg_match_all('/[^' . SCHEDULER_DATE_LETTERS . SCHEDULER_TIME_LETTERS . ']/', $no_punctuation, $extra)) {
      $form_state->setErrorByName('date_format', t('You may only use the letters $date_letters for the date and $time_letters for the time. Remove the extra characters $extra', [
        '$date_letters' => SCHEDULER_DATE_LETTERS,
        '$time_letters' => SCHEDULER_TIME_LETTERS,
        '$extra' => implode(' ', $extra[0]),
      ]));
    };

    $time_format = $this->getTimeOnlyFormat($form_state->getValue(['date_format']));

    if ($form_state->getValue(['field_type']) == 'date_popup') {
      // The Date Popup function date_popup_time_formats() only returns the
      // values 'H:i:s' and 'h:i:sA' but Scheduler can accept more variations
      // than just these. Firstly, we add the lowercase 'a' alternative.
      // Secondly timepicker always requires hours and minutes, but seconds are
      // optional.
      $acceptable = ['H:i:s', 'h:i:sA', 'h:i:sa', 'H:i', 'h:iA', 'h:ia'];

      if ($time_format && !in_array($time_format, $acceptable)) {
        $form_state->setErrorByName('date_format', t('When using the Date Popup module, the allowed time formats are: !formats', ['!formats' => implode(', ', $acceptable)]));
      }
    }

    // If date-only is enabled then check if a valid default time was entered.
    // Leading zeros and seconds can be omitted, eg. 6:30 is considered valid.
    if ($form_state->getValue(['allow_date_only'])) {
      $default_time = date_parse($form_state->getValue(['default_time']));
      if ($default_time['error_count']) {
        $form_state->setErrorByName('default_time', t('The default time should be in the format HH:MM:SS'));
      }
      else {
        // Insert any possibly omitted leading zeroes.
        $unix_time = mktime($default_time['hour'], $default_time['minute'], $default_time['second']);
        $form_state->setValue(['default_time'], format_date($unix_time, 'custom', 'H:i:s'));
      }
    }

    // Check that either the date format has a time part or the date-only option
    // is turned on.
    if ($time_format == '' && !$form_state->getValue(['allow_date_only'])) {
      $form_state->setErrorByName('date_format', t('You must either include a time within the date format or enable the date-only option.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('scheduler.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    // For the minute increment, change a blank value to 1. Date popup does not
    // support blank values.
    if (empty($form_state->getValue(['date_popup_minute_increment']))) {
      $form_state->setValue(['date_popup_minute_increment'], 1);
    }

    // Extract the date part and time part of the full format, for use with the
    // default time functionality. Assume the date and time time parts begin and
    // end with a letter, but any punctuation between these will be retained.
    $format = $form_state->getValue(['date_format']);
    $time_only_format = $this->getTimeOnlyFormat($format);
    variable_set('time_only_format', $time_only_format);

    $date_only_format = $this->getDateOnlyFormat($format);
    variable_set('date_only_format', $date_only_format);

    if (empty($time_only_format)) {
      drupal_set_message(t('The date part of the Scheduler format is %date_part. There is no time part', ['%date_part' => $date_only_format]));
    }
    else {
      drupal_set_message(t('The date part of the Scheduler format is %date_part and the time part is %time_part.', ['%date_part' => $date_only_format, '%time_part' => $time_only_format]));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the time part of a date format.
   *
   * For example, when given the string 'Y-m-d H:s:i' it will return 'H:s:i'.
   *
   * @param string $format
   *   A date format compatible with the PHP date() function.
   *
   * @return string
   *   The time part of the date format, or an empty string if it does not
   *   contain a time part.
   */
  protected function getTimeOnlyFormat($format) {
    $time_start = strcspn($format, SCHEDULER_TIME_LETTERS);
    $time_length = strlen($format) - strcspn(strrev($format), SCHEDULER_TIME_LETTERS) - $time_start;
    return substr($format, $time_start, $time_length);
  }

  /**
   * Returns the date part of a date format.
   *
   * For example, when given the string 'Y-m-d H:s:i' it will return 'Y-m-d'.
   *
   * @param string $format
   *   A date format compatible with the PHP date() function.
   *
   * @return string
   *   The date part of the date format, or an empty string if it does not
   *   contain a date part.
   */
  protected function getDateOnlyFormat($format) {
    $date_start = strcspn($format, SCHEDULER_DATE_LETTERS);
    $date_length = strlen($format) - strcspn(strrev($format), SCHEDULER_DATE_LETTERS) - $date_start;
    return substr($format, $date_start, $date_length);
  }

}
