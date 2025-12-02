<?php

/**
 * Declare our sort plugin to views.
 *
 * @return array
 *    The views data array.
 */
function views_arg_order_sort_views_data() {

  $data['views_arg_order_sort']['table']['group']  = t('Arguments');
  $data['views_arg_order_sort']['table']['join']  = array(
    '#global' => array(),
  );

  $data['views_arg_order_sort']['weight'] = array(
    'title' => t('Multi-item Argument Order'),
    'help' => t('Sort by the order of items in an multi-item argument'),
    'sort' => array(
      'id' => 'views_arg_order_sort_default',
    ),
  );
  return $data;

}
