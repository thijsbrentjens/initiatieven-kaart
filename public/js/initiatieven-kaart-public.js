(function ($) {
  'use strict';

  class HTMLItemsMap {

    constructor(config) {
			this.mapItemsContainerId = "map-items";
      this.mapItemClass = "map-item";
			this.mapClass = "archives-map";
			// Overrule config defaults, for now for all properties
			Object.assign(this, config);

			this.mapElementId = this.mapItemsContainerId + "-mapObject";

			// other props
			this._map = false;
			this.unclusteredLayers = [];
			this.clusterLayer = {};
			this.features = [];
    }

    getLMap() {
      return this._map;
    }

		setLMap(mapObject) {
			this._map = mapObject;
		}

    test() {
      console.log(this.mapItemClass);
			console.log(this.mapElementId);
			console.log(this.mapItemsContainerId);
    }

    initMap() {
			// console.log("initMap")
      // first: detect if there are map-objects available
      if ($("." + this.mapItemClass).length == 0) {
        return false;
      }

      // use a custon prefix for led
      L.AwesomeMarkers.Icon.prototype.options.prefix = 'led';
      // init the map
      // TODO: configuration map-id
      // TODO: parse the HTML and create a div over the list
      if (!this.getLMap()) {
        // create a div
        // initiatieven-kaart-map
				// check if the element exists, of not: create one
				if ($("#"+this.mapElementId).length == 0 ){
					const _self = this;
					// TODO: where to append? now use a config
					// after the container, append?
					// TODO: aria label for the map
					const mapDivHtml = `<div id="${this.mapElementId}" class="${this.mapClass}"></div>`;
					$("#"+this.mapItemsContainerId).after(mapDivHtml);

					// create and (no jquery)
					const toggleListButton = Object.assign(document.createElement('button'), {
						textContent: 'Toon de lijst',
						id: "toggleListMapButton",
						title: "Wissel tussen het tonen van de kaart en de lijst",
						onclick (ev) {
							_self.toggleListMap();
						},
					});
					// before: toggle
					$("#"+this.mapItemsContainerId).before(toggleListButton);
					// hide the list
					$("#"+this.mapItemsContainerId).hide();

				}

        const mapObject = L.map(this.mapElementId, {
          'maxZoom': 18,
          scrollWheelZoom: true
        }).setView([52.1, 5.2], 7);
				this.setLMap(mapObject);

        const osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        const osmAttrib = 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
        const osm = new L.TileLayer(osmUrl, {
          attribution: osmAttrib
        });
        this.getLMap().addLayer(osm);
        const markers = this.parseLocationData();
      }
      // IKaart.enableClusters(false);
    }

    parseLocationData() {
      // to avoid scope issues
      const _self = this;
      const features = [];
      $("." + this.mapItemClass).each(function (cntr, elem) {
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
          return L.marker(latlng, {
            icon: customIcon
          });
        }
      }).bindPopup(function (layer) {
        return layer.feature.properties.popupContent;
      }).addTo(_self.getLMap());
      // update the data, for later usage like removal of layers or whatever..
      _self.features = features;
      return features;
    }

		toggleListMap(){
			// console.log("toggle");
			if ($("#"+this.mapElementId).is(":visible")) {
				// hide the map, show the list
				$("#"+this.mapElementId).hide();
				$("#"+this.mapItemsContainerId).show();
				// TODO: config of id for button?
				$("#toggleListMapButton").html("Toon de kaart");
			} else {
				// the other way around
				$("#"+this.mapElementId).show();
				$("#"+this.mapItemsContainerId).hide();
				$("#toggleListMapButton").html("Toon de lijst");
			}
			return true;
		}

		// TODO: refactor, for class HTMLItemsMap
    enableClusters(enable) {
      // Configuration of clustering?
      // What if clustering should be disabled too? keep track of unclustered markers?
      // TODO: keyboard enable? what to do onclick?
      if (enable) {
        var clusterLayer = L.markerClusterGroup({
          maxClusterRadius: 32,
          showCoverageOnHover: false,
          iconCreateFunction: function (cluster) {
            var sizeClass = 'sm';
            var baseSize = 20;
            var increaseSize = 6;
            var iconSize = L.point(baseSize, baseSize);
            if (cluster.getChildCount() >= 10) {
              sizeClass = 'md';
              iconSize = L.point(baseSize + increaseSize, baseSize + increaseSize)
            } else if (cluster.getChildCount() >= 100) {
              sizeClass = 'lg';
              iconSize = L.point(baseSize + 2 * increaseSize, baseSize + 2 * increaseSize);
            }
            return L.divIcon({
              html: '<b>' + cluster.getChildCount() + '</b>',
              className: 'clusterIcon-' + sizeClass,
              iconSize: iconSize
            });
          }
        });

        IKaart.map.addLayer(clusterLayer);

        // TODO: is it save to assume that all point layers should be in the clusterlayer?
        // for this map it seems to be like a good selection
        IKaart.unclusteredLayers = []
        IKaart.map.eachLayer(function (layer) {
          IKaart.unclusteredLayers.push(layer);
          if (layer.feature) {
            if (layer.feature.geometry.type === "Point") {
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

  }

  // create the object in the window scope, TODO: refactor for proper class?
  // TODO: what is the best Wordpress approach for this?
  //

  // IKaart = {"mapItemClass": 'map-objects'};
  // IKaart.mapItemClass = 'map-object';
  // now add all functions?

  $(function () {

			const fullConfig = {
				"mapItemsContainerId": "map-items",
		    "mapItemClass": "map-item",
				// optional: other map id for styling for example?
				// TODO: extra styling class?
				"mapElementId": "initiatieven-kaart-map",
				"mapClass": "archives-map"
		  }

			const minConfig = {
				"mapItemsContainerId": "map-items",
		    "mapItemClass": "map-item",
			}

		  const itemsMap = new HTMLItemsMap(minConfig);
		  // itemsMap.test()
		  itemsMap.initMap()


    //
    // IKaart.unclusteredLayers = [];
    // IKaart.clusterLayer = {};
    // IKaart.features = [];

    // IKaart.parseLocationData = function(className)
    // var IKaart = {};
    // IKaart.enableClusters = function (enable) {
    //   // Configuration of clustering?
    //   // What if clustering should be disabled too? keep track of unclustered markers?
    //   // TODO: keyboard enable? what to do onclick?
    //   if (enable) {
    //     var clusterLayer = L.markerClusterGroup({
    //       maxClusterRadius: 32,
    //       showCoverageOnHover: false,
    //       iconCreateFunction: function (cluster) {
    //         var sizeClass = 'sm';
    //         var baseSize = 20;
    //         var increaseSize = 6;
    //         var iconSize = L.point(baseSize, baseSize);
    //         if (cluster.getChildCount() >= 10) {
    //           sizeClass = 'md';
    //           iconSize = L.point(baseSize + increaseSize, baseSize + increaseSize)
    //         } else if (cluster.getChildCount() >= 100) {
    //           sizeClass = 'lg';
    //           iconSize = L.point(baseSize + 2 * increaseSize, baseSize + 2 * increaseSize);
    //         }
    //         return L.divIcon({
    //           html: '<b>' + cluster.getChildCount() + '</b>',
    //           className: 'clusterIcon-' + sizeClass,
    //           iconSize: iconSize
    //         });
    //       }
    //     });
		//
    //     IKaart.map.addLayer(clusterLayer);
		//
    //     // TODO: is it save to assume that all point layers should be in the clusterlayer?
    //     // for this map it seems to be like a good selection
    //     IKaart.unclusteredLayers = []
    //     IKaart.map.eachLayer(function (layer) {
    //       IKaart.unclusteredLayers.push(layer);
    //       if (layer.feature) {
    //         if (layer.feature.geometry.type === "Point") {
    //           clusterLayer.addLayer(layer);
    //           IKaart.map.removeLayer(layer);
    //         }
    //       }
    //     });
    //     // keep track of the clusterLayer too, to make it removable
    //     IKaart.clusterLayer = clusterLayer;
    //   } else {
    //     if (IKaart.unclusteredLayers.length > 0) {
    //       // remove the clusterlayer?
    //       for (var l in IKaart.unclusteredLayers) {
    //         IKaart.map.addLayer(IKaart.unclusteredLayers[l]);
    //       }
    //     }
    //     IKaart.map.removeLayer(IKaart.clusterLayer);
    //     IKaart.clusterLayer = {};
    //   }
    // }

    // IKaart.init(IKaart.mapItemClass);

  });

})(jQuery);
