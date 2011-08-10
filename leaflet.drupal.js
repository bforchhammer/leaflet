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
      var layers = new Object();
      var i = 0;
      for (var layer in this.map.layers) {
        map_layer = new L.TileLayer(this.map.layers[layer].urlTemplate);
        if (this.map.layers[layer].options) {
          for (option in this.map.layers[layer].options) {
             map_layer.options[option] = this.map.layers[layer].options[option];
           }          
        }      
        layers[layer] = map_layer;
      
        // add the first layer to the map
        if (i == 0) {
          map.addLayer(map_layer);          
        }
        i++;        
      }
      
      // add layer switcher
      if (this.map.settings.layerControl) {
        map.addControl(new L.Control.Layers(layers));
      }
      
      // add features
      var bounds = new Array();
      for (var i=0; i < this.features.markers.length; i++) {
        var latLng = new L.LatLng(this.features.markers[i].lat, this.features.markers[i].lon);
        var mymarker = this.features.markers[i];
        bounds[i] = latLng;
        if (mymarker.icon) {
          var icon = new L.Icon(mymarker.icon.iconUrl);
          icon.iconSize = new L.Point(mymarker.icon.iconSize['x'], mymarker.icon.iconSize['y']);
          icon.iconAnchor = new L.Point(mymarker.icon.iconAnchor['x'], mymarker.icon.iconAnchor['y']);
          icon.popupAnchor = new L.Point(mymarker.icon.popupAnchor['x'], mymarker.icon.popupAnchor['y']);
          icon.shadowUrl = mymarker.icon.shadowUrl;
          var marker = new L.Marker(latLng, {icon: icon});
        }
        else {
          var marker = new L.Marker(latLng);
        }

        map.addLayer(marker);
        if (mymarker.popup) {
          marker.bindPopup(mymarker.popup);
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

