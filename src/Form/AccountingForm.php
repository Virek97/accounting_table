<?php


namespace Drupal\accounting_table\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AccountingForm
 *
 * @package Drupal\accounting_table\Form
 */
class AccountingForm extends FormBase {

  protected $monthsList = [
    ['Jan', 'Feb', 'Mar'],
    ['Apr', 'May', 'Jun'],
    ['Jul', 'Aug', 'Sep'],
    ['Oct', 'Nov', 'Dec'],
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'accounting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_year = date('Y');

    $form['#prefix'] = "<div id='table_wrapper'>";
    $form['#suffix'] = "</div>";

    $form['#attached']['library'][] = 'accounting_table/scripts_accounting_table_home';

    $form = $this->tableRender($form,'accounting_1', 0, $current_year, 1);

    if ($form_state->get('table_id')) {
      for ($i = 1; $i <= $form_state->get('table_id'); $i++) {
        $table_name = 'accounting_' . $i;

        $form_state_table_rows = count($form_state->getValue($table_name)) - 1;
        if ($form_state_table_rows == 0) {
          $form = $this->tableRender($form, $table_name, $form_state_table_rows, $current_year, $i);
        } else {
          $year = date('Y') - $form_state_table_rows;
          $form = $this->tableRender($form, $table_name, $form_state_table_rows, $year, $i);
        }

        if ($form_state->getTriggeringElement()['#name'] == "add_year_{$i}") {
          $last_year = $form_state->getTriggeringElement()['#last_year'] - 1;
          $form_state->set('last_year', $last_year);
          $table_rows = date('Y') - $last_year;

          $form = $this->tableRender($form, $table_name, $table_rows, $last_year, $i);
        }
      }
    } else {
      $form_state->set('table_id', 1);
    }

    if ($form_state->getTriggeringElement()['#name'] == "add_table") {
      $table_id = $form_state->getTriggeringElement()['#table_id'] + 1;
      $form_state->set('table_id', $table_id);
      $table_name = 'accounting_' . $table_id;

      $form = $this->tableRender($form, $table_name, 0, $current_year, $table_id);
    }

    $form['actions']['add_table'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Table'),
      '#name' => 'add_table',
      '#table_id' => $form_state->get('table_id'),
      '#ajax' => [
        'callback' => '::submitAjaxCallback',
        'event' => 'click',
        'wrapper' => 'table_wrapper',
      ]
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Method for rendering tables
   */
  public function tableRender($form, $table_name, $table_rows, $last_year, $table_id) {
    $fields_list = ['Year', 'Jan', 'Feb', 'Mar', 'Q1', 'Apr', 'May', 'Jun', 'Q2', 'Jul', 'Aug', 'Sep', 'Q3', 'Oct', 'Nov', 'Dec', 'Q4', 'YTD'];
    $actions_name = "action_{$table_id}";

    $form[$actions_name]['addYear'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Year'),
      '#name' => 'add_year_' . $table_id,
      '#last_year' => $last_year,
      '#ajax' => [
        'callback' => '::submitAjaxCallback',
        'event' => 'click',
        'wrapper' => 'table_wrapper',
      ]
    ];

    $form[$table_name] = [
      '#type' => 'table',
      '#title' => 'Sample Table',
      '#header' => $fields_list,
    ];

    for ($j = 0; $j <= $table_rows; $j++) {
      $qtd = 1;
      for ($i = 0; $i < count($fields_list); $i++) {
        if ($fields_list[$i] == 'Year') {
          $form[$table_name][$j]['Year'] = array(
            '#type' => 'textfield',
            '#size' => 4,
            '#attributes' => array('readonly' => 'readonly'),
            '#default_value' => $last_year,
          );
        } elseif ($fields_list[$i] == 'Q1' || $fields_list[$i] == 'Q2' || $fields_list[$i] == 'Q3' || $fields_list[$i] == 'Q4') {
          $qtd++;
          $form[$table_name][$j][$fields_list[$i]] = [
            '#type' => 'number',
            '#prefix' => "<div class='table-field-$j-$fields_list[$i]-$table_name black-field'>",
            '#suffix' => "</div>",
            '#step' => '0.01',
            '#attributes' => [
              'class' => ['qtd-input'],
              'data-qtd' => $fields_list[$i],
            ]
          ];
        } elseif ($fields_list[$i] == 'YTD') {
          $form[$table_name][$j][$fields_list[$i]] = [
            '#type' => 'number',
            '#prefix' => "<div class='table-field-YTD-$j-$table_name'>",
            '#suffix' => "</div>",
            '#attributes' => [
              'class' => ['year-input'],
            ],
            '#step' => '0.01',
          ];
        } else {
          $form[$table_name][$j][$fields_list[$i]] = [
            '#type' => 'number',
            '#step' => '0.01',
            '#attributes' => [
              'class' => ['month-input'],
              'data-month' => $i,
              'data-qtd' => 'Q' . $qtd,
            ]
          ];
        }
      }
    }

    return $form;
  }

  /**
   * Ajax callback to display updated form
   */
  public function submitAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $first_table_info = [];
    $tables_validate_res = TRUE;
    $form_state->set('validate_error', FALSE);

    if ($form_state->getTriggeringElement()['#name'] == "submit") {
      for ($i = 1; $i <= $form_state->get('table_id'); $i++) {
        $table_name = 'accounting_' . $i;

//        The number of rows in the table
        $form_state_table_rows = count($form_state->getValue($table_name));

//        Determination of the first and last filled months
        $first_month = $this->determiningMonthPosition($form, $table_name, $form_state_table_rows);
        $last_month = $this->determiningMonthPosition($form, $table_name, $form_state_table_rows, 'last_month');

//        If nothing is filled, then we display an error
        if (!$first_month || !$last_month) {
          $form_state->set('validate_error', TRUE);
          $form_state->setErrorByName('error', $this->t('Invalid'));

          return $form;
        }

//        We write the first and last filled months for further verification of the tables
        if ($i == 1) {
          $first_table_info['first_month'] = $first_month;
          $first_table_info['last_month'] = $last_month;
        }

//        Checking tables for similarity
        if ($i > 1) {
          $tables_validate_res = $this->validateTablesIdentical($first_table_info['first_month'], $first_month) && $this->validateTablesIdentical($first_table_info['last_month'], $last_month);
        }

//        If the tables are different, an error is displayed
//        If not, validation of all filled fields is performed
        if (!$tables_validate_res) {
          $form_state->set('validate_error', TRUE);
          $form_state->setErrorByName('error', $this->t('Invalid'));

          return $form;
        } else {
          $res = $this->validateAllQuarters($form, $first_month, $last_month, $table_name);
        }

//        If previous validations have been successful, changes in quarter and year values are validated
        if ($res) {
          $res = $this->validateQuartersAndYearsChanged($form, $table_name, $last_month['row_number']);
        }

//        If there is a validation error, a message is displayed
        if (!$res) {
          $form_state->set('validate_error', TRUE);
          $form_state->setErrorByName('error', $this->t('Invalid'));
        }
      }
    }
  }

  /**
   * Method for finding the first and last months
   */
  public function determiningMonthPosition($form, $table_name, $last_table_row, $month_position = 'first_month') {
    $first_month = FALSE;

    for ($k = 0; $k < $last_table_row; $k++) {
      for ($i = 0; $i < count($this->monthsList); $i++) {
        for ($j = 0; $j < count($this->monthsList[$i]); $j++) {
          $month_name = $this->monthsList[$i][$j];
          $month_data = $form[$table_name][$k][$month_name]['#value'];

          if (!is_null($month_data) && $month_data != "") {
            $first_month = [
              'quarter_number' => $i,
              'month_number' => $j,
              'row_number' => $k,
            ];

            if ($month_position == 'first_month') {
              return $first_month;
            }
          }
        }
      }
    }

    return $first_month;
  }

  /**
   * Method for comparing the filled periods of time in all tables
   */
  public function validateTablesIdentical($arr1, $arr2) {
    $array_keys = ['quarter_number', 'month_number', 'row_number',];

    for ($i = 0; $i <= 2; $i++) {
      if ($arr1[$array_keys[$i]] != $arr2[$array_keys[$i]]) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Method for validation of filling of months and quarters
   */
  public function validateAllQuarters($form, $first_month, $last_month, $table_name) {
    $one_value_in_table = $this->validateTablesIdentical($first_month, $last_month);

    // If there is only one value in the table and it is valid, TRUE is returned
    if ($one_value_in_table) {
      if ($form[$table_name][$first_month['row_number']][$this->monthsList[$first_month['quarter_number']][$first_month['month_number']]]['#value'] != "") {
        return TRUE;
      }
    }

    for ($i = $first_month['row_number']; $i <= $last_month['row_number']; $i++) {

      // Check the first filled row
      if ($i == $first_month['row_number']) {

        // Check if only the first two values in one month are filled
        if ($first_month['month_number'] != $last_month['month_number'] && $first_month['quarter_number'] == $last_month['quarter_number'] && $first_month['row_number'] == $last_month['row_number']) {
          for ($k = $last_month['month_number']; $k >= $first_month['month_number']; $k--) {
            if ($form[$table_name][$i][$this->monthsList[$last_month['quarter_number']][$k]]['#value'] == "") {
              return FALSE;
            }
          }
        } else {
          // Check all months in the quarter from the first filled to the last
          for ($k = $first_month['month_number']; $k < count($this->monthsList[$first_month['quarter_number']]); $k++) {
            if ($form[$table_name][$i][$this->monthsList[$first_month['quarter_number']][$k]]['#value'] == "") {
              return FALSE;
            }
          }

          // Check all months in the quarter from the last filled to the first
          if ($first_month['quarter_number'] != $last_month['quarter_number'] && $first_month['row_number'] == $last_month['row_number']) {
            for ($k = $last_month['month_number']; $k >= 0; $k--) {
              if ($form[$table_name][$i][$this->monthsList[$last_month['quarter_number']][$k]]['#value'] == "") {
                return FALSE;
              }
            }
          }

          if ($last_month['row_number'] > $first_month['row_number']) {
            $last_quarter_number = 4;
          } else {
            $last_quarter_number = $last_month['quarter_number'];
          }

          // Check the quarters between the first and last filled in the row
          $first_quarter = $first_month['quarter_number'] + 1;
          for ($j = $first_quarter; $j < $last_quarter_number; $j++) {
            for ($k = 0; $k < count($this->monthsList[$j]); $k++) {
              if ($form[$table_name][$i][$this->monthsList[$j][$k]]['#value'] == "") {
                return FALSE;
              }
            }
          }
        }

      }

      // Check the last filled row
      if ($i != $first_month['row_number'] && $i == $last_month['row_number']) {

        // Checking the last completed quarter
        for ($k = $last_month['month_number']; $k >= 0; $k--) {
          if ($form[$table_name][$i][$this->monthsList[$last_month['quarter_number']][$k]]['#value'] == "") {
            return FALSE;
          }
        }

        // Check all quarters from the first to the last filled
        for ($j = 0; $j < $last_month['quarter_number']; $j++) {
          for ($k = 0; $k < count($this->monthsList[$j]); $k++) {
            if ($form[$table_name][$i][$this->monthsList[$j][$k]]['#value'] == "") {
              return FALSE;
            }
          }
        }
      }

      // Check all rows between the first and last filled
      if ($i != $first_month['row_number'] && $i != $last_month['row_number']) {
        for ($j = 0; $j < 4; $j++) {
          for ($k = 0; $k < count($this->monthsList[$j]); $k++) {
            if ($form[$table_name][$i][$this->monthsList[$j][$k]]['#value'] == "") {
              return FALSE;
            }
          }
        }
      }
    }

    return TRUE;
  }

  /**
   * Method of checking changes in values in all quarters and years
   */
  public function validateQuartersAndYearsChanged($form, $table_name, $table_rows) {
    $validation_result = TRUE;

    // An array for writing the values of filled quarters
    $year_result = [];

    for ($i = 0; $i <= $table_rows; $i++) {
      for ($j = 0; $j < count($this->monthsList); $j++) {
        $quarter_number = "Q" . ($j + 1);

        // Validation of changes in quarter values
        $quarter_validate = $this->validateQuarterOrYear($form, $table_name, $i, $j, $quarter_number, $year_result);
        if ($quarter_validate['result'] == FALSE) {
          $validation_result = FALSE;
        }
        $year_result = $quarter_validate['year_result'];
      }

      // Validation of changes in years values
      $year_validate = $this->validateQuarterOrYear($form, $table_name, $i, 0, 'YTD', $year_result);
      if ($year_validate['result'] == FALSE) {
        $validation_result = FALSE;
      }
    }

    return $validation_result;
  }

  /**
   * Method that checks the change in value in a specific quarter or year
   */
  public function validateQuarterOrYear($form, $table_name, $row_number, $month_number, $input_name, $year_result) {
    $result = TRUE;

    // If the input value is empty, write 0
    if ($form[$table_name][$row_number][$input_name]['#value'] == "") {
      $input_result = 0;
    } else {
      $input_result = $form[$table_name][$row_number][$input_name]['#value'];
    }

    if ($input_name != 'YTD') {
      $plus_result = $this->monthsPlus($form, $this->monthsList[$month_number], $table_name, $row_number);
      array_push($year_result, $plus_result);
    } else {
      $year_results = array_chunk($year_result, 4);
      $plus_result = $this->quartersPlus($year_results[$row_number]);
    }

    $input_result_max = $input_result + 0.05;
    $input_result_min = $input_result - 0.05;

    if ($plus_result > $input_result_max || $plus_result < $input_result_min) {
      $result = FALSE;
      return [
        'result' => $result,
        'year_result' => $year_result,
      ];
    }

    return [
      'result' => $result,
      'year_result' => $year_result,
    ];
  }

  /**
   * Method for determining the value of a quarter
   */
  public function monthsPlus($form, $months, $table_name, $row_number) {
    $result = 0;

    for ($i = 0; $i < count($months); $i++) {
      if ($form[$table_name][$row_number][$months[$i]]['#value'] == "") {
        $form[$table_name][$row_number][$months[$i]]['#value'] = 0;
      }
      $result += $form[$table_name][$row_number][$months[$i]]['#value'];
    }

    if ($result == 0) {
      return $result;
    } else {
      $result = ($result + 1) / 3;
      $result = round($result, 2);

      return $result;
    }
  }

  /**
   * Method for determining the value of a year
   */
  public function quartersPlus($months) {
    $result = 0;

    for ($i = 0; $i < count($months); $i++) {
      $result += $months[$i];
    }

    if ($result == 0) {
      return $result;
    } else {
      $result = ($result + 1) / 4;
      $result = round($result, 2);
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('validate_error') == FALSE) {
      \Drupal::messenger()->addMessage($this->t("Valid!"), 'status');
    }
  }

}
