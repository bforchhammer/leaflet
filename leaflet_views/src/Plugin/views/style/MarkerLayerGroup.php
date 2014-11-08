<?php
/**
 * @file
 * Contains Drupal\leaflet_views\Plugin\views\style\MarkerLayerGroup.
 */

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render leaflet features in layer groups.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "leaflet_marker_group",
 *   title = @Translation("Grouped Markers"),
 *   help = @Translation("Render data as leaflet markers, grouped in layers."),
 *   display_types = {"leaflet"},
 * )
 */
class MarkerLayerGroup extends MarkerDefault {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = TRUE;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function renderGrouping($records, $groupings = array(), $group_rendered = NULL) {
    $sets = parent::renderGrouping($records, $groupings, $group_rendered);
    if (!$groupings) {
      $attachment_title = $this->view->getDisplay()->getOption('title');
      $sets['']['group'] = $attachment_title ? $attachment_title : $this->t('Label missing');
    }
    return $sets;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
    // Add group options.
  }

  /**
   * {@inheritdoc}
   */
  protected function renderRowGroup(array $rows = array()) {
    return array(
      '#leaflet' => 'LayerGroup',
      '#markers' => $rows,
    );
  }
}
