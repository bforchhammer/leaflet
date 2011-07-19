Drupal.behaviors.leaflet = {
  attach: function (context, settings) {
    jQuery(settings.leaflet).each(function() {
      console.log(this);
      var map = new L.Map(this.mapId, {
          center: new L.LatLng(this.features[0]['lat'], this.features[0]['lon']), 
          zoom: 3//this.zoom
      });
      
      var tileUrl = this.layers[0],
          layer = new L.TileLayer(tileUrl, {maxZoom: 18, scheme: 'tms'});
      
      map.addLayer(layer);          
    });
  }
};