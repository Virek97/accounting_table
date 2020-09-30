<?php


namespace Drupal\accounting_table\Controller;

use \Drupal\Core\Controller\ControllerBase;


class Accounting extends ControllerBase {

  public function get_data() {

    $accounting_form = \Drupal::formBuilder()->getForm('Drupal\accounting_table\Form\AccountingForm');

    return array(
      '#theme' => 'accounting_table_theme',
      '#title' => $this->t('Accounting Table'),
      '#accounting_form' => $accounting_form,
    );

  }

}
