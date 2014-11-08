<?php
/**
 * @file
 * Contains Drupal\leaflet_views\Plugin\views\style\MarkerDefault.
 */

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render leaflet markers.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "leaflet_marker_default",
 *   title = @Translation("Markers"),
 *   theme = "leaflet-marker-default",
 *   help = @Translation("Render data as leaflet markers."),
 *   display_types = {"leaflet"},
 * )
 */
class MarkerDefault extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function renderRowGroup(array $rows = array()) {
    return array(
      '#leaflet' => 'markers',
      '#markers' => $rows,
    );
  }

}
