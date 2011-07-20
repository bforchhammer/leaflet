Drupal.behaviors.leaflet = {
  attach: function (context, settings) {
    jQuery(settings.leaflet).each(function() {
      console.log(this);
      // load a settings object with all of our map settings
      var settings = new Object();
      for (setting in this.map.settings) {
        settings[setting] = this.map.settings[setting];
      }
      
      // instantiate our new map
      var map = new L.Map(this.mapId, settings);

      // add map layers
      for (layer in this.map.layers) {
        map_layer = new L.TileLayer(this.map.layers[layer].urlTemplate, 
          {
            scheme: this.map.layers[layer].scheme
          }
        );
        map.addLayer(map_layer);
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
    });
  }
};