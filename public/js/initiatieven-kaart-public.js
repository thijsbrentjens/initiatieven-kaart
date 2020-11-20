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
      this.typeFilterControlTxtId = "typeFilterControlTxt";
      this.typeFilterControlContent = "";

			// other props
			this._map = false;
			this.unclusteredLayers = [];
			this.clusterLayer = {};
			this.features = [];
			// todo: init directly?
      this.types = {};
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
      // For now: use ion as icon
      L.AwesomeMarkers.Icon.prototype.options.prefix = 'ion';

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

        // initial zoom will be overwritten by the bounds of the data layer
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
        const features = this.parseLocationData();
        // after parsing the location data, create a control to filter
        const typeFilterControl = this.createTypeFilterControl();
        this.getLMap().addControl(typeFilterControl);
        // now add the content:
        const content = this.createTypeFilterControlContent(this.types);
        // console.log(content)

      }
      // TODO: fix clusters
      this.enableClusters(true);
    }

    parseLocationData() {
      // to avoid scope issues
      const _self = this;
      const features = [];
      // reset the types
      const types = {};

      $("." + this.mapItemClass).each(function (cntr, elem) {
				// for all elements with latitude and longitude, add a marker
        if ($(elem).data("latitude") && $(elem).data("longitude")) {
          const lat = $(elem).data("latitude");
          const lon = $(elem).data("longitude");
          // TODO: max Width?
          // maxWidth: 800
          const category = $(elem).data("map-item-type") ? $(elem).data("map-item-type") : "onbekend";
          // let's create a nice geojson feature
          if (category) {
            if (types[category]) {
              types[category] += 1;
            } else {
              types[category] = 1;
            }
          }
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
      this.features = features;
      this.types = types;
      // baseIcon:

      // icon (wxh): 30 x 40
      // best is to make nice numbers for ratio 3:4
      const iconHeight = 32;
      const iconWidth = Math.round(0.75 * iconHeight);

      const baseIcon = L.Icon.extend({
        options: {
            // PvB: ik heb de iconUrl aangepast en ervoor gezorgd dat der geen 404 meer
            // optreedt.
            // TB: ik heb het nog iets verder aangepast: rekening houden met een langer pad (bij mij draait deze installatie bijvoorbeeld op http://...domein../led/). De siteurl wordt door WP weggeschreven in een javascript object via de public class: public/class-initiatieven-kaart-public.php
          shadowUrl: `${Utils.siteurl}/wp-content/plugins/initiatieven-kaart/public/css/images/marker-shadow.svg`,
          iconSize: [iconWidth, iconHeight],
          iconAnchor: [iconWidth / 2, iconHeight],
          // shadow: 40 x 40
          // shadowsize image is sqaure now, so 2x iconHeight
          shadowSize: [iconHeight, iconHeight],
          shadowAnchor: [iconWidth / 3, iconHeight],
          popupAnchor: [0, -1 * iconHeight]
        }
      })


      const pointsLayer = L.geoJSON(features, {
        pointToLayer: function (feature, latlng) {
          // create a customicon
          var category = feature.properties.category ? feature.properties.category : "onbekend";
          // TODO: multiple?
          // current:
          // portaal, datalab, community, onbekend, strategie, visualisatie
          const customIcon = new baseIcon({
            // customize according to category
              // PvB: ik heb de iconUrl aangepast en ervoor gezorgd dat der geen 404 meer
              // optreedt.
              // TB: de URL moet ook de basis bevatten (bij mij lokaal staat er nog /led/ voor). Nog een kleine aanpassing gedaan.
            iconUrl: `${Utils.siteurl}/wp-content/plugins/initiatieven-kaart/public/css/images/marker-${category}.svg`,
          });

          return L.marker(latlng, {
            icon: customIcon
          });
        }
      }).bindPopup(function (layer) {
        return layer.feature.properties.popupContent;
      }).addTo(_self.getLMap());

      // Set initial zoom to the layer data
      _self.getLMap().fitBounds(pointsLayer.getBounds())

      // update the data, for later usage like removal of layers or whatever..
      this.features = features;
      return features;
    }


    createTypeFilterControl() {
        const _self = this;
        var TypeFilterControl =  L.Control.extend({
        options: {
          position: 'topright'
          //control position - allowed: 'topleft', 'topright', 'bottomleft', 'bottomright'
        },
        onAdd: function (map) {
          const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom map-item-control-types');
          container.style.backgroundColor = 'white';
          container.style.width = '200px';
          container.style.height = 'auto';
          const clusterControlTxt = L.DomUtil.create('div', _self.typeFilterControlTxtId, container);
          clusterControlTxt.innerHTML = '<h4>Types</h4>';
          clusterControlTxt.id = _self.typeFilterControlTxtId;
          return container;
        },
      });

      const typeFilterControl = new TypeFilterControl();
      return typeFilterControl;
    }

    toggleType(_self, category, show) {
      var checked = false;
      if (show == undefined) {
        checked = false;
      }
      if (show) {
        checked = true;
      }
      _self.types[category]["checked"] = checked;

    }

    createTypeFilterControlContent() {
      // sort by keys
      const typeKeys = Object.keys(this.types);
      const _self = this;
      // console.log(this.types);
      typeKeys.sort();
      // var filterContent = "<h4>Initiatieven</h4>";
      var filterContent = $(`<ul>`);
      for (var k in typeKeys) {
        const category = typeKeys[k];
        const nrPosts = this.types[category];
        // TODO: checked?
        const inputId = `post-${category}`;
        const checkedTxt = (this.types[category].checked == false) ? "" : "checked";
        // TODO: the proper object? for a public function? global?
        var input = $(`<input type="checkbox" id="${inputId}" ${checkedTxt}/>`);
        // note the scope _self
        $(input).on('change', function() {
          _self.toggleType(_self, category, _self.types[category].checked)
        });
        var li = $(`<li>`).append(input).append(`<label for="${inputId}">${category} (${nrPosts})</label>`);
        filterContent.append(li)
        // filterContent += li;
      }
      // filterContent += `</ul>`;
      $("#" + this.typeFilterControlTxtId ).html(filterContent);
      return filterContent;
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
						var sizeClass = 'sm';
            const baseSize = 20;
            const increaseSize = 6;
            var iconSize = L.point(baseSize, baseSize);
            // TODO: SVG icon for clusters?
            if (cluster.getChildCount() >= 10) {
              sizeClass = 'md';
              iconSize = L.point(baseSize + increaseSize, baseSize + increaseSize)
            } else if (cluster.getChildCount() >= 100) {
              sizeClass = 'lg';
              iconSize = L.point(baseSize + 2 * increaseSize, baseSize + 2 * increaseSize);
            }
            return L.divIcon({
              html: '<b>' + cluster.getChildCount() + '</b>',
              className: 'clusterIcon clusterIcon-' + sizeClass,
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
          // remove the clusterlayer
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
