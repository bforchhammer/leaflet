<?php

/**
 * @file
 * Extension of the Views Plugin Style for Leaflet Map
 * Adapted from the GeoField Map views module and the OpenLayers Views module.
 */
class leaflet_views_plugin_style extends views_plugin_style {

  /**
   * Set default options
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['data_source'] = array('default' => '');
    $options['name_field'] = array('default' => '');
    $options['description_field'] = array('default' => '');
    $options['map'] = array('default' => '');
    $options['view_mode'] = array('default' => 'full');
    return $options;
  }

  /**
   * Options form
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Get list of fields in this view & flag available geodata fields
    $handlers = $this->display->handler->get_handlers('field');

    $fields = array();
    $fields_data = array();
    foreach ($handlers as $field_id => $handler) {
      $fields[$field_id] = $handler->ui_name();
      
      if (!empty($handler->field_info['type']) && $handler->field_info['type'] == 'geofield') {
        $fields_data[$field_id] = $handler->ui_name();
      }
    }
    
    // Check whether we have a geofield we can work with
    if (!count($fields_data)) {
      $form['error'] = array(
        '#markup' => t('Please add at least one geofield to the view'),
      );
      return;
    }

    // Map preset.
    $form['data_source'] = array(
      '#type' => 'select',
      '#title' => t('Data Source'),
      '#description' => t('Which field contains geodata?'),
      '#options' => $fields_data,
      '#default_value' => $this->options['data_source'],
      '#required' => TRUE,
    );
    
    // Name field
    $form['name_field'] = array(
      '#type' => 'select',
      '#title' => t('Title Field'),
      '#description' => t('Choose the field which will appear as a title on tooltips.'),
      // '#options' => $fields,
      '#options' => array_merge(array('' => ''), $fields),
      '#default_value' => $this->options['name_field'],
    );

    // For node content type, we can render the node as with a specific view mode.
    // Based on code from openlayers_views_style_data::options_form()
    // @todo add #row support to render the entire row
    if ($this->view->base_table == 'node') {
      $desc_options = array_merge(array(
        '' => '',
        '#node' => '<' . t('entire node') . '>'
      ), $fields);
    }
    else {
      $desc_options = array_merge(array(
        '' => '',
      ), $fields);
    }

    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => t('Description Content'),
      '#description' => t('Choose the field or rendering method which will appear as a description on tooltips or popups.'),
      '#required' => FALSE,
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
    );

    // Taken from openlayers_views_style_data::options_form().
    // Create view mode options
    // @todo add support for view modes of other entities, not just nodes!
    // (e.g. profile2 entities, etc.)
    if ($this->view->base_table == 'node') {
      // Available_view_modes is the array to contain the avaible
      // view modes, for now this is not content type aware, but should be
      $available_view_modes = array();
      $entity_info = entity_get_info();
      // Get the machine names of the view modes
      $view_modes_machine_names[] = array_keys($entity_info['node']['view modes']);
      // Get the labels (human readable) of the view modes
      foreach ($entity_info['node']['view modes'] as $key => $view_mode) {
        $view_modes_labels[] = $view_mode['label'];
      }
      // Combine the machine view mode name with the label, this could sure be done much better?
      $entities_to_display = array_combine($view_modes_machine_names[0], $view_modes_labels);
      //output the form
      $form['view_mode'] = array(
        '#type' => 'select',
        '#title' => t('View mode'),
        '#description' => t('View modes are ways of displaying nodes.'),
        '#options' => $entities_to_display,
        '#default_value' => !empty($this->options['view_mode']) ?
          $this->options['view_mode'] : 'full',
        '#states' => array(
          'visible' => array(
            ':input[name="style_options[description_field]"]' => array('value' => '#node'),
          ),
        ),
      );
    }     

    // Choose a map preset
    $map_options = array();
    foreach (leaflet_map_get_info() as $key => $map) {
      $map_options[$key] = t($map['label']);
    }

    $form['map'] = array(
      '#title' => t('Map'),
      '#type' => 'select',
      '#options' => $map_options,
      '#default_value' => $this->options['map'] ? $this->options['map'] : '',
      '#required' => TRUE,
    );
  }

  /**
   * Renders view
   */
  function render() {
    if (!empty($this->view->live_preview)) {
      return t('No preview available');
    }
    
    $data = array();
    
    if ($this->options['data_source']) {
      $this->render_fields($this->view->result);

      foreach ($this->view->result as $id => $result) {
        $data_source_field = 'field_' . $this->options['data_source'];
        $geofield = (!empty($result->$data_source_field)) ? $result->$data_source_field : NULL;

        if (!empty($geofield)) {     
          // Handle rendering of a node view     
          if ($this->options['description_field'] === '#node' && is_object($result) && isset($result->nid)) {          
            $view_mode = $this->options['view_mode'] ? $this->options['view_mode'] : 'full';
            $build = node_view(node_load($result->nid), $view_mode);
            $description = drupal_render($build);
          }
        
          // Normal rendering via fields
          else if($this->options['description_field']) {
            $description = $this->rendered_fields[$id][$this->options['description_field']];
          }
          
          $raw_data = array();
          foreach ($geofield as $item) {
            $raw_data[] = $item['raw'];
          }
          $points = leaflet_process_geofield($raw_data);
          
          // Attach pop-ups if we have a description field
          if (isset($description)) {
            array_walk($points, create_function('&$p,$i,$t', '$p[\'popup\'] = $t;'), $description);
          }
          
          $data = array_merge($data, $points);
        }
      }
      
      $map = leaflet_map_get_info($this->options['map']);
      return leaflet_render_map($map, $data);
    }
    
    return '';
  }
}
