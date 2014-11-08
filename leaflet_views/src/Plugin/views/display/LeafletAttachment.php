<?php
/**
 * @file
 * Contains Drupal\leaflet_views\Plugin\views\display\LeafletDataAttachment.
 */

namespace Drupal\leaflet_views\Plugin\views\display;


use Drupal\views\Plugin\views\display\Attachment;

/**
 * The plugin which handles attachment of additional leaflet features to
 * leaflet map views.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "leaflet_attachment",
 *   title = @Translation("Leaflet Attachment"),
 *   help = @Translation("Add additional markers to a leaflet map."),
 *   theme = "leaflet-attachment",
 *   contextual_links_locations = {""}
 * )
 */
class LeafletAttachment extends Attachment {

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = FALSE;

  /**
   * Whether the display allows the use of a 'more' link or not.
   *
   * @var bool
   */
  protected $usesMore = FALSE;

  /**
   * Whether the display allows area plugins.
   *
   * @var bool
   */
  protected $usesAreas = FALSE;

  /**
   * {@inheritdoc}
   */
  public function usesLinkDisplay() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'leaflet';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Overrides for standard stuff.
    $options['style']['contains']['type']['default'] = 'leaflet_data';
    $options['defaults']['default']['style'] = FALSE;

    return $options;
  }

}
