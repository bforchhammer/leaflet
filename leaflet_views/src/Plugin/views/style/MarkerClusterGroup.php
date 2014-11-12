<?php
/**
 * @file
 * Contains Drupal\leaflet_views\Plugin\views\style\MarkerClusterGroup.
 */

namespace Drupal\leaflet_views\Plugin\views\style;


/**
 * Style plugin to render leaflet features in layer clusters.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "leaflet_marker_cluster",
 *   title = @Translation("Clustered Markers"),
 *   help = @Translation("Render data as leaflet marker clusters."),
 *   display_types = {"leaflet"}
 * )
 */
class MarkerClusterGroup extends MarkerLayerGroup {

  /**
   * {@inheritdoc}
   */
  protected function renderLeafletGroup(array $features = array(), $title = '', $level = 0) {
    return array(
      'group' => TRUE,
      'label' => $title,
      'features' => $features,
    );
  }

}
