Drupal.behaviors.leaflet = {
  attach: function (context, settings) {
    jQuery(settings.leaflet).each(function() {
      // load a settings object with all of our map settings
      var settings = new Object();
      for (setting in this.map.settings) {
        settings[setting] = this.map.settings[setting];
      }
      
      // instantiate our new map
      var map = new L.Map(this.mapId, settings);

      // add map layers
      var layers = new Array();
      for (layer in this.map.layers) {
        map_layer = new L.TileLayer(this.map.layers[layer].urlTemplate, 
          {
            scheme: this.map.layers[layer].scheme
          }
        );
        map.addLayer(map_layer);        
        layers[layer] = map_layer;
      }
      
      // add layer switcher
      if (this.map.settings.layerControl) {
        map.addControl(new L.Control.Layers(layers));        
      }
      
      // add features
      var bounds = new Array();
      for (var i=0; i < this.features.markers.length; i++) {
        var latLng = new L.LatLng(this.features.markers[i].lat, this.features.markers[i].lon);
        bounds[i] = latLng;
        var marker = new L.Marker(latLng);
        map.addLayer(marker);        
        if (this.features.markers[i].popup) {
          marker.bindPopup(this.features.markers[i].popup);          
        }
      };
      
      // either center the map or set to bounds
      if (this.map.center) {
        map.setView(new L.LatLng(this.map.center.lat, this.map.center.lon), this.map.settings.zoom);
      }
      else {
        map.fitBounds(new L.LatLngBounds(bounds));        
      }

      // add attribution
      if (this.map.settings.attributionControl) {
        map.attributionControl.setPrefix(this.map.attribution.prefix);
        map.attributionControl.addAttribution(this.map.attribution.text);
      }
    });
  }
};
