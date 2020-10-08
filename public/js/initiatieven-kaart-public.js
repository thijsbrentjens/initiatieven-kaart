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
			// todo: init directly?

    }

    getLMap() {
      return this._map;
    }

		setLMap(mapObject) {
			this._map = mapObject;
		}

    initMap() {
      // first: detect if there are map-objects available
      if ($("." + this.mapItemClass).length == 0) {
        return false;
      }

      // use a custon prefix for led
      L.AwesomeMarkers.Icon.prototype.options.prefix = 'led-icon';

      // init the map if it is not available yet
      if (!this.getLMap()) {
        // make sure all elements are avialable
				if ($("#"+this.mapElementId).length == 0 ){
					const _self = this;
					// append after the container
					// TODO: aria label for the map
					const mapDivHtml = `<div id="${this.mapElementId}" class="${this.mapClass}"></div>`;
					$("#"+this.mapItemsContainerId).after(mapDivHtml);

					// create the toggle button
					// TODO: labels
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
      this.enableClusters(true);
    }

    parseLocationData() {
      // to avoid scope issues
      const _self = this;
      const features = [];
      $("." + this.mapItemClass).each(function (cntr, elem) {
				// for all elements with latitude and longitude, add a marker
        if ($(elem).data("latitude") && $(elem).data("longitude")) {
          const lat = $(elem).data("latitude");
          const lon = $(elem).data("longitude");
          // TODO: max Width?
          // maxWidth: 800
          const category = $(elem).data("map-item-type") ? $(elem).data("map-item-type") : "unknown";
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
          const customIcon = L.AwesomeMarkers.icon({
            icon: category,
            // prefix: 'fa',
						// TODO: use CSS for styling?
            iconColor: 'black',
            markerColor: 'white'
          });
          return L.marker(latlng, {
            icon: customIcon,
						// TODO: title:
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
			this.getLMap().invalidateSize();
			return true;
		}

		// TODO: why are only clusters shown?
    enableClusters(enable) {
      // Configuration of clustering?
      // What if clustering should be disabled too? keep track of unclustered markers?
      // TODO: keyboard enable? what to do onclick?
			const _self = this;
      if (enable) {
        const clusterLayer = L.markerClusterGroup({
          maxClusterRadius: 32,
          showCoverageOnHover: false,
          iconCreateFunction: function (cluster) {
						const sizeClass = 'sm';
            const baseSize = 20;
            const increaseSize = 6;
            const iconSize = L.point(baseSize, baseSize);
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

        // TODO: is it save to assume that all point layers should be in the clusterlayer?
        // for this map it seems to be like a good selection
        _self.unclusteredLayers = [];

        _self.getLMap().eachLayer(function (layer) {
          _self.unclusteredLayers.push(layer);
          if (layer.feature) {
            if (layer.feature.geometry.type === "Point") {
              clusterLayer.addLayer(layer);
              _self.getLMap().removeLayer(layer);
            }
          }
        });

        _self.getLMap().addLayer(clusterLayer);
        // keep track of the clusterLayer too, to make it removable
        _self.clusterLayer = clusterLayer;
      } else {
        if (_self.unclusteredLayers.length > 0) {
          // remove the clusterlayer?
          for (const l in _self.unclusteredLayers) {
            _self.getLMap().addLayer(_self.unclusteredLayers[l]);
          }
        }
        _self.getLMap().removeLayer(_self.clusterLayer);
        _self.clusterLayer = {};
      }

    }

  }

  // now init the object
	// this could also be done somewhere else?
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
		  itemsMap.initMap()

  });

})(jQuery);
