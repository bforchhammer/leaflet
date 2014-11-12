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

  public function renderGroupingSets($sets, $level = 0) {
    $output = array();
    foreach ($sets as $set) {
      if ($this->usesRowPlugin()) {
        foreach ($set['rows'] as $index => $row) {
          $this->view->row_index = $index;
          $set['rows'][$index] = $this->view->rowPlugin->render($row);
        }
      }
      $set['features'] = array();
      foreach ($set['rows'] as $group) {
        $set['features'] = array_merge($set['features'], $group);
      }

      $featureGroup = $this->renderLeafletGroup($set['features'], $set['group'], $level);
      // If the rendered "feature group" is actually only a list of features,
      // merge them into the output; else simply append the feature group.
      if (empty($featureGroup['group'])) {
        $output = array_merge($output, $featureGroup);
      }
      else {
        $output[] = $featureGroup;
      }
    }
    unset($this->view->row_index);
    return $output;
  }

  /**
   * Render a single group of leaflet markers.
   *
   * @param array $features
   *   The list of leaflet features / points.
   * @param $title
   *   The group title.
   * @param $level
   *   The current group level.
   * @return array
   *   Definition of leaflet markers, compatible with leaflet_render_map().
   */
  protected function renderLeafletGroup(array $features = array(), $title, $level) {
    return $features;
  }

}
