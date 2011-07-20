<?php

/**
 * Implements hook_field_formatter_info().
 */
function leaflet_field_formatter_info() {
  $formatters = array();

  if (module_exists('geofield')) {  
    $formatters['geofield_leaflet'] = array(
      'label' => t('Leaflet'),
      'field types' => array('geofield'),
      'settings' => array('leaflet_map' => '', 'data' => ''),
    );
  }
  
  return $formatters;
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function leaflet_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $element = array();

  if ($display['type'] == 'geofield_leaflet') {
    $options = array('' => t('-- Select --'));
    foreach(leaflet_map_get_info() as $key => $map) {
      $options[$key] = t($map['label']);
    }
    
    $element['leaflet_map'] = array(
      '#title' => t('Leaflet Map'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $settings['leaflet_map'],
      '#required' => TRUE,
    );
  }

  return $element;
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function leaflet_field_formatter_settings_summary($field, $instance, $view_mode) {
  $display = $instance['display'][$view_mode];
  $settings = $display['settings'];

  $summary = '';

  if ($display['type'] == 'geofield_leaflet') {
    $summary = t('Leaflet map: @map', array('@map' => $settings['leaflet_map']));
  }

  return $summary;
}

/**
 * Implements hook_field_formatter_view().
 */
function leaflet_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();
  $settings = $display['settings'];

  switch ($display['type']) {
    case 'geofield_leaflet':
      $map = leaflet_map_get_info($settings['leaflet_map']);
      $markers = array();
      foreach($items as $item) {
        $markers[] = array(
          'lat' => $item['lat'],
          'lon' => $item['lon'],
          'popup' => $entity->title
        );
      }
      $element[0] = array('#markup' => leaflet_render_map($map, array('markers' => $markers)));
      return $element;
  }

  return $element;
}
