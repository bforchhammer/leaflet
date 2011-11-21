(function($) {

Drupal.behaviors.leaflet = {
  attach: function (context, settings) {

    $(settings.leaflet).each(function() {
      // load a settings object with all of our map settings
      var settings = {};
      var lMap = {};
      var bounds = [];
      for (var setting in this.map.settings) {
        settings[setting] = this.map.settings[setting];
      }
      
      // instantiate our new map
      lMap = new L.Map(this.mapId, settings);

      // add map layers
      var layers = {};
      var i = 0;
      for (var key in this.map.layers) {
				var layer = this.map.layers[key];
        var map_layer = new L.TileLayer(layer.urlTemplate);
        if (layer.options) {
          for (var option in layer.options) {
             map_layer.options[option] = layer.options[option];
           }          
        }
        layers[key] = map_layer;

        // layers served from TileStream need this correction in the y coordinates
        // TODO: Need to explore this more and find a more elegant solution
        if (layer.type == 'tilestream') {
          map_layer.getTileUrl = function(tilePoint, zoom){
            var subdomains = this.options.subdomains,
              s = this.options.subdomains[(tilePoint.x + tilePoint.y) % subdomains.length];

            return this._url
              .replace('{z}', zoom)
              .replace('{x}', tilePoint.x)
              .replace('{y}', Math.pow(2,zoom) - tilePoint.y -1);
          }
        }

        // add the first layer to the map
        if (i === 0) {
          lMap.addLayer(map_layer);
        }
        i++;        
      }

      // add layer switcher
      if (this.map.settings.layerControl) {
        lMap.addControl(new L.Control.Layers(layers));
      }
      
      // add markers
      for (var i=0; i < this.features.length; i++) {
        var feature = this.features[i];
				var lFeature;
				switch(feature.type) {
					case 'point':
						lFeature = leaflet_create_point(feature, bounds);
						break;
					case 'linestring':
						lFeature = leaflet_create_linestring(feature, bounds);
						break;
					case 'polygon':
						lFeature = leaflet_create_polygon(feature, bounds);
						break;
          case 'multipolygon':
          case 'multipolyline':
            lFeature = leaflet_create_multipoly(feature, bounds);
            break;
        }

        lMap.addLayer(lFeature);

	      if (feature.popup) {
	        lFeature.bindPopup(feature.popup);
	      }
      }

      // either center the map or set to bounds
      if (this.map.center) {
        lMap.setView(new L.LatLng(this.map.center.lat, this.map.center.lon), this.map.settings.zoom);
      }
      else {
        lMap.fitBounds(new L.LatLngBounds(bounds));
      }

      // add attribution
      if (this.map.settings.attributionControl && this.map.attribution) {
        lMap.attributionControl.setPrefix(this.map.attribution.prefix);
        lMap.attributionControl.addAttribution(this.map.attribution.text);
      }

      // add the leaflet map to our settings object to make it accessible
      this.lMap = lMap;
    });

		function leaflet_create_point(marker, bounds) {
      var latLng = new L.LatLng(marker.lat, marker.lon);
      bounds.push(latLng);
			var lMarker;
      if (marker.icon) {
        var icon = new L.Icon(marker.icon.iconUrl);

        // override applicable marker defaults
        if (marker.icon.iconSize) {
          icon.iconSize = new L.Point(marker.icon.iconSize.x, marker.icon.iconSize.y);
        }
        if (marker.icon.iconAnchor) {
          icon.iconAnchor = new L.Point(marker.icon.iconAnchor.x, marker.icon.iconAnchor.y);
        }
        if (marker.icon.popupAnchor) {
          icon.popupAnchor = new L.Point(marker.icon.popupAnchor.x, marker.icon.popupAnchor.y);
        }
        if (marker.icon.shadowUrl) {
          icon.shadowUrl = marker.icon.shadowUrl;
        }
        lMarker = new L.Marker(latLng, {icon: icon});
      }
      else {
        lMarker = new L.Marker(latLng);
      }
      return lMarker;		
		}
		
		function leaflet_create_linestring(polyline, bounds) {
			var latlngs = [];
			for (var i=0; i < polyline.points.length; i++) {
				var latlng = new L.LatLng(polyline.points[i].lat, polyline.points[i].lon);
        latlngs.push(latlng);
        bounds.push(latlng);
			}
			return new L.Polyline(latlngs);			
		}
		
		function leaflet_create_polygon(polygon, bounds) {
			var latlngs = [];
			for (var i=0; i < polygon.points.length; i++) {
				var latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
        latlngs.push(latlng);
        bounds.push(latlng);
			}
			return new L.Polygon(latlngs);
		}

    function leaflet_create_multipoly(multipoly, bounds) {
      var polygons = [];
      for (var x=0; x < multipoly.component.length; x++) {
        var latlngs = [];
        var polygon = multipoly.component[x];
        for (var i=0; i < polygon.points.length; i++) {
          var latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
          latlngs.push(latlng);
          bounds.push(latlng);
        }
        polygons.push(latlngs);
      }
      return new L.MultiPolygon(polygons);
    }
  }
};

})(jQuery);
