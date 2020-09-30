(function( $ ) {
	'use strict';

	 $(function() {

		 	 IKaart.unclusteredLayers = [];
		 	 IKaart.clusterLayer = {};
		 	 IKaart.features = [];

		 	 IKaart.parseLocationData = function(className) {

		 	 	const features = [];
		 	 	$("."+ className).each(function(cntr, elem) {
		 	 		// add them by id?
		 	 		// add layers?
		 	 		if ($(elem).data("latitude") && $(elem).data("longitude")) {
		 	 			const lat = $(elem).data("latitude");
		 	 			const lon = $(elem).data("longitude");
		 	 			// const marker = L.marker([lat, lon]);
		 	 			// TODO: max Width?
		 	 			// maxWidth: 800
		 	 			// marker.bindPopup(L.popup({}).setContent(elem.innerHTML));
		 	 			// markers.push(marker);
						const category = $(elem).data("category") ? $(elem).data("category") : "unknown";

						// let's create a nice geojson feature
						const feature = {
						    "type": "Feature",
						    "properties": {
						        "category": category,
						        "popupContent": elem.innerHTML
						    },
						    "geometry": {
						        "type": "Point",
						        "coordinates": [lon, lat]
						    }
						};
						features.push(feature);
		 	 		}
		 	 	});

				const pointsLayer = L.geoJSON(features, {
						pointToLayer: function (feature, latlng) {
							// create a customicon
							const category = feature.properties.category ? feature.properties.category : "unknown";
							var customIcon = L.AwesomeMarkers.icon({
								icon: category,
								// prefix: 'fa',
								iconColor: 'white',
								markerColor: 'white'
							});
							return L.marker(latlng, {icon: customIcon});
				    }
				}).bindPopup(function (layer) {
				    return layer.feature.properties.popupContent;
				}).addTo(IKaart.map);
		 	 	// update the data, for later usage like removal of layers or whatever..
		 	 	IKaart.features = features;
		 	 	return features;
		 	 }

		 	 IKaart.enableClusters = function(enable) {
		 	   // Configuration of clustering?
		 	   // What if clustering should be disabled too? keep track of unclustered markers?
		 	   // TODO: keyboard enable? what to do onclick?
		 	   if (enable) {
		 	     var clusterLayer = L.markerClusterGroup({maxClusterRadius: 32, showCoverageOnHover: false,
		 	       iconCreateFunction: function (cluster) {
		 	               var sizeClass='sm';
		 	               var baseSize = 20;
		 	               var increaseSize = 6;
		 	               var iconSize = L.point(baseSize, baseSize);
										 if (cluster.getChildCount() >=10) {
		 	                 sizeClass='md';
		 	                 iconSize = L.point(baseSize + increaseSize, baseSize + increaseSize)
		 	               } else if (cluster.getChildCount() >=100) {
		 	                 sizeClass='lg';
		 	                 iconSize = L.point(baseSize + 2*increaseSize, baseSize + 2*increaseSize);
		 	               }
		 	               return L.divIcon({ html: '<b>' + cluster.getChildCount() + '</b>', className: 'clusterIcon-'+sizeClass, iconSize: iconSize });
		 	             }
		 	     });

		 	     IKaart.map.addLayer(clusterLayer);

		 	     // TODO: is it save to assume that all point layers should be in the clusterlayer?
					 // for this map it seems to be like a good selection
		 	     IKaart.unclusteredLayers = []
		 	     IKaart.map.eachLayer(function(layer) {
		 	         IKaart.unclusteredLayers.push(layer);
		 	         if (layer.feature) {
		 	           if (layer.feature.geometry.type==="Point") {
		 	             clusterLayer.addLayer(layer);
		 	             IKaart.map.removeLayer(layer);
		 	           }
		 	         }
		 	     });
		 	     // keep track of the clusterLayer too, to make it removable
		 	     IKaart.clusterLayer = clusterLayer;
		 	   } else {
		 	     if (IKaart.unclusteredLayers.length > 0) {
		 	       // remove the clusterlayer?
		 	       for (var l in IKaart.unclusteredLayers) {
		 	         IKaart.map.addLayer(IKaart.unclusteredLayers[l]);
		 	       }
		 	     }
		 	     IKaart.map.removeLayer(IKaart.clusterLayer);
		 	     IKaart.clusterLayer = {};
		 	   }
		 	 }

		 	 IKaart.init = function(className) {
				 // use a custon prefix for led
				 L.AwesomeMarkers.Icon.prototype.options.prefix = 'led';
		 	 	// init the map
		 	 	// TODO: configuration map-id
		 	 	// TODO: parse the HTML and create a div over the list
		 	 	if (!IKaart.map) {
		 	 		IKaart.map = L.map('initiatieven-kaart-map', {'maxZoom': 18, scrollWheelZoom:true}).setView([52.1, 5.2], 7);
		 	 		const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
		 	 		const osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
		 	 		const osm = new L.TileLayer(osmUrl, {
		 	 				attribution: osmAttrib
		 	 		});
		 	 		IKaart.map.addLayer(osm);
		 	 		const markers = IKaart.parseLocationData(className);
		 	 	}
		 	   IKaart.enableClusters(false);
		 	 }

			 IKaart.init(IKaart.classNameMapObjects);

	 });


})( jQuery );

// create the object in the window scope, TODO: refactor for proper class?
IKaart = {};
IKaart.classNameMapObjects = 'map-object';
