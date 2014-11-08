<?php
/**
 * @file
 * Contains Drupal\leaflet_views\Plugin\views\style\Leaflet.
 */

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leafet",
 *   title = @Translation("Leaflet"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 */
class Leaflet extends StylePluginBase {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Choose a map preset
    $map_options = array();
    foreach (leaflet_map_get_info() as $key => $map) {
      $map_options[$key] = $this->t($map['label']);
    }
    $form['map'] = array(
      '#title' => $this->t('Map'),
      '#type' => 'select',
      '#options' => $map_options,
      '#default_value' => $this->options['map'] ?: '',
      '#required' => TRUE,
    );

    $form['height'] = array(
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#field_suffix' => $this->t('px'),
      '#size' => 4,
      '#default_value' => $this->options['height'],
      '#required' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $height = $form_state->getValue(array('style_options', 'height'));
    if (!is_numeric($height) || $height <= 0) {
      $form_state->setError($form['height'], $this->t('Map height needs to be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    // Render map even if there is no data.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Avoid querying the database.
    $this->built = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $features = array();
    foreach ($this->view->attachment_before as $attachment) {
      $args = $attachment['#arguments'];
      array_unshift($args, $attachment['#display_id']);
      array_unshift($args, $attachment['#name']);
      $view_result = call_user_func_array('views_embed_view', $args);
      $features = array_merge($features, $this->extract_features($view_result['#rows']));
    }

    $element = leaflet_render_map(leaflet_map_get_info($this->options['map']), $features, $this->options['height'] . 'px');
    if ($this->view->preview) {
      return '<pre>' . print_r($element, 1) . '</pre>';
    }
    return $element;
  }

  protected function extract_features($data) {
    if (isset($data['#leaflet'])) {
      switch ($data['#leaflet']) {
        case 'markers':
          return $this->extract_features($data['#markers']);

        case 'marker':
          $points = $data['#points'];
          foreach ($points as &$point) {
            // @todo label?
            $point['popup'] = $data['#popup']['body'];
          }
          return $points;

        case 'LayerGroup':
        case 'MarkerClusterGroup':
          return array(
            array(
              'group' => TRUE,
              'label' => $data['#title'],
              'features' => $this->extract_features($data['#markers']),
            )
          );

        default:
          return array();
      }
    }
    else {
      $features = array();
      foreach ($data as $row) {
        $features = array_merge($features, $this->extract_features($row));
      }
      return $features;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (empty($this->options['map'])) {
      $errors[] = $this->t('Style @style requires a leaflet map to be configured.', array('@style' => $this->definition['title']));
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['map'] = array('default' => '');
    $options['height'] = array('default' => '400');
    return $options;
  }
}
