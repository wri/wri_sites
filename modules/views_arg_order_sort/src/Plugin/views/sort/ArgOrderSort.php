<?php

namespace Drupal\views_arg_order_sort\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Handle a random sort.
 *
 * @ViewsSort("views_arg_order_sort_default")
 */
class ArgOrderSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['inherit_type'] = ['default' => 1];
    $options['null_below'] = ['default' => 1];
    $options['argument_number'] = ['default' => 0];
    $options['field_type'] = ['default' => 'node::nid'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [];
    $group_options = [];

    $base_tables = Views::viewsData()->fetchBaseTables();
    $table_data = Views::viewsData()->get();

    foreach ($base_tables as $table => $values) {
      $data = $table_data[$table];
      $group = (string) \Drupal::service('renderer')->render($data['table']['group']);
      foreach ($data as $field => $f) {
        if (isset($f['entity field'])) {
          $options[$group][$table . '::' . $field] = \Drupal::service('renderer')->render($f['title']);
        }
      }
    }

    $form['argument_number'] = [
      '#title' => t('Argument'),
      '#type' => 'select',
      '#options' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
      '#default_value' => $this->options['argument_number'],
    ];
    $form['null_below'] = [
      '#type' => 'checkbox',
      '#title' => 'Non arguments at End',
      '#description' => t('Place items not in the argument at the end.'),
      '#default_value' => $this->options['null_below'],
      '#options' => [0, 'Null values below'],
    ];
    $form['inherit_type'] = [
      '#type' => 'checkbox',
      '#title' => 'Inherit type of Field from Argument',
      '#description' => t('If the argument is the NULL argument or you want to choose a different type for linking the uncheck, otherwise it is safe to leave it checked.'),
      '#default_value' => $this->options['inherit_type'],
      '#options' => [0, 'Inherit type of Field from Argument'],
      '#ajax' => [
        'callback' => 'views_boxes_arg_order_type_callback',
        'wrapper' => 'arg-order-type',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    $form['field_type'] = [
      '#title' => t('Type of Argument Field'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->options['field_type'],

    ];

  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Retrieve options.
    $arg_to_use = $this->options['argument_number'];
    $inherit_type = $this->options['inherit_type'];
    $order = $this->options['order'];
    $null_below = $this->options['null_below'];

    // If inherited look at the argument to get table and field.
    if ($inherit_type) {
      $arg_handlers = array_values($this->view->argument);
      $arg_handler = $arg_handlers[$arg_to_use];
      $left_table = $arg_handler->table;
      $left_field = $arg_handler->field;
    }
    else {
      [$left_table, $left_field] = explode('::', $this->options['field_type']);
    }

    // The type to replace. Always uses string--seems ok.
    $value_query_type = "'%s'";

    // The arguments passed into the view.
    $args = $this->view->args;
    $items = isset($args[$arg_to_use]) ? explode('+', $args[$arg_to_use]) : [];

    // Get our sort order.
    $invert_order = ($order == 'DESC') ? TRUE : FALSE;
    $items = $invert_order ? array_reverse($items) : $items;

    // Defaults.
    $max_o = 0;
    $case_items = [];

    // Generate the case statement from the args.
    foreach ($items as $o => $value) {
      if ($value) {
        $case_items[] = "WHEN $value_query_type THEN $o ";
        $max_o = $max_o > $o ? $max_o : $o;
      }
    }

    // If the case statement items are populated.
    if (!empty($case_items)) {
      $is_desc = ($order == 'DESC');
      $null_o = $is_desc == $null_below ? -1 : $max_o + 1;

      $order_by = "CASE $left_table.$left_field " . implode("", $case_items) . " ELSE $null_o END";
      $order_by = vsprintf($order_by, $items);

      $alias = "arg_order" . rand(0, 10000);
      $this->query->addOrderBy(NULL, $order_by, $order, $alias);
    }
  }

}
