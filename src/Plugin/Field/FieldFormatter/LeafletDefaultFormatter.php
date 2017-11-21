<?php

/**
 * @file
 * Definition of Drupal\leaflet\Plugin\Field\FieldFormatter\LeafletDefaultFormatter.
 */

namespace Drupal\leaflet\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'leaflet_default' formatter.
 *
 * @FieldFormatter(
 *   id = "leaflet_formatter_default",
 *   label = @Translation("Leaflet map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class LeafletDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'leaflet_map' => '',
      'height' => 400,
      'popup' => False,
      'icon' => array(
        'icon_url' => '',
        'shadow_url' => '',
        'icon_size' => array('x' => 0, 'y' => 0),
        'icon_anchor' => array('x' => 0, 'y' => 0),
        'shadow_anchor' => array('x' => 0, 'y' => 0),
        'popup_anchor' => array('x' => 0, 'y' => 0),
      ),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $options = array('' => $this->t('-- Select --'));
    foreach (leaflet_map_get_info() as $key => $map) {
      $options[$key] = $this->t($map['label']);
    }
    $elements['leaflet_map'] = array(
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('leaflet_map'),
      '#required' => TRUE,
    );
    $elements['height'] = array(
      '#title' => $this->t('Map Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#field_suffix' => $this->t('px'),
      '#element_validate' => array('form_validate_number'),
    );
    $elements['popup'] = array(
      '#title' => $this->t('Popup'),
      '#description' => $this->t('Show a popup for single location fields.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('popup'),
    );
    $icon = $this->getSetting('icon');
    $elements['icon'] = array(
      '#title' => $this->t('Map Icon'),
      '#description' => $this->t('These settings will overwrite the icon settings defined in the map definition.'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => empty($icon),
    );
    $elements['icon']['icon_url'] = array(
      '#title' => $this->t('Icon URL'),
      '#description' => $this->t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $icon['icon_url'],
      '#element_validate' => array(array($this, 'validateUrl')),
    );
    $elements['icon']['shadow_url'] = array(
      '#title' => $this->t('Icon Shadow URL'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $icon['shadow_url'],
      '#element_validate' => array(array($this, 'validateUrl')),
    );

    $elements['icon']['icon_size'] = array(
      '#title' => $this->t('Icon Size'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('Size of the icon image in pixels.')
    );
    $elements['icon']['icon_size']['x'] = array(
      '#title' => $this->t('Width'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['icon_size']['x'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['icon_size']['y'] = array(
      '#title' => $this->t('Height'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['icon_size']['y'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['icon_anchor'] = array(
      '#title' => $this->t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The coordinates of the "tip" of the icon (relative to
        its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.')
    );
    $elements['icon']['icon_anchor']['x'] = array(
      '#title' => $this->t('X'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['icon_anchor']['x'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['icon_anchor']['y'] = array(
      '#title' => $this->t('Y'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['icon_anchor']['y'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['shadow_anchor'] = array(
      '#title' => $this->t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the shadow is shown.')
    );
    $elements['icon']['shadow_anchor']['x'] = array(
      '#title' => $this->t('X'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['shadow_anchor']['x'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['shadow_anchor']['y'] = array(
      '#title' => $this->t('Y'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['shadow_anchor']['y'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['popup_anchor'] = array(
      '#title' => $this->t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the marker popup opens, relative
        to the anchor point.')
    );
    $elements['icon']['popup_anchor']['x'] = array(
      '#title' => $this->t('X'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['popup_anchor']['x'],
      '#element_validate' => array('form_validate_number'),
    );
    $elements['icon']['popup_anchor']['y'] = array(
      '#title' => $this->t('Y'),
      '#type' => 'textfield',
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $icon['popup_anchor']['y'],
      '#element_validate' => array('form_validate_number'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->t('Leaflet map: @map', array('@map' => $this->getSetting('leaflet_map')));
    $summary[] = $this->t('Map height: @height px', array('@height' => $this->getSetting('height')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $icon_url = $settings['icon']['icon_url'];

    $map = leaflet_map_get_info($settings['leaflet_map']);

    $elements = array();
    foreach ($items as $delta => $item) {

      $features = leaflet_process_geofield($item->value);

      // If only a single feature, set the popup content to the entity title.
      if ($settings['popup'] && count($items) == 1) {
        $features[0]['popup'] = $items->getEntity()->label();
      }
      if (!empty($icon_url)) {
        foreach ($features as $key => $feature) {
          $features[$key]['icon'] = $icon_url;
        }
      }
      $elements[$delta] = leaflet_render_map($map, $features, $settings['height'] . 'px');
    }
    return $elements;
  }

  public function validateUrl($element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !UrlHelper::isValid($element['#value'])) {
      $form_state->setError($element, $this->t("Icon Url is not valid."));
    }
  }

}
