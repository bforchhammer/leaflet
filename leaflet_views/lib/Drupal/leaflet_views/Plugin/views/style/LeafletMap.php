<?php

/**
 * @file
 * Definition of Drupal\leaflet_views\Plugin\views\style\LeafletMap.
 */

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;


/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @Plugin(
 *   id = "leafet_map",
 *   title = @Translation("Leaflet map"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   type = "normal",
 *   theme = "leaflet-map",
 *   even_empty = TRUE
 * )
 */
class LeafletMap extends StylePluginBase {

  /**
   * If this view is displaying an entity, save the entity type and info.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {

    // Set these before calling parent::init() as it uses these.
    $this->definition['even empty'] = TRUE; // cannot have space in annotation, so doing it here
    $this->usesOptions = TRUE;
    $this->usesRowPlugin = FALSE;
    $this->usesRowClass = FALSE;
    $this->usesGrouping = FALSE;
    $this->usesFields = TRUE;

    parent::init($view, $display, $options);

    // For later use, set entity info related to the View's base table.
    $base_tables = array_keys($view->getBaseTables());
    $base_table = reset($base_tables);
    foreach (entity_get_info() as $key => $info) {
      if (isset($info['base_table']) && $info['base_table'] == $base_table) {
        $this->entity_type = $key;
        $this->entity_info = $info;
        return;
      }
    }
  }

  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['data_source'] = array('default' => '');
    $options['name_field'] = array('default' => '');
    $options['description_field'] = array('default' => '');
    $options['view_mode'] = array('default' => 'full');
    $options['map'] = array('default' => '');
    $options['height'] = array('default' => '400');
    $options['icon'] = array('default' => array());
    return $options;
  }

  /**
   * Options form
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get a list of fields and a sublist of geo data fields in this view
    $fields = array();
    $fields_geo_data = array();
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->label() ?: $field_id;
      $fields[$field_id] = $label;
      if (!empty($handler->field_info['type']) && $handler->field_info['type'] == 'geofield') {
        $fields_geo_data[$field_id] = $label;
      }
    }

    // Check whether we have a geo data field we can work with
    if (!count($fields_geo_data)) {
      $form['error'] = array(
        '#markup' => t('Please add at least one geofield to the view.'),
      );
      return;
    }

    // Map preset.
    $form['data_source'] = array(
      '#type' => 'select',
      '#title' => t('Data Source'),
      '#description' => t('Which field contains geodata?'),
      '#options' => $fields_geo_data,
      '#default_value' => $this->options['data_source'],
      '#required' => TRUE,
    );

    // Name field
    $form['name_field'] = array(
      '#type' => 'select',
      '#title' => t('Title Field'),
      '#description' => t('Choose the field which will appear as a title on tooltips.'),
      '#options' => array_merge(array('' => ''), $fields),
      '#default_value' => $this->options['name_field'],
    );

    $desc_options = array_merge(array('' => ''), $fields);
    // Add an option to render the entire entity using a view mode
    if ($this->entity_type) {
      $desc_options += array(
        '#rendered_entity' => '<' . t('!entity entity', array('!entity' => $this->entity_type)) . '>',
      );
    }

    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => t('Description Field'),
      '#description' => t('Choose the field or rendering method which will appear as a description on tooltips or popups.'),
      '#required' => FALSE,
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
    );

    if ($this->entity_type) {

      // Get the human readable labels for the entity view modes
      $view_mode_options = array();
      foreach (entity_get_view_modes($this->entity_type) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visibile conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $form['view_mode'] = array(
        '#type' => 'select',
        '#title' => t('View mode'),
        '#description' => t('View modes are ways of displaying entities.'),
        '#options' => $view_mode_options,
        '#default_value' => !empty($this->options['view_mode']) ? $this->options['view_mode'] : 'full',
        '#states' => array(
          'visible' => array(
            ':input[name="style_options[description_field]"]' => array(
              'value' => '#rendered_entity')
          )
        )
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
      '#default_value' => isset($this->options['map']) ? $this->options['map'] : '',
      '#required' => TRUE,
    );

    $form['height'] = array(
      '#title' => t('Map height'),
      '#type' => 'textfield',
      '#field_suffix' => t('px'),
      '#size' => 4,
      '#default_value' => $this->options['height'],
      '#required' => FALSE,
    );

    $form['icon'] = array(
      '#title' => t('Map Icon'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => !isset($this->options['icon']['iconUrl']),
    );

    $form['icon']['iconUrl'] = array(
      '#title' => t('Icon URL'),
      '#description' => t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => isset($this->options['icon']['iconUrl']) ? $this->options['icon']['iconUrl'] : '',
      '#element_validate' => array('leaflet_icon_validate')
    );

    $form['icon']['shadowUrl'] = array(
      '#title' => t('Icon Shadow URL'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => isset($this->options['icon']['shadowUrl']) ? $this->options['icon']['shadowUrl'] : '',
      '#element_validate' => array('leaflet_icon_validate')
    );

    $form['icon']['iconSize'] = array(
      '#title' => t('Icon Size'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => t('Size of the icon image in pixels.')
    );

    $form['icon']['iconSize']['x'] = array(
      '#title' => t('Width'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['iconSize']['x']) ? $this->options['icon']['iconSize']['x'] : '',
      '#element_validate' => array('element_validate_integer_positive'),
    );

    $form['icon']['iconSize']['y'] = array(
      '#title' => t('Height'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['iconSize']['y']) ? $this->options['icon']['iconSize']['y'] : '',
      '#element_validate' => array('element_validate_integer_positive'),
    );

    $form['icon']['iconAnchor'] = array(
      '#title' => t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => t('The coordinates of the "tip" of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.')
    );

    $form['icon']['iconAnchor']['x'] = array(
      '#title' => t('X'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['iconAnchor']['x']) ? $this->options['icon']['iconAnchor']['x'] : '',
      '#element_validate' => array('element_validate_number'),
    );

    $form['icon']['iconAnchor']['y'] = array(
      '#title' => t('Y'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['iconAnchor']['y']) ? $this->options['icon']['iconAnchor']['y'] : '',
      '#element_validate' => array('element_validate_number'),
    );

    $form['icon']['shadowAnchor'] = array(
      '#title' => t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => t('The point from which the shadow is shown.')
    );
    $form['icon']['shadowAnchor']['x'] = array(
      '#title' => t('X'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['shadowAnchor']['x']) ? $this->options['icon']['shadowAnchor']['x'] : '',
      '#element_validate' => array('element_validate_number'),
    );
    $form['icon']['shadowAnchor']['y'] = array(
      '#title' => t('Y'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['shadowAnchor']['y']) ? $this->options['icon']['shadowAnchor']['y'] : '',
      '#element_validate' => array('element_validate_number'),
    );

    $form['icon']['popupAnchor'] = array(
      '#title' => t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => t('The point from which the marker popup opens, relative to the anchor point.')
    );

    $form['icon']['popupAnchor']['x'] = array(
      '#title' => t('X'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['popupAnchor']['x']) ? $this->options['icon']['popupAnchor']['x'] : '',
      '#element_validate' => array('element_validate_number'),
    );

    $form['icon']['popupAnchor']['y'] = array(
      '#title' => t('Y'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($this->options['icon']['popupAnchor']['y']) ? $this->options['icon']['popupAnchor']['y'] : '',
      '#element_validate' => array('element_validate_number'),
    );
  }

  /**
   * Validates the options form.
   */
  public function validateOptionsForm(&$form, &$form_state) {
    if (!is_numeric($form_state['values']['style_options']['height']) || $form_state['values']['style_options']['height'] < 0) {
      form_error($form['height'], t('Map height needs to be a positive number'));
    }
  }

  /**
   * Renders the View.
   */
  function render() {
    
    if (!empty($this->view->live_preview)) {
      return t('Preview is not available for Leaflet map.');
    }
    $data = array();
    $geofield_name = $this->options['data_source'];
    if ($this->options['data_source']) {
      $this->renderFields($this->view->result);
      foreach ($this->view->result as $id => $result) {

        $geofield_value = $this->getFieldValue($id, $geofield_name);

        if (empty($geofield_value)) {
          // In case the result is not among the raw results, get it from the
          // rendered results.
          $geofield_value = leaflet_process_rendered_geofield($this->rendered_fields[$id][$geofield_name]);
        }
        if (!empty($geofield_value)) {
          $points = leaflet_process_geofield($geofield_value);

          // Render the entity with the selected view mode
          if ($this->options['description_field'] === '#rendered_entity' && is_object($result)) {
            $entity = entity_load($this->entity_type, $result->{$this->entity_info['entity_keys']['id']});
            $build = entity_view($entity, $this->options['view_mode']);
            $description = drupal_render($build);
          }
          // Normal rendering via fields
          elseif ($this->options['description_field']) {
            $description = $this->rendered_fields[$id][$this->options['description_field']];
          }

          // Attach pop-ups if we have a description field
          if (isset($description)) {
            foreach ($points as &$point) {
              $point['popup'] = $description;
            }
          }

          // Attach also titles, they might be used later on
          if ($this->options['name_field']) {
            foreach ($points as &$point) {
              $point['label'] = $this->rendered_fields[$id][$this->options['name_field']];
            }
          }

          $data = array_merge($data, $points);

          if (!empty($this->options['icon']) && $this->options['icon']['iconUrl']) {
            foreach ($data as $key => $feature) {
              $data[$key]['icon'] = $this->options['icon'];
            }
          }
        }
      }

      $map = leaflet_map_get_info($this->options['map']);

      if (!empty($data)) {
        return leaflet_render_map($map, $data, $this->options['height'] . 'px');
      }
    }
    return '';
  }
}
