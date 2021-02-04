(function ($) {
    'use strict';

    class HTMLItemsMap {

        constructor(config) {
            this.mapItemsContainerId = "map-items";
            this.mapItemClass = "map-item";
            this.mapClass = "archives-map";
            this.clustering = true;
            this.iconSpiderfiedOpacity = 0.75;

            // labels: also Uppercase?
            this.typeLabels = {
                "onbekend": "Onbekend",
                "community": "Community",
                "datalab": "Datalab",
                "lab": "Datalab",
                "portaal": "Portaal",
                "strategie": "Strategie",
                "visualisatie": "Visualisatie",
            }

            // Overrule config defaults, for now for all properties
            Object.assign(this, config);

            this.mapElementId = this.mapItemsContainerId + "-mapObject";
            this.typeFilterControlTxtId = "typeFilterControlTxt";
            this.typeFilterControlContent = "";

            // other props
            this._map = false;
            this.pointsLayer;
            this.unclusteredLayers = [];
            this.clusterLayer = {};
            this.features = [];
            this.types = {};
            // icon (wxh): 30 x 40
            // best is to make nice numbers for ratio 3:4
            this.iconHeight = 32;
            this.iconWidth = Math.round(0.75 * this.iconHeight);

            this.baseIcon = L.Icon.extend({
                options: {
                    // PvB: ik heb de iconUrl aangepast en ervoor gezorgd dat der geen 404 meer
                    // optreedt.
                    // TB: ik heb het nog iets verder aangepast: rekening houden met een langer pad (bij mij draait deze installatie bijvoorbeeld op http://...domein../led/). De siteurl wordt door WP weggeschreven in een javascript object via de public class: public/class-initiatieven-kaart-public.php
                    shadowUrl: `${Utils.siteurl}/wp-content/plugins/initiatieven-kaart/public/css/images/marker-shadow.svg`,
                    iconSize: [this.iconWidth, this.iconHeight],
                    iconAnchor: [this.iconWidth / 2, this.iconHeight],
                    // shadow: 40 x 40
                    // shadowsize image is sqaure now, so 2x iconHeight
                    shadowSize: [this.iconHeight, this.iconHeight],
                    shadowAnchor: [this.iconWidth / 3, this.iconHeight],
                    popupAnchor: [0, -1 * this.iconHeight]
                }
            })

            this.unspiderfied = false;

        }

        getLMap() {
            return this._map;
        }

        setLMap(mapObject) {
            this._map = mapObject;
        }

        initMap() {
            const _self = this;
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
                if ($("#" + this.mapElementId).length == 0) {
                    const _self = this;
                    // append after the container
                    // TODO: aria label for the map
                    const mapDivHtml = `<div id="${this.mapElementId}" class="${this.mapClass}" tabindex="0" aria-label="Kaart met initiatieven"></div>`;
                    $("#" + this.mapItemsContainerId).after(mapDivHtml);

                    // create the toggle button
                    // TODO: labels
                    const toggleListButton = Object.assign(document.createElement('button'), {
                        textContent: 'Toon de lijst',
                        id: "toggleListMapButton",
                        title: "Wissel tussen het tonen van de kaart en de lijst",
                        onclick(ev) {
                            _self.toggleListMap();
                        }
                    });
                    // before: toggle
                    $("#" + this.mapItemsContainerId).before(toggleListButton);
                    // hide the list
                    $("#" + this.mapItemsContainerId).hide();

                }

                // initial zoom will be overwritten by the bounds of the data layer
                const mapObject = L.map(this.mapElementId, {
                    'maxZoom': 18,
                    // scrollWheelZoom: true,
                    gestureHandling: true
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

                // add the control to zoom to the pointslayer:
                const zoomToAllControl = this.createZoomToAllControl();
                this.getLMap().addControl(zoomToAllControl);

                // on zoom:
                this.getLMap().on("zoomend", function () {
                    // wait a while, not nice, but we need the browser to be ready rendering the items (and updating the DOM)
                    setTimeout(_self.bindClusterIconEnter, 100);

                    _self.bindFocusToIcons();
                    _self.fixAccessibilityIssues();
                })

                _self.previousOpened = null;
                // use previousopened to set focus back?
                _self.previousFocus = null;

                this.getLMap().on("popupopen", function (evt) {
                    // find the first link in the contentNode, this is the header
                    // or use the _closeButton:
                    // $(evt.popup._closeButton).focus()
                    // $(evt.popup._contentNode).find("a").focus();
                    // focus on the content element
                    $(evt.popup._contentNode).find("div.leaflet-popup-content").focus();
                    // TODO: if "esc" is chosen, close the popup

                });
                this.getLMap().on("popupclose", function (evt) {
                    try {
                        _self.previousFocus.focus();
                    } catch (e) {
                    }
                })

            }
            this.enableClusters(this.clustering);
            this.fixAccessibilityIssues();
        }

        // an internal function to update the layer?
        pointToLayer(feature, latlng, scope) {
            // create a customicon

        }

        bindFocusToIcons() {
            // bind focus events on each marker
            const _self = this;
            $(".leaflet-marker-icon").each(function (elem) {
                // first remove focus events?
                $(this).unbind("focus");
                $(this).bind("focus", function (evt) {
                    _self.previousFocus = evt.currentTarget;
                })
            });
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

                    // maxWidth: 800
                    const category = $(elem).data("map-item-type") ? $(elem).data("map-item-type") : "onbekend";
                    // let's create a nice geojson feature
                    if (category) {
                        if (types[category]) {
                            types[category]["nrPosts"] += 1;
                        } else {
                            // default to true for filtering?
                            types[category] = {"nrPosts": 0, "visible": true};
                        }
                    }
                    // try to find a nice title
                    let title = category;
                    if (category in _self.typeLabels) title = _self.typeLabels[category];

                    if ($(elem).find("h2>a")) {
                        // TODO: savely get the content? What if HTMl chars are in the title?
                        // title = $(elem).find("h2>a").html();
                        // for now: fall back to the label
                    }

                    const feature = {
                        "type": "Feature",
                        "properties": {
                            "category": category,
                            "popupContent": elem.innerHTML,
                            "title": title
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

            // recreate the Geojson layer
            const pointsLayer = this.createPointsLayer(features, _self)

            // Set initial zoom to the layer data
            const bounds = pointsLayer.getBounds();
            // add a margin around the bounds, to avoid points being too close on the edge of the map. Issue #27
            _self.getLMap().fitBounds(bounds, {padding: [50, 50]})
            this.pointsLayer = pointsLayer;
            // update the data, for later usage like removal of layers or whatever..
            this.features = features;
            return features;
        }

        createZoomToAllControl() {
            let _self = this;
            var ZoomAllControl = L.Control.extend({
                options: {
                    position: 'topleft'
                },
                onAdd: function (map) {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                    const a = L.DomUtil.create('a', 'leaflet-control-zoomall', container);
                    a.innerHTML = '&harr;'; // '&#8596;'
                    // a.innerHTML = `<img src="${Utils.siteurl}/wp-content/plugins/initiatieven-kaart/public/css/images/zoomall.svg"/>`;
                    a.href = '#';
                    a.title = "Toon alles";
                    a.onclick = function () {
                        _self.getLMap().fitBounds(_self.pointsLayer.getBounds(), {padding: [50, 50]});
                        return false;
                    }
                    // container.append(a);

                    return container;
                },
            });
            return new ZoomAllControl();
        }


        createPointsLayer(features, _self) {
            const layer = L.geoJSON(features, {
                pointToLayer: function (feature, latlng) {
                    // wrapper function
                    var category = feature.properties.category ? feature.properties.category : "onbekend";
                    var labelTxt = _self.typeLabels[category];
                    if (feature.properties.title) {
                        labelTxt = feature.properties.title;
                    }
                    if (_self.types[feature.properties.category]) {
                        if (_self.types[feature.properties.category].visible == true) {
                            // TODO: properly update the clustericons: only update counters, but keep same position
                            // For now very hard, maybe next stage
                        } else {
                            return false;
                        }
                    }
                    // TODO: multiple categories?
                    // multiple categories are postponed for now (10-12-2020)
                    // current categories:
                    // portaal, datalab, community, onbekend, strategie, visualisatie
                    const customIcon = new _self.baseIcon({
                        // customize according to category
                        // PvB: ik heb de iconUrl aangepast en ervoor gezorgd dat der geen 404 meer
                        // optreedt.
                        // TB: de URL moet ook de basis bevatten (bij mij lokaal staat er nog /led/ voor). Nog een kleine aanpassing gedaan.
                        iconUrl: _self.getIconURL(category),
                    });
                    let lbl = "Icoon voor initiatief " + labelTxt;
                    let marker = L.marker(latlng, {
                        icon: customIcon,
                        alt: lbl,
                        title: labelTxt
                    });
                    return marker
                }
            }).bindPopup(function (layer) {
                return layer.feature.properties.popupContent;
            }).addTo(_self.getLMap());
            return layer;
        }

        getIconURL(category) {
            return `${Utils.siteurl}/wp-content/plugins/initiatieven-kaart/public/css/images/marker-${category.toLowerCase()}.svg`;
        }

        createTypeFilterControl() {
            const _self = this;
            var TypeFilterControl = L.Control.extend({
                options: {
                    position: 'topright'
                },
                onAdd: function (map) {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom map-item-control-types');
                    container.style.backgroundColor = 'white';
                    container.style.width = '200px';
                    container.style.height = 'auto';
                    const clusterControlTxt = L.DomUtil.create('div', _self.typeFilterControlTxtId, container);
                    clusterControlTxt.innerHTML = '';
                    clusterControlTxt.id = _self.typeFilterControlTxtId;
                    return container;
                },
            });

            const typeFilterControl = new TypeFilterControl();
            return typeFilterControl;
        }

        toggleType(_self, category, show) {
            var visible = false;
            if (show == undefined) {
                visible = false;
            }
            if (show) {
                visible = true;
            }
            _self.types[category]["visible"] = visible;
            // recreate layer
            _self.recreatePointsLayer()

        }

        recreatePointsLayer() {

            this.pointsLayer.remove();
            this.getLMap().removeLayer(this.clusterLayer);
            this.pointsLayer = this.createPointsLayer(this.features, this);
            // enable clustering again? use setting for this?
            this.enableClusters(this.clustering);
            // fix accessibility issues Leaflet: quite ugly this way, but faster than in Leaflet itself. If fixes are adequate try to port it to Leaflet core
            this.fixAccessibilityIssues();
        }

        fixAccessibilityIssues() {
            // tabindex, labels, roles for elements where this apparerently can;t be done at initialization
            $(".leaflet-marker-icon:not('.clusterIcon')").attr("role", "button").attr("aria-label", "Knop om een initiatief te tonen op deze locatie").attr("tabindex", "0");
            // clustericon: multiple
            $(".clusterIcon").attr("role", "button").attr("aria-label", "Knop om meerdere initiatieven te tonen op deze locatie").attr("tabindex", "0");
            // custom controls:
            $(".leaflet-control-zoomall").attr("role", "button").attr("aria-label", "Toon alles").attr("tabindex", "0");

            // shadows: explicit hide these
            $(".leaflet-marker-shadow").attr("aria-hidden", "true");

            $(".leaflet-overlay-pane svg").attr("role", "presentation").attr("aria-label", "Kaart met initiatieven");

            // no tabindex: the links inside should be accessible only
            $(".leaflet-control-attribution").attr("tabindex", "0").attr("aria-label", "Attribution");
        }

        createTypeFilterControlContent() {
            const _self = this;

            // sort by keys
            const typeKeys = Object.keys(this.types);
            typeKeys.sort();

            let filterContent = $(`<h4>`).html(`Toon initiatieven van:`);
            let filterContentList = $(`<ul>`);
            for (var k in typeKeys) {
                const category = typeKeys[k];
                let labelTxt = category;
                if (category in this.typeLabels) {
                    labelTxt = this.typeLabels[category];
                }
                const nrPosts = this.types[category].nrPosts;
                const inputId = `post-${category}`;
                const checkedTxt = (this.types[category].visible == false) ? "" : "checked";
                let input = $(`<input type="checkbox" id="${inputId}" ${checkedTxt}/>`);
                // note the scope _self
                $(input).on('change', function (evt) {
                    _self.toggleType(_self, category, evt.target.checked)
                });
                let imgTitle = `Icoon voor ${labelTxt}`;
                let img = $(`<img>`).attr('src', this.getIconURL(category)).attr('title', imgTitle).attr('alt', imgTitle).attr('aria-hidden', 'true');
                let labelElem = $(`<label for="${inputId}">${labelTxt} (${nrPosts})</label>`);

                let li = $(`<li>`).append(input).append(img).append(labelElem);
                filterContentList.append(li);
            }
            // filterContent.append(filterContentList);
            $("#" + this.typeFilterControlTxtId).html(filterContent).append(filterContentList);
            return filterContent;
        }

        toggleListMap() {
            if ($("#" + this.mapElementId).is(":visible")) {
                // hide the map, show the list
                $("#" + this.mapElementId).hide();
                $("#" + this.mapItemsContainerId).show();
                $("#toggleListMapButton").html("Toon de kaart");
            } else {
                // the other way around
                $("#" + this.mapElementId).show();
                $("#" + this.mapItemsContainerId).hide();
                $("#toggleListMapButton").html("Toon de lijst");
            }
            this.getLMap().invalidateSize();
            return true;
        }

        enableClusters(enable) {
            // Configuration of clustering via init function
            const _self = this;
            this.clustering = enable;
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
                            // html: `<span title='Meerdere initiatieven' tabindex='0' role='button' aria-label='Toont meerdere initiatieven op deze locatie'>${cluster.getChildCount()}</span>`,
                            html: `<span title='Meerdere initiatieven'>${cluster.getChildCount()}</span>`,
                            className: 'clusterIcon clusterIcon-' + sizeClass,
                            iconSize: iconSize
                        });
                    }
                });

                clusterLayer.on('click', function (evt) {
                    // an individual marker is clicked, use evt.layer for that
                    // _self.previousOpened = evt.layer;
                });

                _self.unspiderfied = false;
                clusterLayer.on('clusterclick', function (evt) {
                    // make sure that another enter or click closes the clustericon again
                    if (_self.unspiderfied == false) {
                        // close cluster
                        evt.layer.unspiderfy();
                        _self.fixAccessibilityIssues();
                    }
                });

                clusterLayer.on('unspiderfied', function (evt, markers) {
                    // set focus back tot the icon of the marker
                    // need to use internal references, becauase there is no proper getter in Leaflet to get the icon element
                    evt.cluster._icon.focus();
                    _self.unspiderfied = true;
                    _self.fixAccessibilityIssues();
                });

                clusterLayer.on('spiderfied', function (evt) {
                    // get the icons and focus on one of them
                    // overrule the hardcoded opacity of .3 in leaflet.markercluster.js
                    // can't be done with CSS unfortunately
                    // add the focus elements:
                    _self.bindFocusToIcons();
                    evt.cluster.setOpacity(_self.iconSpiderfiedOpacity)
                    if (evt.markers) {
                        if (evt.markers.length > 0) {
                            evt.markers[0]._icon.focus();
                        }
                    }
                    _self.unspiderfied = false;
                    _self.fixAccessibilityIssues();
                });

                // for this map it seems to save to assume that all point layers should be in the clusterlayer
                _self.unclusteredLayers = [];
                // this will keep track of all the subgroup arrays
                _self.subgroups = {}
                _self.getLMap().eachLayer(function (layer) {
                    _self.unclusteredLayers.push(layer);
                    if (layer.feature) {
                        if (layer.feature.geometry.type === "Point") {
                            clusterLayer.addLayer(layer);
                            _self.getLMap().removeLayer(layer);
                        }
                    }
                });
                // after forming the groups, add them
                // mySubGroup = L.featureGroup.subGroup(clusterLayer, arrayOfMarkers);

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

            this.bindFocusToIcons();
            this.bindClusterIconEnter();

        }

        bindClusterIconEnter() {
            // add focusable elements around the markers. Do this for each clusterIcon, also after zoom
            let eventedIcons = 0;
            // create a list of icons in the map view that should only be accessible by TAB?

            $(".clusterIcon").each(function (elem) {
                eventedIcons++;
                $(this).keypress(function (event) {
                    var keycode = (event.keyCode ? event.keyCode : event.which);
                    // on enter or spacebar:
                    // TODO: spacebar is nasty, need to interfere with default browser behaviour
                    // 13 = enter, 32 = spacebar
                    // if(keycode == '13' || keycode == '32')
                    if (keycode == '13') {
                        // zoom to clustericon
                        // trigger a click on enter.
                        $(this).click();
                        event.preventDefault();
                        return false;
                    }
                    event.stopPropagation();
                })
            });
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
            "mapClass": "archives-map",
            "clustering": true,
            "iconSpiderfiedOpacity": 0.75
        }

        const minConfig = {
            "mapItemsContainerId": "map-items",
            "mapItemClass": "map-item",
        }

        const itemsMap = new HTMLItemsMap(fullConfig);
        itemsMap.initMap();

    });

})(jQuery);
