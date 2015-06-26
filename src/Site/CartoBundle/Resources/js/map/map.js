var map, GPX, routeCreateControl, routeSaveControl, pointArray, latlngArray, polyline, tracepolyline, elevationScript, elevationChartScript,
    denivelep, denivelen, drawnItems, drawControl, currentLayer, el, mapgeojson, editDrawControl, segmentID, fetchingElevation, traceData, formerZoom, markerGroup, polyArray,
    radiusGroup, potentialPoly, routeButton, routeSaveButton,routeDeleteButton,routeCancelButton,autoButton,autoCancelButton,pogGroup,computePogs,pogBar,routeBar, geocoder;
var isCreateRoute = false;
var isCreateSegment = false;
var isEditSegment = false;
var isLoadingMap = false;
var supprSeg = false;
var is_reloading = false;
var map_load = false;
var elevationURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevation&shapeFormat=raw&unit=m";
var elevationURLAJAX = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&shapeFormat=raw&unit=m";
var elevationSegmentURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevationSegment&shapeFormat=raw&unit=m";
var elevationMultipleSegmentURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevationMultipleSegment&shapeFormat=raw&unit=m";
var graph = $("<img>").css("display", "none");
var latPoi, lngPoi, altPoi, idLieuPoi, iconePoi;
var roleMap;
var radius = 0;
var points_id_Group = {};
var liste_poly_detect;
var liste_poly_detect_point;
var liste_marqueur_detect;
var cursor_pos_lat;
var cursor_pos_lng;
var update_list_segment = {};
var points_tab_index = {};
var compteur_glob = 0 ;
var create_poly_segmentation_count = 0;
var table_pos_poly_courante = {};
var multiple_point_object_elevation = {};
var points_current_get_elevation;
var point_other_poly;
var save_points_elevation;


//constructeur objet point 
var Point = function (lat, lng) {
    this.lat = lat;
    this.lng = lng;
};

//constructeur objet TypeLieu
var TypeLieu = function (id, label, icone) {
    this.id = id;
    this.label = label;
    this.icone = icone;
};

//constructeur objet icone
var Icone = function (id, path) {
    this.id = id;
    this.path = path;
};

// ???
var editDraw = L.Control.extend({
    options: {
        position: 'topright'
    },

    onAdd: function (map) {
        // create the control container with a particular class name
        var container = L.DomUtil.create('div', 'editdraw');
        $(container).html("<button type='button' class='btn btn-default' id='editdraw'>Prolonger</button></div>");
        $("#editdraw").click(function () {
            map.fireEvent("draw:edited");
            map.fireEvent("draw:drawstart");
        });
        return container;
    }
});

//fonction d'initialisation de la map 
function init(callback, params) {
	//déclaration de l'objet carte
	//intègre la modal montrant l'ajout des POIs
    map = new L.map('map', {
        contextmenu: true,
        contextmenuWidth: 140,
        contextmenuItems: [{
            text: 'Ajouter POI',
            callback: function (data) {
                latPoi = data.latlng.lat;
                lngPoi = data.latlng.lng;
                altPoi = 1;
                $("#addpoi").modal('show');
            }
        }]
    });
	//chargments de tout les POIs existants dans la base de données
    loadLieux();
	//si le navigateur est autorisé à indiquer la géolocalisation du client
    if (navigator.geolocation) {
		//récupération de la position de l'utilisateur et appel de la fnction goToPosition
        navigator.geolocation.getCurrentPosition(goToPosition, showError);
    }
    else {
        goToPosition({"coords": {"latitude": 0, "longitude": 0}}, callback, params);
    }
	//ajout de l'overley qui affiche l'élévation d'un itinéraire
    addOverlay();
	//
    $('#savepoi').click(savePoi);
    if (typeof callback !== "undefined" && typeof params !== "undefined") {
		map_load = true;
        callback.apply(null, params);
    }
}

//fonction de chargement du rôle de l'utilisateur.
function loadRoleMap() {
    var role;
	//appel ajax vers l'adresse générée par symfony (se trouve dans le fichier routing.yml)
    $.ajax({
        url: Routing.generate('site_carto_getRoleMap'),
        type: 'POST',
        async: false,
        dataType: 'json',
        success: function (json, statut) {
            role=json['role'];
        },

        error: function (resultat, statut, erreur) {
        }
    });
    return role;
}

//fonction de chargement des lieux
function loadLieux() {
    var res = [];
    $.ajax({
        url: Routing.generate('site_carto_getAllLieux'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
		
			//rajoute les lieux trouvés à la suite de l'élément HTML possédant l'id typelieu
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].id).text(json[i].label);
                opt.appendTo("#typelieu");
            }
        },

        error: function (resultat, statut, erreur) {
        }
    });
    return res;
}

//fonction de chargement des POIs
function loadPois()
{
    $.ajax({
        url : Routing.generate('site_carto_getAllPois'),
        type : 'GET',
        dataType : 'json',
        success : function(json, statut){
		
			//on charge le role de l'utilisateur
            roleMap = loadRoleMap();
			//initialisation
            var icone;
            var marker;
			//pour chaque résultats du tableau json, on génère un POI
            for(var i = 0; i < json.length; i++)
            {
				//attribution des valeurs à l'icone 
                icone = L.icon({
                    iconUrl : json[i].typelieu.icone.path,
                    iconSize : [30, 30]
                });

				//si l'utilisateur est un admin ou un cartographe, on lui donne le droit de modifier et de supprimer les POIs
				//génération des modals pour chaque POIs
                if(roleMap == 1 || roleMap == 3)
                {
                    if(json[i].image != null)
                    {
                        if(json[i].image.path != null)
                        {
                            marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<div id='imgPoi' class='img-size' style='background-image: url(" + json[i].image.path + ");'></div> <p><b>" + json[i].titre + "</b></p><p>" + json[i].description + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + json[i].id + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + json[i].id + ")'>Modifier le POI</button>");
                            markerGroup.addLayer(marker);
                            marker.on("click", function (event) { markerSelectionne = event.target; });
                        }
                        else
                        {
                            marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<p><b>" + json[i].titre + "</b></p> <p>" + json[i].description + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + json[i].id + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + json[i].id + ")'>Modifier le POI</button>");
                            markerGroup.addLayer(marker);
                            marker.on("click", function (event) { markerSelectionne = event.target; });
                        }
                    }
                    else
                    {
                        marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<p><b>" + json[i].titre + "</b></p> <p>" + json[i].description + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + json[i].id + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + json[i].id + ")'>Modifier le POI</button>");
                        markerGroup.addLayer(marker);
                        marker.on("click", function (event) { markerSelectionne = event.target; });
                    }
                }
                else
                {
                    if(json[i].image != null)
                    {
                        if(json[i].image.path != null)
                        {
                            marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<div id='imgPoi' class='img-size' style='background-image: url(" + json[i].image.path + ");'></div> <p><b>" + json[i].titre + "</b></p><p>" + json[i].description + "</p>");
                            markerGroup.addLayer(marker);
                            marker.on("click", function (event) { markerSelectionne = event.target; });
                        }
                        else
                        {
                            marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<p><b>" + json[i].titre + "</b></p> <p>" + json[i].description + "</p>");
                            markerGroup.addLayer(marker);
                            marker.on("click", function (event) { markerSelectionne = event.target; });
                        }
                    }
                    else
                    {
                        marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<p><b>" + json[i].titre + "</b></p> <p>" + json[i].description + "</p>");
                        markerGroup.addLayer(marker);
                        marker.on("click", function (event) { markerSelectionne = event.target; });
                    }
                }
            }
        },

        error: function (resultat, statut, erreur) {
        }
    });
}

//Obtenir les coordonnées à partir du navigateur
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(goToPosition, showError);
    } else {
        goToPosition({"coords": {"latitude": 0, "longitude": 0}});
    }
}

//???
function showError(error) {
    goToPosition({"coords": {"latitude": 0, "longitude": 0}});
}

//Place la map à la position récupérée dans getLocation.
//cette fonction contient également la rédéfinition des classes draw et edit de la librairie leafletDraw
function goToPosition(position) {
    //Définition des attributs de la carte et positionnement
    $("#map").css("height", "100%").css("width", "100%").css("margin", "auto");
    var zoom = 3;
    if (position.coords.latitude !== 0 && position.coords.longitude !== 0) {
        zoom = 13;
    }
    formerZoom = zoom;
    map.setView([position.coords.latitude, position.coords.longitude], zoom);
	
    geocoder = L.Control.geocoder().addTo(map);
    geocoder.markGeocode = function(result) {
        this._map.fitBounds(result.bbox);

        if (this._geocodeMarker) {
            this._map.removeLayer(this._geocodeMarker);
        }

        this._geocodeMarker = new L.Marker(result.center)
            .bindPopup(result.html || result.name)
            .addTo(this._map)
            .openPopup();
			
		if(!is_reloading)
		{
			drawnItems.eachLayer(function (layer) {
				map.removeLayer(layer);
			});

			pogGroup.eachLayer(function (layer) {
				map.removeLayer(layer);
			});
			loadSegments();
			is_reloading = true;
		}
        return this;
    };

    //Ajout du fond de carte Landscape obtenu sur Thunderforest
    L.tileLayer('http://tile.thunderforest.com/landscape/{z}/{x}/{y}.png', {
        attribution: 'Landscape'
    }).addTo(map);

	//déclaration 
	//contient les marqueurs de toutes les polylines
    markerGroup = new L.LayerGroup();
	//contient tout les POGs des polylines
    pogGroup = new L.LayerGroup();
	//contient toutes les polylines 
    drawnItems = new L.FeatureGroup();
	//contient
	radiusGroup = new L.FeatureGroup();
	//le groupe drawnItems est ajouté à la map (c'est un layerGroup)
    map.addLayer(drawnItems);
    map.eachLayer(function (layer) {
        if ((layer instanceof L.Polyline) && !(layer instanceof L.Polygon)) {
            drawnItems.addLayer(layer);
        }
    });

	//redéfinition de l'une des classes de la librairie leafletDraw
	//rien n'a été modifié dans cette classe
    L.Draw.SegmentFeature = L.Draw.Feature.extend({
        includes: L.Mixin.Events,

        initialize: function (map, options) {
            this._map = map;
            this._container = map._container;
            this._overlayPane = map._panes.overlayPane;
            this._popupPane = map._panes.popupPane;

            // Merge default shapeOptions options with custom shapeOptions
            if (options && options.shapeOptions) {
                options.shapeOptions = L.Util.extend({}, this.options.shapeOptions, options.shapeOptions);
            }
            L.setOptions(this, options);
        },
        enable: function () {
            if (this._enabled) {
                return;
            }

            L.Handler.prototype.enable.call(this);

            this.fire('enabled', {handler: this.type});

            this._map.fire('draw:segmentstart', {layerType: this.type});
        },

        disable: function () {
            if (!this._enabled) {
                return;
            }

            L.Handler.prototype.disable.call(this);

            this._map.fire('draw:segmentstop', {layerType: this.type});

            this.fire('disabled', {handler: this.type});
        },
        addHooks: function () {
            var map = this._map;

            if (map) {
                L.DomUtil.disableTextSelection();

                map.getContainer().focus();

                this._tooltip = new L.Tooltip(this._map);

                L.DomEvent.on(this._container, 'keyup', this._cancelDrawing, this);
            }
        },

        removeHooks: function () {
            if (this._map) {
                L.DomUtil.enableTextSelection();

                this._tooltip.dispose();
                this._tooltip = null;

                L.DomEvent.off(this._container, 'keyup', this._cancelDrawing, this);
            }
        },

        setOptions: function (options) {
            L.setOptions(this, options);
        },

        _fireCreatedEvent: function (layer) {
            this._map.fire('draw:segmentcreated', {layer: layer, layerType: this.type});
        },

        // Cancel drawing when the escape key is pressed
        _cancelDrawing: function (e) {
            if (e.keyCode === 27) {
                this.disable();
            }
        }
    });

    //Création d'une polyline custom pour les segments
	//cette classe a été redéfinies pour implémenter des fonctionnalités lors du traçage d'un tronçon
	//(ici, un segment est un tronçon)
    L.Draw.SegmentPolyline = L.Draw.SegmentFeature.extend({
        statics: {
            TYPE: 'segmentpolyline'
        },

        Poly: L.Polyline,

        options: {
            allowIntersection: true,
            repeatMode: false,
            drawError: {
                color: '#b00b00',
                timeout: 2500
            },
            icon: new L.DivIcon({
                iconSize: new L.Point(8, 8),
                className: 'leaflet-div-icon leaflet-editing-icon'
            }),
            guidelineDistance: 20,
            maxGuideLineLength: 4000,
            shapeOptions: {
                stroke: true,
                color: 'blue',
                weight: 4,
                opacity: 0.5,
                fill: false,
                clickable: true
            },
            metric: true, // Whether to use the metric meaurement system or imperial
            showLength: true, // Whether to display distance in the tooltip
            zIndexOffset: 2000 // This should be > than the highest z-index any map layers
        },

        initialize: function (map, options) {
		
            this.options.drawError.message = L.drawLocal.draw.handlers.polyline.error;

            if (options && options.drawError) {
                options.drawError = L.Util.extend({}, this.options.drawError, options.drawError);
            }
            this.type = L.Draw.Polyline.TYPE;

            L.Draw.SegmentFeature.prototype.initialize.call(this, map, options);
        },

        addHooks: function () {
            L.Draw.SegmentFeature.prototype.addHooks.call(this);
            if (this._map) {
                this._markers = [];

                this._markerGroup = new L.LayerGroup();
                this._map.addLayer(this._markerGroup);

                this._poly = new L.Polyline([], this.options.shapeOptions);

                this._tooltip.updateContent(this._getTooltipText());

                if (!this._mouseMarker) {
                    this._mouseMarker = L.marker(this._map.getCenter(), {
                        icon: L.divIcon({
                            className: 'leaflet-mouse-marker',
                            iconAnchor: [20, 20],
                            iconSize: [40, 40]
                        }),
                        opacity: 0,
                        zIndexOffset: this.options.zIndexOffset
                    });
                }

                this._mouseMarker
                    .on('mousedown', this._onMouseDown, this)
                    .addTo(this._map);

                this._map
                    .on('mousemove', this._onMouseMove, this)
                    .on('mouseup', this._onMouseUp, this)
                    .on('zoomend', this._onZoomEnd, this);
            }
        },

        removeHooks: function () {
            L.Draw.SegmentFeature.prototype.removeHooks.call(this);

            this._clearHideErrorTimeout();

            this._cleanUpShape();

            // remove markers from map
            this._map.removeLayer(this._markerGroup);
            delete this._markerGroup;
            delete this._markers;

            this._map.removeLayer(this._poly);
            delete this._poly;

            this._mouseMarker
                .off('mousedown', this._onMouseDown, this)
                .off('mouseup', this._onMouseUp, this);
            this._map.removeLayer(this._mouseMarker);
            delete this._mouseMarker;

            // clean up DOM
            this._clearGuides();

            this._map
                .off('mousemove', this._onMouseMove, this)
                .off('zoomend', this._onZoomEnd, this);
        },

        deleteLastVertex: function () {
            if (this._markers.length <= 1) {
                return;
            }

            var lastMarker = this._markers.pop(),
                poly = this._poly,
                latlng = this._poly.spliceLatLngs(poly.getLatLngs().length - 1, 1)[0];

            this._markerGroup.removeLayer(lastMarker);

            if (poly.getLatLngs().length < 2) {
                this._map.removeLayer(poly);
            }

            this._vertexChanged(latlng, false);
        },

        addVertex: function (latlng) {
            var markersLength = this._markers.length;

            if (markersLength > 0 && !this.options.allowIntersection && this._poly.newLatLngIntersects(latlng)) {
                this._showErrorTooltip();
                return;
            }
            else if (this._errorShown) {
                this._hideErrorTooltip();
            }

            this._markers.push(this._createMarker(latlng));

            this._poly.addLatLng(latlng);

            if (this._poly.getLatLngs().length === 2) {
                this._map.addLayer(this._poly);
            }

            this._vertexChanged(latlng, true);
        },

        _finishShape: function () {
            var intersects = this._poly.newLatLngIntersects(this._poly.getLatLngs()[0], true);

            if ((!this.options.allowIntersection && intersects) || !this._shapeIsValid()) {
                this._showErrorTooltip();
                return;
            }
            this._fireCreatedEvent();
			
            this.disable();
            if (this.options.repeatMode) {
                this.enable();
            }
        },

        //Called to verify the shape is valid when the user tries to finish it
        //Return false if the shape is not valid
        _shapeIsValid: function () {
            return true;
        },

        _onZoomEnd: function () {
            this._updateGuide();
        },

        _onMouseMove: function (e) {
            var newPos = e.layerPoint,
                latlng = e.latlng;

            // Save latlng
            // should this be moved to _updateGuide() ?
            this._currentLatLng = latlng;

            this._updateTooltip(latlng);

            // Update the guide line
            this._updateGuide(newPos);

            // Update the mouse marker position
            this._mouseMarker.setLatLng(latlng);

            L.DomEvent.preventDefault(e.originalEvent);
        },

        _vertexChanged: function (latlng, added) {
            this._updateFinishHandler();

            this._updateRunningMeasure(latlng, added);

            this._clearGuides();

            this._updateTooltip();
        },

        _onMouseDown: function (e) {
            var originalEvent = e.originalEvent;
            this._mouseDownOrigin = L.point(originalEvent.clientX, originalEvent.clientY);
        },

        _onMouseUp: function (e) {
            if (this._mouseDownOrigin) {
                // We detect clicks within a certain tolerance, otherwise let it
                // be interpreted as a drag by the map
                var distance = L.point(e.originalEvent.clientX, e.originalEvent.clientY)
                    .distanceTo(this._mouseDownOrigin);
                if (Math.abs(distance) < 9 * (window.devicePixelRatio || 1)) {
                    this.addVertex(e.latlng);
                }
            }
            this._mouseDownOrigin = null;
        },

        _updateFinishHandler: function () {
            var markerCount = this._markers.length;
            // The last marker should have a click handler to close the polyline
            if (markerCount > 1) {
                this._markers[markerCount - 1].on('click', this._finishShape, this);
            }

            // Remove the old marker click handler (as only the last point should close the polyline)
            if (markerCount > 2) {
                this._markers[markerCount - 2].off('click', this._finishShape, this);
            }
        },
		
		//fonction qui a été modifiée 
        _createMarker: function (latlng) {
		
			var number_point = Object.keys(this._markerGroup._layers);

			//calcul de la zone de détection en fonction du radius
			
			//en plus de l'ajout de point, la fonction est chargée de détecté les "colisions" entre d'autre point.
			
			//si deux points possèdent les même coordonées, on superpose les deux points (leurs coordonnées sont identiques)
			var number_temp = 0;
            var radius = 5;
			
			//calcul pour attribuer un rayon de detection autour du point ou l'on clique
			number_temp = 0.0001*((radius/10)*(18-map.getZoom()+1));
			
			number_temp = number_temp + (0.0001*((radius/10) * (18-map.getZoom())));
			
			var full_poly_tab = drawnItems;
			var base_lat = latlng.lat;
			var base_lng = latlng.lng;

			var index_save = "";
			//ensuite on parcourt tout les points enregistrés dans la variables drawnItems
			$.each(full_poly_tab._layers, function(key_poly, val) {					

				$.each(val._latlngs, function(key, points) 
				{
						//si il y a une concordance (on arrondis pour éliminer les imperfections), 						
						//il peut y avoir plusieurs polyline dans l'intersection
						//structure du tableau : tab[id_poly][pos] 
						
						//on calcule la différence entre le point ou l'on a cliqué, et le point courant de la variable DrawItems
						
						dif_lat = base_lat - points.lat;
						dif_lng = base_lng - points.lng;
						
						//si le point courant se trouve dans le rayon calculé
						if((dif_lat < number_temp && dif_lat > -1*number_temp ) && (dif_lng < number_temp*2 && dif_lng > -1*number_temp*2))
						{
							//on set la longitude et la latitude du point ou l'on a cliqué sur le point détecté 
							latlng.lat = points.lat;
							latlng.lng = points.lng;
							
							//le tableau points_tab_index sert à stocker les points qui sont des POPS et qui sont des intersection entre un ou plusieurs tronçons.
							//si c'est le cas, 
							if(points_tab_index[key_poly] === undefined)
							{				
								points_tab_index[key_poly] = {};
								points_tab_index["poly_courante"] = {};
							}
							
							//on sauvegarde la position du point dans le tableau si ce n'est pas un POG.
							if(key != 0 && key != drawnItems._layers[key_poly]._latlngs.length-1)
							{							
								points_tab_index[key_poly][key] = key;
								points_tab_index["poly_courante"][compteur_glob] = latlng;
							}							
							
							//on sauvegarde le compteur dans le tableau : create_poly_segmentation_count
							//ce tableau est utilisé pour stocker les intersections de la polyline qu'on est en train de tracer.
							if(create_poly_segmentation_count != 0)
							{
								table_pos_poly_courante[create_poly_segmentation_count] = create_poly_segmentation_count;
							}
							//on incémente le compteur qui permet de défnir la position d'un point dans la polyline
							compteur_glob++;
						}						
					});
			});	
			
			//fin de l'ajout custom
		
            var marker = new L.Marker(latlng, {
                icon: this.options.icon,
                zIndexOffset: this.options.zIndexOffset * 2
            });

            this._markerGroup.addLayer(marker);

			create_poly_segmentation_count++;
			
            return marker;
        },

        _updateGuide: function (newPos) {
            var markerCount = this._markers.length;

            if (markerCount > 0) {
                newPos = newPos || this._map.latLngToLayerPoint(this._currentLatLng);

                // draw the guide line
                this._clearGuides();
                this._drawGuide(
                    this._map.latLngToLayerPoint(this._markers[markerCount - 1].getLatLng()),
                    newPos
                );
            }
        },

        _updateTooltip: function (latLng) {
            var text = this._getTooltipText();

            if (latLng) {
                this._tooltip.updatePosition(latLng);
            }

            if (!this._errorShown) {
                this._tooltip.updateContent(text);
            }
        },

        _drawGuide: function (pointA, pointB) {
            var length = Math.floor(Math.sqrt(Math.pow((pointB.x - pointA.x), 2) + Math.pow((pointB.y - pointA.y), 2))),
                guidelineDistance = this.options.guidelineDistance,
                maxGuideLineLength = this.options.maxGuideLineLength,
            // Only draw a guideline with a max length
                i = length > maxGuideLineLength ? length - maxGuideLineLength : guidelineDistance,
                fraction,
                dashPoint,
                dash;

            //create the guides container if we haven't yet
            if (!this._guidesContainer) {
                this._guidesContainer = L.DomUtil.create('div', 'leaflet-draw-guides', this._overlayPane);
            }

            //draw a dash every GuildeLineDistance
            for (; i < length; i += this.options.guidelineDistance) {
                //work out fraction along line we are
                fraction = i / length;

                //calculate new x,y point
                dashPoint = {
                    x: Math.floor((pointA.x * (1 - fraction)) + (fraction * pointB.x)),
                    y: Math.floor((pointA.y * (1 - fraction)) + (fraction * pointB.y))
                };

                //add guide dash to guide container
                dash = L.DomUtil.create('div', 'leaflet-draw-guide-dash', this._guidesContainer);
                dash.style.backgroundColor =
                    !this._errorShown ? this.options.shapeOptions.color : this.options.drawError.color;

                L.DomUtil.setPosition(dash, dashPoint);
            }
        },

        _updateGuideColor: function (color) {
            if (this._guidesContainer) {
                for (var i = 0, l = this._guidesContainer.childNodes.length; i < l; i++) {
                    this._guidesContainer.childNodes[i].style.backgroundColor = color;
                }
            }
        },

        // removes all child elements (guide dashes) from the guides container
        _clearGuides: function () {
            if (this._guidesContainer) {
                while (this._guidesContainer.firstChild) {
                    this._guidesContainer.removeChild(this._guidesContainer.firstChild);
                }
            }
        },

        _getTooltipText: function () {
            var showLength = this.options.showLength,
                labelText, distanceStr;

            if (this._markers.length === 0) {
                labelText = {
                    text: L.drawLocal.draw.handlers.polyline.tooltip.start
                };
            } else {
                distanceStr = showLength ? this._getMeasurementString() : '';

                if (this._markers.length === 1) {
                    labelText = {
                        text: L.drawLocal.draw.handlers.polyline.tooltip.cont,
                        subtext: distanceStr
                    };
                } else {
                    labelText = {
                        text: L.drawLocal.draw.handlers.polyline.tooltip.end,
                        subtext: distanceStr
                    };
                }
            }
            return labelText;
        },

        _updateRunningMeasure: function (latlng, added) {
            var markersLength = this._markers.length,
                previousMarkerIndex, distance;

            if (this._markers.length === 1) {
                this._measurementRunningTotal = 0;
            } else {
                previousMarkerIndex = markersLength - (added ? 2 : 1);
                distance = latlng.distanceTo(this._markers[previousMarkerIndex].getLatLng());

                this._measurementRunningTotal += distance * (added ? 1 : -1);
            }
        },

        _getMeasurementString: function () {
            var currentLatLng = this._currentLatLng,
                previousLatLng = this._markers[this._markers.length - 1].getLatLng(),
                distance;

            // calculate the distance from the last fixed point to the mouse position
            distance = this._measurementRunningTotal + currentLatLng.distanceTo(previousLatLng);

            return L.GeometryUtil.readableDistance(distance, this.options.metric);
        },

        _showErrorTooltip: function () {
            this._errorShown = true;

            // Update tooltip
            this._tooltip
                .showAsError()
                .updateContent({text: this.options.drawError.message});

            // Update shape
            this._updateGuideColor(this.options.drawError.color);
            this._poly.setStyle({color: this.options.drawError.color});

            // Hide the error after 2 seconds
            this._clearHideErrorTimeout();
            this._hideErrorTimeout = setTimeout(L.Util.bind(this._hideErrorTooltip, this), this.options.drawError.timeout);
        },

        _hideErrorTooltip: function () {
            this._errorShown = false;

            this._clearHideErrorTimeout();

            // Revert tooltip
            this._tooltip
                .removeError()
                .updateContent(this._getTooltipText());

            // Revert shape
            this._updateGuideColor(this.options.shapeOptions.color);
            this._poly.setStyle({color: this.options.shapeOptions.color});
        },

        _clearHideErrorTimeout: function () {
            if (this._hideErrorTimeout) {
                clearTimeout(this._hideErrorTimeout);
                this._hideErrorTimeout = null;
            }
        },

        _cleanUpShape: function () {
            if (this._markers.length > 1) {
                this._markers[this._markers.length - 1].off('click', this._finishShape, this);
            }
        },

        _fireCreatedEvent: function () {
            var poly = new this.Poly(this._poly.getLatLngs(), this.options.shapeOptions);
            L.Draw.SegmentFeature.prototype._fireCreatedEvent.call(this, poly);
        }
    });

    L.Draw.Segment = L.Draw.SegmentPolyline.extend({
        initialize: function (map, options) {
            this.type = 'segment';

            L.Draw.SegmentFeature.prototype.initialize.call(this, map, options);
        }
    });
	
//redefinition de la classe edit.

/*
 * L.Edit.Poly is an editing handler for polylines and polygons.
 */

L.Edit.Poly = L.Handler.extend({
	options: {
		icon: new L.DivIcon({
			iconSize: new L.Point(8, 8),
			className: 'leaflet-div-icon leaflet-editing-icon'
		})
	},

	initialize: function (poly, options) {
		this._poly = poly;
		L.setOptions(this, options);
	},

	addHooks: function () {
		var poly = this._poly;

		if (!(poly instanceof L.Polygon)) {
			//poly.options.editing.fill = false;
		}

		poly.setStyle(poly.options.editing);

		if (this._poly._map) {
			if (!this._markerGroup) {
				this._initMarkers();
			}
			this._poly._map.addLayer(this._markerGroup);
		}
	},

	removeHooks: function () {
		var poly = this._poly;

		poly.setStyle(poly.options.original);

		if (poly._map) {
			poly._map.removeLayer(this._markerGroup);
			delete this._markerGroup;
			delete this._markers;
		}
	},

	updateMarkers: function () {
		this._markerGroup.clearLayers();
		this._initMarkers();
	},

	_initMarkers: function () {
		if (!this._markerGroup) {
			this._markerGroup = new L.LayerGroup();
		}
		this._markers = [];

		var latlngs = this._poly._latlngs,
			i, j, len, marker;

		// TODO refactor holes implementation in Polygon to support it here

		for (i = 0, len = latlngs.length; i < len; i++) {

			marker = this._createMarker(latlngs[i], i);
			marker.on('click', this._onMarkerClick, this);
			this._markers.push(marker);
		}

		var markerLeft, markerRight;

		for (i = 0, j = len - 1; i < len; j = i++) {
			if (i === 0 && !(L.Polygon && (this._poly instanceof L.Polygon))) {
				continue;
			}

			markerLeft = this._markers[j];
			markerRight = this._markers[i];

			this._createMiddleMarker(markerLeft, markerRight);
			this._updatePrevNext(markerLeft, markerRight);
		}
	},
	
	

	_createMarker: function (latlng, index) {
		var marker = new L.Marker(latlng, {
			draggable: true,
			icon: this.options.icon
		});

		marker._origLatLng = latlng;
		marker._index = index;

		marker.on('dragstart', this._onMarkerDragStart, this);
		marker.on('drag', this._onMarkerDrag, this);
		marker.on('dragend', this._fireEdit, this);

		this._markerGroup.addLayer(marker);

		return marker;
	},

	_removeMarker: function (marker) {
		var i = marker._index;

		this._markerGroup.removeLayer(marker);
		this._markers.splice(i, 1);
		this._poly.spliceLatLngs(i, 1);
		this._updateIndexes(i, -1);

		marker
			.off('drag', this._onMarkerDrag, this)
			.off('dragend', this._fireEdit, this)
			.off('click', this._onMarkerClick, this);
	},

	_fireEdit: function () {
		this._poly.edited = true;
		this._poly.fire('edit');
	},
	
	//rajout de l'evenement onMarkerStart. Cette fonction est utilisée pour déplacer plusieurs points de plusieurs polyline (Layers) en même temps.
	_onMarkerDragStart: function (e) {
	
		var base_lat_first = e.target._latlng.lat;
		var base_lng_first = e.target._latlng.lng;
		
		
		//on crée un objet qui va contenir tout les marqueurs et tout les points qui sont à modifier
		liste_poly_detect = {};
		liste_poly_detect_point = {};
		
		//on detecte tout les polylines concernée par le déplacement (par les points que l'on a trouvé dans le createMarker de la classe segment)
		
		var full_poly_tab = $.extend(true, {}, drawnItems);
		
		//comparaison avec les points du tableau full_poly_tab
		
		$.each(full_poly_tab._layers, function(key, val) {
			
			$.each(val._latlngs, function(pos, points) 
			{									
				//test de concordances entre les points 
				if(points.lat == base_lat_first && points.lng == base_lng_first)
				{	
					liste_poly_detect[key] = {};
					
					liste_poly_detect[key]['key'] = key;
					liste_poly_detect[key]['pos'] = pos;
					
					update_list_segment[key] = key;
				}
			});
		});
		
		//on doit rechercher les points qui ont une collisions avec le point en paramètre (e)
		//meme chose avec les 
		/*
		liste_marqueur_detect = {};
		
		var markerGroup2 = $.extend(true, {}, markerGroup);
		
		$.each(markerGroup2._layers, function(key, val) { 
			if(val._latlng.lat == base_lat_first && val._latlng.lng == base_lng_first)
			{
				if (!liste_poly_detect.key) {

					liste_marqueur_detect[key] = {};
				
					liste_marqueur_detect[key][0] = val._latlng.lat;
					liste_marqueur_detect[key][1] = val._latlng.lng;
				}
			}
		});*/
	},

	//fonction de déplacement d'un marqueur 
	_onMarkerDrag: function (e) {
		var marker = e.target;
		
		//on récupère les coordonnées du point que l'on a sélectionné
		var base_lat_maj = e.target._latlng.lat;
		var base_lng_maj = e.target._latlng.lng;
		
		//mis à jour des points contenus dans l'object liste_poly_detect
		var point_temp;
		var point_temp_orig;
		/*
		$.each(liste_marqueur_detect, function(key, val) { 		
				//on récupère l'id du point en lecture et on le lit
					
				point_temp = markerGroup._layers[key]._latlng;					
				point_temp_orig = point_temp;

				point_temp_orig.lat = marker._origLatLng.lat;
				point_temp_orig.lng = marker._origLatLng.lng;
					
				point_temp.lat = liste_poly_detect[key][0];
				point_temp.lng = liste_poly_detect[key][1];		
		});
		*/
		var latlngs_modif;
		var poly_id = this._poly._leaflet_id;
		
		$.each(liste_poly_detect, function(key, val) { 			
			
			//on récupère la polyline
			//si la polyline n'est pas la polyline courante 
			if(val != poly_id)
			{
				//on récupère les latlngs de la poyline
				latlngs_modif = $.extend(true, {}, drawnItems._layers[val['key']]._latlngs);				
				//ensuite pour chaque points
				$.each(latlngs_modif, function(pos, val) { 
					//si le point se toruve dans le tableau liste_poly_detect généré dans l'ajout d'un point
					if(pos == liste_poly_detect[key]['pos'])
					{
						//on set les valeurs du points que l'on déplace a partir de la liste liste_poly_detect_point
						val.lat = base_lat_maj;
						val.lng = base_lng_maj;
					}
				});
				//on redessine la polyline courante
				drawnItems._layers[val['key']].redraw();
			}
			
		});
		
		//fin de la modification
		
		L.extend(marker._origLatLng, marker._latlng);
		if (marker._middleLeft) {
			marker._middleLeft.setLatLng(this._getMiddleLatLng(marker._prev, marker));
		}
		if (marker._middleRight) {
			marker._middleRight.setLatLng(this._getMiddleLatLng(marker, marker._next));
		}

		this._poly.redraw();
	},

	_onMarkerClick: function (e) {
		var minPoints = L.Polygon && (this._poly instanceof L.Polygon) ? 4 : 3,
			marker = e.target;

		// If removing this point would create an invalid polyline/polygon don't remove
		if (this._poly._latlngs.length < minPoints) {
			return;
		}

		// remove the marker
		this._removeMarker(marker);

		// update prev/next links of adjacent markers
		this._updatePrevNext(marker._prev, marker._next);

		// remove ghost markers near the removed marker
		if (marker._middleLeft) {
			this._markerGroup.removeLayer(marker._middleLeft);
		}
		if (marker._middleRight) {
			this._markerGroup.removeLayer(marker._middleRight);
		}

		// create a ghost marker in place of the removed one
		if (marker._prev && marker._next) {
			this._createMiddleMarker(marker._prev, marker._next);

		} else if (!marker._prev) {
			marker._next._middleLeft = null;

		} else if (!marker._next) {
			marker._prev._middleRight = null;
		}

		this._fireEdit();
	},

	_updateIndexes: function (index, delta) {
		this._markerGroup.eachLayer(function (marker) {
			if (marker._index > index) {
				marker._index += delta;
			}
		});
	},

	_createMiddleMarker: function (marker1, marker2) {
		var latlng = this._getMiddleLatLng(marker1, marker2),
		    marker = this._createMarker(latlng),
		    onClick,
		    onDragStart,
		    onDragEnd;

		marker.setOpacity(0.6);

		marker1._middleRight = marker2._middleLeft = marker;

		onDragStart = function () {
		
			var i = marker2._index;

			marker._index = i;

			marker
			    .off('click', onClick, this)
			    .on('click', this._onMarkerClick, this);

			latlng.lat = marker.getLatLng().lat;
			latlng.lng = marker.getLatLng().lng;
			
			this._poly.spliceLatLngs(i, 0, latlng);
			this._markers.splice(i, 0, marker);

			marker.setOpacity(1);

			this._updateIndexes(i, 1);
			marker2._index++;
			this._updatePrevNext(marker1, marker);
			this._updatePrevNext(marker, marker2);

			this._poly.fire('editstart');
		};

		onDragEnd = function () {
			marker.off('dragstart', onDragStart, this);
			marker.off('dragend', onDragEnd, this);

			this._createMiddleMarker(marker1, marker);
			this._createMiddleMarker(marker, marker2);
		};

		onClick = function () {
			onDragStart.call(this);
			onDragEnd.call(this);
			this._fireEdit();
		};

		marker
		    .on('click', onClick, this)
		    .on('dragstart', onDragStart, this)
		    .on('dragend', onDragEnd, this);

		this._markerGroup.addLayer(marker);
	},

	_updatePrevNext: function (marker1, marker2) {
		if (marker1) {
			marker1._next = marker2;
		}
		if (marker2) {
			marker2._prev = marker1;
		}
	},

	_getMiddleLatLng: function (marker1, marker2) {
		var map = this._poly._map,
		    p1 = map.project(marker1.getLatLng()),
		    p2 = map.project(marker2.getLatLng());

		return map.unproject(p1._add(p2)._divideBy(2));
	}
});

L.Polyline.addInitHook(function () {

	// Check to see if handler has already been initialized. This is to support versions of Leaflet that still have L.Handler.PolyEdit
	if (this.editing) {
		return;
	}

	if (L.Edit.Poly) {
		this.editing = new L.Edit.Poly(this);

		if (this.options.editable) {
			this.editing.enable();
		}
	}

	this.on('add', function () {
		if (this.editing && this.editing.enabled()) {
			this.editing.addHooks();
		}
	});

	this.on('remove', function () {
		if (this.editing && this.editing.enabled()) {
			this.editing.removeHooks();
		}
	});
});

//fin de la classe edit

	//inclusion de la barre des actions
    L.DrawToolbar.include({
        getModeHandlers: function (map) {
            return [
                {
                    enabled: true,
                    handler: new L.Draw.Segment(map, {
                            icon: new L.DivIcon({
                                iconSize: new L.Point(8, 8),
                                className: 'leaflet-div-icon leaflet-editing-icon'
                            })
                        }
                    ),
                    title: 'Tracer un segment'
                }
            ];
        }
    });
	//ajout du bouton " Tracer un parcours "
    drawControl = new L.Control.Draw({
        draw: {
            polyline: {
                shapeOptions: {
                    color: 'blue'
                }
            },
            segment: true,
            polygon: false,
            rectangle: false,
            marker: false,
            circle: false
        },
        edit: {
            featureGroup: drawnItems
        }
    });
    L.drawLocal.draw.toolbar.buttons.polyline = 'Tracer un parcours';
    map.addControl(drawControl);
	
	//ajout des boutons pour tracer un itinéraire
    routeButton = L.easyButton({
        states:[
            {
                stateName: 'create-route',
                icon: 'fa-pencil',
                onClick: function(control){
                    control.state('save-route');
                    createRoute();
                    routeBar._buttons[2].enable();
                },
                title : "Tracer un itinéraire"
            },
            {
                stateName: 'save-route',
                icon: 'fa-floppy-o',
                onClick: function(control){
                    control.state('create-route');
                    saveRoute();
                },
                title : "Sauvegarder un itinéraire"
            }
        ]
    });

    routeDeleteButton = L.easyButton({
        states:[
            {
                stateName: 'delete-route',
                icon: 'fa-eraser',
                onClick: function(control){
                    deleteLastSegment();
                },
                title : "Retirer le dernier segment de l'itinéraire"
            }
        ]
    }).disable();

    routeCancelButton = L.easyButton({
        states:[
            {
                stateName: 'cancel-route',
                icon: 'fa-undo',
                onClick: function(control){
                    jQuery.each(polyArray,function(k,v){
                        unglow(v);
                        map.removeLayer(v);
                    });
                    jQuery.each(potentialPoly,function(k,v){
                        unglow(v);
                        map.removeLayer(v);
                    });
                    routeBar._buttons[0]._activateStateNamed("create-route");
                    routeBar._buttons[1].disable();
                    routeBar._buttons[2].disable();
                    polyArray = [];
                    potentialPoly = [];
                    isCreateRoute = false;
                    drawnItems.eachLayer(function (layer){
                        layer.addTo(map);
                    });
                    pogGroup.eachLayer(function(layer){
                        layer.off("click");
                        map.removeLayer(layer);
                        layer.addTo(map);
                    });
                },
                title : "Annuler le tracé d'un itinéraire"
            }
        ]
    }).disable();

    routeBar = L.easyBar([ routeButton, routeDeleteButton,routeCancelButton ]);
    routeBar.addTo(map);
	//ajout du bouton de suppression d'un segment d'un tronçon
	Segsuppr = L.easyButton({
            states : [
			{
				stateName: 'del_seg',
                icon : 'fa-times',
                onClick : function (control){
				control.state('editor_mode');
                    supprSegment_call();
                },
                title : "Supprimer un segment d'un tronçon"
            },
			{
                stateName: 'editor_mode',
                icon: 'fa fa-check',
                onClick: function(control){
                    control.state('del_seg');
					
					supprSegment_call();
                },
                title : "Revenir à l'édition"
            }
            ]}
     );

	//ajout dans la barre et dans la map
    pogBar = L.easyBar([ Segsuppr ]);
    pogBar.addTo(map);

    L.control.scale().addTo(map);
	
	//création d'un itinéraire
    map.on('draw:created', function (e) {
        var type = e.layerType,
            layer = e.layer;
        drawnItems.addLayer(layer);
        polyline = layer;
        map.off("click");
        //console.log("poly coords : " + polyline._latlngs.length);
        for (var i = 0; i < polyline._latlngs.length; i++) {
            pointArray[i] = new Point(polyline._latlngs[i].lat, polyline._latlngs[i].lng);
        }
        //console.log("pointArray length : " +  pointArray.length);
		//récupération de l'élévation 
        var URL = elevationURL + '&latLngCollection=';
        for (var i = 0; i < pointArray.length; i++) {
            var lat = pointArray[i].lat;
            var lng = pointArray[i].lng;
            URL += lat + "," + lng;
            if (i !== pointArray.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);
        saveRoute();
        $("#denivp").text("");
        $("#denivn").text("");
    });

	//evenement de création d'un segment
    map.on('draw:segmentcreated', function (e) {
		//lors de l'ajout d'une polyline, on la sépare en plusieurs morceau.
		//boucle pour parcourir toutes les polylines détectées.
		$.each(points_tab_index, function(key, val) { 
			if(key != "poly_courante")
			{
				//si l'objet n'est pas vide
				if(count_object_property(val) != 0)
				{
					//fonction qui sert à séparé les tronçons en plusieurs sous tronçons. S'occupe également de l'envois en BDD
					//SegmentSlice(key, val);
				}
			}
		});
        var type = e.layerType,
            layer = e.layer;
        drawnItems.addLayer(layer);
        polyline = layer;
        map.off("click");
        //on génére les points de la polyline
        for (var i = 0; i < polyline._latlngs.length; i++) {
            pointArray[i] = new Point(polyline._latlngs[i].lat, polyline._latlngs[i].lng);
        }
        //récupération de l'élévation
        var URL = elevationSegmentURL + '&latLngCollection=';
        for (var i = 0; i < pointArray.length; i++) {
            var lat = pointArray[i].lat;
            var lng = pointArray[i].lng;
            URL += lat + "," + lng;
            if (i !== pointArray.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);
		
		//séparation des segments lors de la sauvegarde
		
		//si la  variable globale compteur est initialisée, on n'appelle pas la fonction saveSegment, mais la fonction multiple save segment
		//autoriser le passage seulement si les points ne sont pas des POGS (le zéro n'est pas ajouté dans le tableau, mais le dernier point oui)
		/*if(count_object_property(table_pos_poly_courante) != 0)
		{
				//on appelle sépare les points en plusieurs tronçon et on les save. le tableau pointArray contient la polyline courante
				
				//on récupère une polyline et on la découpe en fonction du tab_pos donné en paramètre.
				var slice_result = {};
				var slice_count = 0; 
				
				//séparation du tableau en plusieurs sous sections
				$.each(table_pos_poly_courante, function(key, val) {				
					if(val != 0 && val != pointArray.length)
					{
						slice_result[val] = pointArray.slice(slice_count,val+1);
						slice_count = val;
					}
				});
				
				slice_result[slice_count+pointArray.length] = pointArray.slice(slice_count,pointArray.length);
				
				//pas de suppresion en BDD puisque le segment n'existe pas encore.		
				
				//création des nouvelles polylines
				//slice_result contient le nuage de points, on le set dans les polylines
				
				//on ne le rédessine pas puisque la BDD recharge la page.
				
				$.each(slice_result, function(key, val) 
				{
					polyline = L.polyline(val, {color: 'blue'});	
					//ajout dans le drawnItems.
					//drawnItems.addLayer(polyline);
					//ajout a la map
					polyline.addTo(map);		
				}); 
				
				//on envois les modifications en base de données : 
				saveMultiplePolyServer(slice_result);
				
				//remise à zéro du tableau et du compteur
				table_pos_poly_courante = {};
				create_poly_segmentation_count = 0;
		}
		else
		{
			//si il n'y a pas de coupure, on sauvegarde simplement la polyline
			 setTimeout(saveSegment, 5000);
		}*/
        setTimeout(saveSegment, 5000);
        $("#denivp").text("");
        $("#denivn").text("");
		create_poly_segmentation_count = 0;
    });

    map.on('draw:drawstart', function (e) {
        drawRoute(e);
    });

    map.on('draw:drawstop', function (e) {
        map.off("click");
    });

    map.on('draw:segmentstart', function (e) {
        drawSegment(e);
    });
	
    map.on('draw:segmentstop', function (e) {
        map.off("click");
        isCreateSegment = false;
    });
	
    map.on('draw:editstart', function (e) {
        isEditSegment = true;
    });
	
	//fonction qui permet de sauvegarder en base de données les modifications apportées à plusieurs polylines
	map.on('draw:edited', function (e) {
        isEditSegment = false;
		if(!map_load)
		{
			//le tableau liste_points_final contient toutes les polylines qui vont être modifiées en BDD
			var liste_points_final = {};
		
			//on lit les polylines du tableau liste_points_final
			$.each(update_list_segment, function(key, val) {
				var current_layer_update = drawnItems.getLayer(val);
				liste_points_final[key] = {};
				liste_points_final[key]['id'] = current_layer_update.id;
				
				liste_points_final[key]['points'] = {};
				
				//on lit ensuite tout les points et on les stocke dans un tableau.
				$.each(current_layer_update._latlngs, function(pos, point) {
					var point_current_update = new Point(point.lat,point.lng);
					
					liste_points_final[key]['points'][pos] = point_current_update;
				});	
			});			
			
			//Puis on le passe en paramètre et on le save grâce à une boucle foreach.
			updateMultipleSegment(liste_points_final);
		}
    });

    map.on("draw:editstop", function(e){
        isEditSegment = false;
    });
	
	//evenement de sauvegarde pour la suppression d'une ou de plusieurs polylines
	map.on('draw:deleted', function (e) {
		var tronId = {};
		//pour récupérer l'id du layer : e.layers._layers
		$.each(e.layers._layers, function(key, val) {
			tronId[key] = val.id;
		});	
		
		//suppression en base
		DeleteTrons(tronId);
    });
	
	//fonction qui gère le zoom.
	//contient la mise à jour du radius de détection des points.
    map.on("zoomend", function (e) {
        if (map.getZoom() < 10 && formerZoom >= 10) {
            markerGroup.eachLayer(function (layer) {
                map.removeLayer(layer);
            });
            drawnItems.eachLayer(function (layer) {
                map.removeLayer(layer);
            });

        }
        else if (map.getZoom() >= 10 && formerZoom < 10) {
            markerGroup.eachLayer(function (layer) {
                layer.addTo(map);
            });
            drawnItems.eachLayer(function (layer) {
                layer.addTo(map);
            });
        }		
        formerZoom = map.getZoom();
		
	radiusGroup.eachLayer(function (layer){
		map.removeLayer(layer);
	});

	//création de tout les cercles des polylines 
		
	var full_poly_tab = drawnItems;
	
	//calcul du radius en fonction du zoom
	
	radius = 20*(18-map.getZoom()+1);
	
	radius = radius + (50 * (18-map.getZoom()));
	
	if(18-map.getZoom() == 1)
	{
		radius = radius - 20;
	}	
	
	///////////FIN DU CALCUL
    })

    map.on('dragend', function () {
        if (map.getZoom() == formerZoom ) {
            if(!isCreateRoute && !isCreateSegment && !isEditSegment)
            {
					drawnItems.eachLayer(function (layer) {
						map.removeLayer(layer);
					});

					pogGroup.eachLayer(function (layer) {
						map.removeLayer(layer);
					 });
				if(!is_reloading)
				{	 
					loadSegments();
					is_reloading = true;
				}
            }


        }
    });
    $("#map").css("cursor", "move");
    loadPois();
	if(!is_reloading)
	{
		loadSegments();
		is_reloading = true;
	}
    if (isLoadingMap) {
        segmentID = traceData.segment.id;
        displayTrace(traceData.segment, traceData.elevation);
        $("#denivp").text("Dénivelé positif : " + traceData.deniveleplus + "m");
        $("#denivn").text("Dénivelé négatif : " + traceData.denivelemoins + "m");
        $("#long").text("Longueur : " + traceData.longueur + "km");
        $("#diffiDisplay").text("Difficulté : " + traceData.difficulte.label);
        isLoadingMap = false;
        map.dragging.disable();
    }

}

function addOverlay() {
    el = L.control.elevation({
        position: "topright",
        theme: "lime-theme", //default: lime-theme
        width: 600,
        height: 175,
        margins: {
            top: 10,
            right: 20,
            bottom: 30,
            left: 50
        },
        useHeightIndicator: true, //if false a marker is drawn at map position
        interpolation: "linear", //see https://github.com/mbostock/d3/wiki/SVG-Shapes#wiki-area_interpolate
        hoverNumber: {
            decimalsX: 3, //decimals on distance (always in km)
            decimalsY: 0, //deciamls on height (always in m)
            formatter: undefined //custom formatter function may be injected
        },
        xTicks: undefined, //number of ticks in x axis, calculated by default according to width
        yTicks: undefined, //number of ticks on y axis, calculated by default according to height
        collapsed: true    //collapsed mode, show chart on click or mouseover
    });
    el.addTo(map);


    var elevationUI = L.Control.extend({
        options: {
            position: 'topright'
        },

        onAdd: function (map) {
            // create the control container with a particular class name
            var container = L.DomUtil.create('div', 'leaflet-control-command');
            $(container).html("<p id='denivp' class='controlText'></p><p id='denivn' class='controlText'></p>" +
            "<p id='long' class='controlText'></p><p id='diffiDisplay' class='controlText'></p>");
            return container;
        }
    });

    map.addControl(new elevationUI());
    //map.addControl(new drawSegmentUI());
}

//Déplace la map aux coordonnées indiquées
function moveToCoords(lat, lng, zoom) {
    map.setView([$("#lat").val(), $("#lng").val()], $("#zoom").val());
}

//Affiche les coordonnées
function displayCoords(event) {
    $("#lat").val(event.latlng.lat);
    $("#lng").val(event.latlng.lng);
}

//???
function parseGPX(path) {
    GPX = new L.GPX(path, {
        async: true,
        marker_options: {
            startIconUrl: 'Trail/web/pin-icon-start.png',
            endIconUrl: 'Trail/web/pin-icon-end.png',
            shadowUrl: 'Trail/web/pin-shadow.png'
        }
    }).on('loaded', function (e) {
            map.fitBounds(e.target.getBounds());
        }).addTo(map);
}

//fonction utilitaire de geocoding
function geocode() {
    var geo = MQ.geocode({map: map})
        .search($("#ville").val());
}

//fonction d'initilisation pour créer un itinéraire (appellée de la barre des boutons)
function createRoute() {
    if (!isCreateRoute) {
        isCreateRoute = true;
        pointArray = [];
        latlngArray = [];
        polyArray = [];
        potentialPoly = [];
        beginRoute();

    }
}


function drawRoute(event) {
    if (!isCreateRoute) {
        isCreateRoute = true;
        pointArray = [];
        latlngArray = [];
        map.on("click", function (ev) {
            addPointOnMap(ev);
        });
    }
}

//sauvegarder l'itinéraire que l'on vient de tracer (après appuis sur le bouton sauvegarder)
function saveRoute() {
    loadDifficultes();
    loadStatus();
    loadTypechemin();
    /*jQuery.each(polyArray,function(i,v){
        jQuery.each(v._latlngs,function(index,value)
        {
            var point = new Point(value.lat,value.lng);
            point.elevation = value.elevation;
            point.distance = value.distance;
            pointArray.push(point);
        });
    });*/
    for(var i = 0; i < polyArray.length; i++)
    {
        var latlngs = polyArray[i]._latlngs;
        var latlngsplus = null;
        if(i < polyArray.length - 1)
        {
            latlngsplus = polyArray[i + 1]._latlngs;
        }
        if(latlngsplus !== null && !latlngEquality(latlngs[latlngs.length - 1], latlngsplus[0]))
        {
            polyArray[i + 1]._latlngs = polyArray[i + 1]._latlngs.reverse();
        }

        for(var j = 0; j < latlngs.length; j++)
        {
            var point = new Point(latlngs[j].lat,latlngs[j].lng);
            point.elevation = latlngs[j].elevation;
            point.distance = latlngs[j].distance;
            pointArray.push(point);
        }
    }
    if(potentialPoly !== undefined && potentialPoly.length > 0)
    {
        jQuery.each(potentialPoly,function(i,v){
            unglow(v);
        });
    }

    $("#save").modal('show');
    $("#saveiti").on("click", function () {
        $.post(Routing.generate('site_carto_saveItineraire'),
            {
                points: JSON.stringify(pointArray),
                longueur: pointArray[pointArray.length - 1].distance,
                denivelep: denivelep,
                denivelen: denivelen,
                nom: $("#nom").val(),
                numero: $("#numero").val(),
                typechemin: $("#typechemin option:selected").val(),
                description: $("#description").val(),
                difficulte: $("#difficulte option:selected").val(),
                auteur: $("#auteur").val(),
                status: $("#status option:selected").val(),
                public: $("#public option:selected").val()
            },
            function (data, status) {

                $.notify("Itinéraire sauvegardé", "success");
            }
        ).fail(function () {
                $.notify("Erreur lors de la sauvegarde", "error");
            });
        jQuery.each(polyArray,function(i,v){
            unglow(v);
            //map.removeLayer(v);
        });
        polyArray = [];
        $("#save").modal('hide');

    });
    $(".closeiti").on("click", function () {
        jQuery.each(polyArray,function(i,v){
            unglow(v);
            map.removeLayer(v);
        });
        polyArray = [];
    });
    $("#denivp").text("Dénivelé positif : 0m");
    $("#denivn").text("Dénivelé négatif : 0m");
    $("#long").text("Longueur : 0km");
    el.clear();

    if(isCreateRoute)
    {
        drawnItems.eachLayer(function (layer) {
           layer.addTo(map);
        });
    }
    isCreateRoute = false;

}

//fonction de traçage d'un segment (attribution d'un écouteur pour la variable map)
function drawSegment(event) {
    if (!isCreateSegment) {
        isCreateSegment = true;
        pointArray = [];
        latlngArray = [];
        map.on("click", function (ev) {
            addPointOnMap(ev);
        });
    }
}

//sauvegarde du segment tracé
function saveSegment() {  
    $.post(Routing.generate('site_carto_saveSegment'),
        {
            points: JSON.stringify(pointArray)
        },
        function (data, status) {
            $.notify("Segment sauvegardé", "success");
            polyline.id = data.id;
        }
    ).fail(function () {
            $.notify("Erreur lors de la sauvegarde", "error");
        });
    isCreateSegment = false;


}

//fonction de sauvegarde d'un POI
function savePoi()
{
    roleMap = loadRoleMap();
    $.post(Routing.generate('site_carto_savePoi'),
    {
        lat: latPoi,
        lng : lngPoi,
        alt : altPoi,
        idLieu : $("#typelieu option:selected").val(),
        titre : $("#titre").val(),
        description : $("#descriptionPoi").val()
    },
    function(data, status){
	
        var iconePoi = L.icon({iconUrl : data.path,iconSize : [30, 30]});

        if(roleMap == 1 || roleMap == 3)
        {
            var marker = L.marker([latPoi,lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<p> <b>" + $("#titre").val() + "</b></p><p>" + $("#descriptionPoi").val() + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + data.idPoi + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + data.idPoi + ")'>Modifier le POI</button>");
        }
        else
        {
            var marker = L.marker([latPoi,lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<p> <b>" + $("#titre").val() + "</b></p><p>" + $("#descriptionPoi").val() + "</p>");
        }
        markerGroup.addLayer(marker);
        marker.on("click", function (event) { markerSelectionne = event.target; });
    });
    
    $("#addpoi").modal('hide');
}

//fonction de modification d'un POI
function modifPoi()
{
    var dataPoi = $('#formModifPoi').serialize();
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_editPoi'),
        data: dataPoi,
        dataType: "json",
        cache: false,
        success: function(data){
            $("#modalEditPoi").modal('hide');
            markerSelectionne.closePopup();
            map.removeLayer(markerSelectionne);
            var iconePoi = L.icon({iconUrl : data.path,iconSize : [30, 30]});

            if(data.pathImagePoi != null)
            {
                var marker = L.marker([data.latPoi,data.lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<div id='imgPoi' class='img-size' style='background-image: url(" + data.pathImagePoi + ");'></div> <p> <b>" + data.titrePoi + "</b></p><p>" + data.descriptionPoi + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + data.idPoi + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + data.idPoi + ")'>Modifier le POI</button>");
            }
            else
            {
                var marker = L.marker([data.latPoi,data.lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<p> <b>" + data.titrePoi + "</b></p><p>" + data.descriptionPoi + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + data.idPoi + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + data.idPoi + ")'>Modifier le POI</button>");
            }
            markerGroup.addLayer(marker);
            marker.on("click", function (event) { markerSelectionne = event.target; });
            }
     });
}

//Afficher le modal de modification d'un poi
function modifPoiForm(idPoi)
{
    $('#modalEditPoi').children().remove();
    $('#modalEditPoi').remove();

    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_afficheEditPoi'),
        cache: false,
        data: {"idPoi" : idPoi},
        success: function(data){
            $('body').append(data);
            $("#modalEditPoi").modal('show');
        }
    });
}

//Afficher le modal de confirmation de suppression d'un poi
function supprPoiConfirm(idPoi)
{
    $('#modalWarningDeletePoi').children().remove();
    $('#modalWarningDeletePoi').remove();

    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_afficheDeletePoi'),
        cache: false,
        data: {"idPoi" : idPoi},
        success: function(data){
            $('body').append(data);
            $("#modalWarningDeletePoi").modal('show');
        }
    });
}

//Suppression d'un poi
function suppressionPoi(idPoi)
{
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_deletePoi'),
        data: {"idPoi" : idPoi},
        cache: false,
        success: function(){
            markerSelectionne.closePopup();
            map.removeLayer(markerSelectionne); 
        }
    });
}

//fonction de récupération de l'élévation de l'itinéraire que l'on trace
function getElevation(response)
{
    blockItineraireSave();
    denivelen = 0;
    denivelep = 0;
    var poly = [];
    for(var i = 0; i < polyArray[polyArray.length - 1]._latlngs.length; i++)
    {
        polyArray[polyArray.length - 1]._latlngs[i].elevation = response.elevationProfile[i].height;
        polyArray[polyArray.length - 1]._latlngs[i].distance = response.elevationProfile[i].distance;
    }
    for(var i = 0; i < polyArray.length; i++)
    {
        for(var j = 0; j < polyArray[i]._latlngs.length - 1; j++)
        {
            var diff = polyArray[i]._latlngs[j].elevation - polyArray[i]._latlngs[j + 1].elevation;
            diff < 0 ? denivelep += diff * -1 : denivelen += diff * -1;
        }
        poly.push.apply(poly,polyArray[i]._latlngs);
    }
    $("#long").val(polyArray[polyArray.length - 1]._latlngs[polyArray[polyArray.length - 1]._latlngs.length - 1].distance + "km");
    $("#denivp").text("Dénivelé positif : " + denivelep + "m");
    $("#denivn").text("Dénivelé négatif : " + denivelen + "m");
    var polyline = polyline = L.polyline(poly, {color: 'blue'});
    var geojson = polyline.toGeoJSON();
    for(var i = 0; i < geojson.geometry.coordinates.length; i++)
    {
        geojson.geometry.coordinates[i].push(poly[i].elevation);
    }
    if(mapgeojson !== undefined)
    {
        map.removeLayer(mapgeojson);
    }
    el.clear();
    mapgeojson = L.geoJson(geojson,{
        onEachFeature: el.addData.bind(el) //working on a better solution
    });
    if(isEditSegment)
    {
        updateSegment(JSON.stringify(pointArray));

    }
    blockItineraireSave();
}

//fonction de récupération de l'élévation du segment que l'on trace
function getElevationSegment(response)
{
    blockItineraireSave();
    denivelen = 0;
    denivelep = 0;
    for(var i = 0; i < pointArray.length; i++)
    {
        pointArray[i].elevation = response.elevationProfile[i].height;
        pointArray[i].distance = response.elevationProfile[i].distance;
    }
    for(var i = 0; i < pointArray.length - 1; i++)
    {
        var diff = pointArray[i].elevation - pointArray[i + 1].elevation;
        diff < 0 ? denivelep += diff * -1 : denivelen += diff * -1;
    }
    $("#long").val(pointArray[pointArray.length - 1].distance + "km");
    $("#denivp").text("Dénivelé positif : " + denivelep + "m");
    $("#denivn").text("Dénivelé négatif : " + denivelen + "m");
    var geojson = polyline.toGeoJSON();
    for(var i = 0; i < geojson.geometry.coordinates.length; i++)
    {
        geojson.geometry.coordinates[i].push(pointArray[i].elevation);
    }
    if(mapgeojson !== undefined)
    {
        map.removeLayer(mapgeojson);
    }
    el.clear();
    mapgeojson = L.geoJson(geojson,{
        onEachFeature: el.addData.bind(el) //working on a better solution
    });
    if(isEditSegment)
    {
        updateSegment(JSON.stringify(pointArray));

    }
    blockItineraireSave();
    map.on("click",function (ev){
        addPointOnMap(ev);
    });
}

//fonction de chargement des difficultées (utilisé dans la modal de sauvegarde d'un itinéraire)
function loadDifficultes() {
    $.ajax({
        url: Routing.generate('site_carto_getDifficulteParcours'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].niveau).text(json[i].label);
                opt.appendTo("#difficulte");
            }
        },

        error: function (resultat, statut, erreur) {

        },

        complete: function (resultat, statut) {

        }

    });
}

//fonction de chargement des status (utilisé dans la modal de sauvegarde d'un itinéraire)
function loadStatus() {
    $.ajax({
        url: Routing.generate('site_carto_getStatus'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].id).text(json[i].label);
                opt.appendTo("#status");
            }
        },

        error: function (resultat, statut, erreur) {

        },

        complete: function (resultat, statut) {

        }

    });
}


//fonction de chargement des types de chemins (utilisé dans la modal de sauvegarde d'un itinéraire)
function loadTypechemin() {
    $.ajax({
        url: Routing.generate('site_carto_getTypechemin'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].id).text(json[i].label);
                opt.appendTo("#typechemin");
            }
        },

        error: function (resultat, statut, erreur) {

        },

        complete: function (resultat, statut) {

        }

    });
}

//fonction de conversion d'un tableau JSON en fichier CSV
function csvJSON(csv) {

    var lines = csv.split("\n");

    var result = [];

    var headers = lines[0].replace(/(?:\\[r])+/g, "").split(",");

    for (var i = 1; i < lines.length; i++) {
        if (lines[i] !== "") {
            var obj = {};
            var currentline = lines[i].replace(/(?:\\[r])+/g, "").split(",");

            for (var j = 0; j < headers.length; j++) {
                obj[headers[j]] = currentline[j];
            }

            result.push(obj);
        }

    }
    //return result; //JavaScript object
    //return JSON.stringify(result); //JSON
    return result;
}

//fonction d'affichage des traces CSV
function displayTrace(trace, elevation) {
    //On convertit les coordonnées JSON en LatLng utilisables pour la polyline
    var LSCoords = trace.split(",");
    var latlngArr = [];
    for (var i = 0; i < LSCoords.length; i++) {
        var coords = LSCoords[i].split(" ");
        var res = new L.LatLng(coords[1], coords[0]);
        latlngArr.push(res);
    }

    //On crée la polyline et on ajoute les points

    polyline = L.polyline(latlngArr, {color: 'blue'});
    drawnItems.addLayer(polyline);
    
    //surbrillance(polyline);
    polyline.markers = [];
    for (var i = 0; i < latlngArr.length; i++) {
        var marker = new L.Marker([latlngArr[i].lat, latlngArr[i].lng], {
            icon: new L.DivIcon({
                iconSize: new L.Point(8, 8),
                className: 'leaflet-div-icon leaflet-editing-icon'
            })
        });
        //surbrillance(marker);
        marker.addTo(map);
        markerGroup.addLayer(marker);
        polyline.markers.push(marker);
    }

    //On ajoute le profil altimétrique
    var geojson = polyline.toGeoJSON();
    var elevations = elevation.split(";");
    for (var i = 0; i < geojson.geometry.coordinates.length; i++) {
        geojson.geometry.coordinates[i].push(elevations[i]);
    }
    mapgeojson = L.geoJson(geojson, {
        onEachFeature: el.addData.bind(el)
    });

    polyline.addTo(map);
    console.log(polyline);


    //Events de la polyline, on retire les points selon les différents cas
    map.on('draw:editstart', function (e) {
        for (var i = 0; i < polyline.markers.length; i++) {
            map.removeLayer(polyline.markers[i]);
        }
    });
    map.on("draw:editstop", function () {


        for (var i = 0; i < polyline.markers.length; i++) {
            polyline.markers[i].addTo(map);
        }
    })
    map.on('draw:edited', function (e) {
	
		if(map_load)
		{
			console.log(traceData);
			console.log(e);
		
			polyline.markers = [];
			pointArray = [];
			var latlngs = polyline.getLatLngs();
			
			console.log(polyline);
			
			/*var URL = elevationUpdateURL + '&latLngCollection=';
			for (var i = 0; i < latlngs.length; i++) {
				var marker = new L.Marker([latlngArr[i].lat, latlngArr[i].lng], {
					icon: new L.DivIcon({
						iconSize: new L.Point(8, 8),
						className: 'leaflet-div-icon leaflet-editing-icon'
					})
				});
				marker.addTo(map);
				polyline.markers.push(marker);
				markerGroup.addLayer(marker);
				pointArray.push(new Point(latlngs[i].lat, latlngs[i].lng));
				URL += latlngs[i].lat + "," + latlngs[i].lng;
				if (i !== latlngs.length - 1) {
					URL += ",";
				}
			}
			URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
			elevationScript = document.createElement('script');
			elevationScript.type = 'text/javascript';
			elevationScript.src = URL;
			$("body").append(elevationScript);*/
			
			//puis on sauvegarde la modification de l'itinéraire
            updateiti(e.target);
		}

    });
    map.on('draw:deletestart', function (e) {
        polyline.on("click", function () {
            for (var i = 0; i < polyline.markers.length; i++) {
                map.removeLayer(polyline.markers[i]);
            }
        })
    });

    map.fitBounds(polyline.getBounds());//On centre la map sur la polyline
}

//fonction d'affichage d'un segment à partir d'une trace CSV
function displaySegment(trace,id,elevation) {
    //On convertit les coordonnées JSON en LatLng utilisables pour la polyline
    var LSCoords = trace.split(",");
    var eleArray = elevation.split(";");
    var latlngArr = [];
    for (var i = 0; i < LSCoords.length; i++) {
        var coords = LSCoords[i].split(" ");
        var res = new L.LatLng(coords[1], coords[0]);
        res.elevation = parseInt(eleArray[i]);
        latlngArr.push(res);
    }

    //On crée la polyline et on ajoute les points
    polyline = L.polyline(latlngArr, {color: 'blue'});
    polyline.id = id;

    if(!segmentLoaded(polyline)) {
        drawnItems.addLayer(polyline);
    }

    var pog1 = new L.Marker([latlngArr[0].lat, latlngArr[0].lng], {
        icon: new L.DivIcon({
            iconSize: new L.Point(10, 10),
            className: 'leaflet-div-icon leaflet-editing-icon'
        })
    });

    var pog2 = new L.Marker([latlngArr[latlngArr.length - 1].lat, latlngArr[latlngArr.length - 1].lng], {
        icon: new L.DivIcon({
            iconSize: new L.Point(10, 10),
            className: 'leaflet-div-icon leaflet-editing-icon'
        })
    });

    pog1.segment = polyline;
    pog2.segment = polyline;
    if(!pogLoaded(pog1)) {
        pogGroup.addLayer(pog1);
        pog1.addTo(map);
    }
    if(!segmentLoaded(pog2)) {
        pogGroup.addLayer(pog2);
        pog2.addTo(map);
    }


    polyline.markers = [];
    polyline.pogs = [pog1,pog2];
    for (var i = 0; i < latlngArr.length; i++) {
        var marker = new L.Marker([latlngArr[i].lat, latlngArr[i].lng], {
            icon: new L.DivIcon({
                iconSize: new L.Point(8, 8),
                className: 'leaflet-div-icon leaflet-editing-icon'
            })
        });

        /*marker.overlaped = [];
        markerGroup.eachLayer(function(layer){
            if(latlngEquality(layer.getLatLng(),marker.getLatLng()))
            {
                marker.overlaped.push(layer);
                if(layer.overlaped === undefined)
                {
                    layer.overlaped = [];
                }
                layer.overlaped.push(marker);
            }
        });
        markerGroup.addLayer(marker);*/

        polyline.markers.push(marker);
    }
}

//fonction utilitaire pour attribuer un JSON dans une variable gloable
function loadMap(json) {
    isLoadingMap = true;
    is_reloading = true;
    traceData = json;
}

//fonction qui sert à gérer la surbrillance d'un objet.
function surbrillance(object) {
    object.on("mouseover", function () {
        object.setStyle({color: 'yellow'});
        object.redraw();
    });
    object.on("mouseout", function () {
        object.setStyle({color: 'blue'});
        object.redraw();
    });
}

//fonction pour mettre à jour un segment
function updateSegment(points) {
    $.post(Routing.generate('site_carto_segment_update'),
        {
            points: points,
            id: segmentID
        },
        function (data, status) {

            $.notify("Segment mis à jour", "success");
        }
    ).fail(function () {
            $.notify("Erreur lors de la mise à jour", "error");
        });
    isEditSegment = false;
}

//fonction pour mettre à jour plusieurs segments
function updateMultipleSegment(points) {
	//si l'on charge une page d'un itinéraire, on appelle une autre fonction 
	if(!map_load)
	{
		var points_string = JSON.stringify(points);
		console.log(points_string);
		$.post(Routing.generate('site_carto_segment_multiple_update'),
			{
				points: points_string
			},
			function (data, status) {
				console.log(data);
				$.notify("Segments mis à jour", "success");
			}
		).fail(function () {
				$.notify("Erreur lors de la mise à jour", "error");
			});
	}
	else
	{
		console.log(points);
	}	
}

//fonction pour faire apparaitre la modal de sauvegarde d'un itinéraire
function blockItineraireSave() {
    if (!$("#saveiti").prop("disabled")) {
        $("#saveiti").prop("disabled", true).change();
    }
    else {
        $("#saveiti").prop("disabled", false).change();
    }
}

//fonction d'ajout d'un point sur la map
function addPointOnMap(ev) {
	//création du point
    pointArray.push(new Point(ev.latlng.lat, ev.latlng.lng));
    latlngArray.push(ev.latlng);
    if (pointArray.length > 1) {
		//récupération de l'élévation
        polyline = L.polyline(latlngArray);
        var URL = elevationSegmentURL + '&latLngCollection=';
        for (var i = 0; i < latlngArray.length; i++) {
            var lat = latlngArray[i].lat;
            var lng = latlngArray[i].lng;
            URL += lat + "," + lng;
            if (i !== latlngArray.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);
        map.off("click");
    }
}

//fonction de chargement des segments
function loadSegments() {
    var bounds = map.getBounds();
    $.post(Routing.generate('site_carto_loadSegment'),
        {
            northeast: JSON.stringify(new Point(bounds._northEast.lat, bounds._northEast.lng)),
            southwest: JSON.stringify(new Point(bounds._southWest.lat, bounds._southWest.lng)),
            northwest: JSON.stringify(new Point(bounds._northEast.lat, bounds._southWest.lng)),
            southeast: JSON.stringify(new Point(bounds._southWest.lat, bounds._northEast.lng))
        },
        function (data, status) {
            var json = JSON.parse(data);
            jQuery.each(json.searchResults, function (k, v) {
                displaySegment(v.trace, v.id, v.elevation);
            });
            if(isCreateRoute)
            {
                drawnItems.eachLayer(function (layer) {
                    map.removeLayer(layer);
                });
            }
			
			//on reset la variable pour de nouveau autoriser les appels ajax
			is_reloading = false;
            //$.notify("Segment mis à jour", "success");
        }
    ).fail(function () {
            $.notify("Erreur lors du chargement du graphe des chemins", "error");
        });
}

//fonction de construction de l'itinéraire qu'on est en train de tracer.
function buildRoute(e)
 {
     var oldSize = polyArray.length;
     var selectedPoly = e.target;
     polyArray.push(selectedPoly);

     if(oldSize === 0)
     {

         routeBar._buttons[1].enable();
     }

	 //récupération de l'élévation
     var URL = elevationURL + '&latLngCollection=';
     for (var i = 0; i <selectedPoly._latlngs.length; i++) {
         var lat = selectedPoly._latlngs[i].lat;
         var lng = selectedPoly._latlngs[i].lng;
         URL += lat + "," + lng;
         if (i !== selectedPoly._latlngs.length - 1) {
             URL += ",";
         }
     }
     URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
     elevationScript = document.createElement('script');
     elevationScript.type = 'text/javascript';
     elevationScript.src = URL;
     $("body").append(elevationScript);

     bestChoices(selectedPoly);

 }

//Brillance de la polyline
function glow(object)
{
    object.setStyle({color: 'yellow',dashArray : "1"});
    object.redraw();
}

//Polyline normale
function unglow(object)
{
    object.setStyle({color: 'blue',dashArray : "1"});
    object.redraw();
}

//Mise en évidence d'une polyline (pour les segments du graphe)
function attention(object)
{
    object.setStyle({color: 'red', dashArray : "5, 5" });
    object.redraw();
}

//Suppression du dernier segment de l'itinéraire
function deleteLastSegment()
{
        if(polyArray.length === 1)
        {
            map.removeLayer(polyArray[polyArray.length - 1]);
            drawnItems.eachLayer(function (layer){
                layer.off("click");
            });
            pogGroup.eachLayer(function (layer){
                map.removeLayer(layer);
            });
            beginRoute();
        }
        else
        {
            unglow(polyArray[polyArray.length - 1]);
        }

        polyArray.pop();

        bestChoices(polyArray[polyArray.length - 1]);
        if(polyArray.length === 0)
        {
            /*routeDeleteButton.removeFrom(map);
            routeSaveButton.removeFrom(map);
            routeButton.addTo(map);*/
            routeBar._buttons[1].disable();
            pogGroup.eachLayer(function (layer){
                layer.off("click");
                map.removeLayer(layer);
                layer.addTo(map);
            });
            isCreateRoute = false;
        }
}

//Proposition des segments contigus
function bestChoices(selectedPoly)
{
    if(potentialPoly.length !== 0)
    {
        jQuery.each(potentialPoly,function(index,value){
            unglow(value);
            map.removeLayer(value);
        })
    }
    jQuery.each(polyArray,function(index,value){
        glow(value);
        map.addLayer(value);
    });
    pogGroup.eachLayer(function (layer){
        map.removeLayer(layer);
    });

    potentialPoly = [];
    if(selectedPoly !== undefined)
    {
        drawnItems.eachLayer(function(layer){
            if(layer !== selectedPoly)
            {
                var pog1SelectedPoly = selectedPoly._latlngs[0]; //POG1 selectedPoly
                var pog2SelectedPoly = selectedPoly._latlngs[selectedPoly._latlngs.length - 1]; //POG2 selectedPoly
                var pog1Layer = layer._latlngs[0]; //POG1 layer
                var pog2Layer = layer._latlngs[layer._latlngs.length - 1]; //POG2 layer

                if(latlngEquality(pog1Layer,pog2SelectedPoly) ||
                    latlngEquality(pog2Layer,pog2SelectedPoly) ||
                    latlngEquality(pog1Layer,pog1SelectedPoly) ||
                    latlngEquality(pog2Layer,pog1SelectedPoly)
                )
                {
                    if(polyArray.indexOf(layer) === -1)
                    {
                        layer.addTo(map);
                        attention(layer);
                        potentialPoly.push(layer);
                        layer.markers[0].addTo(map);
                        layer.markers[layer.markers.length - 1].addTo(map);
                        layer.on("click",function (e){
                            buildRoute(e);
                        })
                    }

                }
            }

        });
    }

}

//teste l'égalité entre deux points (lat et lng)
function latlngEquality(latlngA, latlngB)
{
    return latlngA.lat === latlngB.lat && latlngA.lng === latlngB.lng;
}

//ajout des layers à la map après le chargement
function segmentLoaded(poly)
{
    var res = false;
    drawnItems.eachLayer(function (layer) {
        if(layer.id === poly.id)
        {
            res = true;
            if(!isCreateRoute)
            {
                layer.addTo(map);
            }
        }
    });
    return res;
}

function pogLoaded(pog)
{
    var res = false;
    pogGroup.eachLayer(function (layer) {
        if(latlngEquality(pog._latlng,layer._latlng))
        {
            res = true;
        }
    });
    return res;
}

//début du traçage de la route
function beginRoute()
{
    drawnItems.eachLayer(function (layer) {
        map.removeLayer(layer);
    });
    pogGroup.eachLayer(function (layer) {
        layer.on("click",function (e){
            var pog = e.target;
            pogGroup.eachLayer(function (layer){
                layer.off("click");
                map.removeLayer(layer);
            });
            pog.addTo(map);
            drawnItems.eachLayer(function(layer){
                var pog1Layer = layer._latlngs[0]; //POG1 layer
                var pog2Layer = layer._latlngs[layer._latlngs.length - 1]; //POG2 layer

                if(latlngEquality(pog1Layer,pog._latlng))
                {
                    layer.markers[0].addTo(map);
                    attention(layer);
                    potentialPoly.push(layer);
                    layer.addTo(map);
                }
                else if(latlngEquality(pog2Layer,pog._latlng))
                {
                    layer.markers[layer.markers.length - 1].addTo(map);
                    attention(layer);
                    potentialPoly.push(layer);
                    layer.addTo(map);
                }

            });
            jQuery.each(potentialPoly,function(index,value){
                value.on("click",function (e){
                    buildRoute(e);
                })
            })
        })
    });
}

//fonction qui trouve une route avec deux POGs donnés
function autoRoute()
{
    computePogs = [];
    //autoButton.removeFrom(map);
    drawnItems.eachLayer(function (layer){
        map.removeLayer(layer);
    });
    pogGroup.eachLayer(function (layer) {
        layer.on("click",function (e){
            computePogs.push(e.target);
            if(computePogs.length === 2)
            {
                jQuery.each(computePogs,function(k,v)
                {
                    v.off("click");
                });
                pogGroup.eachLayer(function (layer){
                    layer.off("click");
                });
                autoButton._activateStateNamed("auto-route");
                computeRoute(computePogs);
            }
        })

    });
}

//annulation 
function cancelAutoRoute()
{
    computePogs = [];
    pogGroup.eachLayer(function (layer) {
        layer.off("click");
    });

    drawnItems.eachLayer(function (layer){
        layer.addTo(map);
    });
}

//fonction de calcul de la detection automatique d'une route
function computeRoute(pogs)
{
    var finalPog = pogs[1];//Point d'arrivée
    var currentPogs = [];//Pogs traités actuellement
    currentPogs.push(pogs[0]);//On place le point de départ dans la liste des pogs actuels
    var poly = currentPogs[0].segment;
    var end = null;//Variable d'arret du while
    var tree = new TreeModel();
    var root = tree.parse({pog: currentPogs[0]});//Racine de l'arbre
    var node = root;//
    var possiblePogs = [];
    var latlngs = [];
    while(end === null)
    {
        var limit = 0;
        jQuery.each(currentPogs,function(key,value){
            if(limit === 1000)
            {
                console.log("limit");
                return false;
            }
            limit++;
            console.log(currentPogs);
            var continu = true;
            var skip = false;
            //On cherche tous les pogs enfants du pog courant
            pogGroup.eachLayer(function(layer)
            {
                if(!skip)
                {
                    var add;
                    if(latlngEquality(layer.segment.pogs[1]._latlng,value._latlng))
                    {
                        possiblePogs.push(layer.segment.pogs[0]);
                        add = layer.segment.pogs[0];
                    }
                    else if(latlngEquality(layer.segment.pogs[0]._latlng, value._latlng))
                    {
                        possiblePogs.push(layer.segment.pogs[1]);
                        add = layer.segment.pogs[1];
                    }
                    if(add !== undefined && add === finalPog)
                    {
                        skip = true;
                    }
                }
            });
            //On récupère le node pour le pog courant
            var currentNode = root.first(function (node) {
                return node.model.pog === value;
            });
            //On ajoute les nodes des enfants
            jQuery.each(possiblePogs,function(k,v){
                var newNode = tree.parse({pog : v});
                currentNode.addChild(newNode);
                if(v._leaflet_id === finalPog._leaflet_id)//Si on arrive au point final
                {
                    end = 0;
                    continu = false;
                    return false;
                }
            });
            return continu;
        });
        if(possiblePogs.length === 0)
        {
            end = 0;
        }
        currentPogs = possiblePogs.slice();
        possiblePogs = [];
    }
    var endNode = root.all(function (node) {
        if (node.model.pog._leaflet_id === finalPog._leaflet_id)
        {
            return node;
        }
    });
    if(endNode.length > 0)
    {
        console.log("test");
        var path = endNode[0].getPath();
        var polys = [];
        for(var i = 1; i < path.length; i++)
        {
            polys.push(path[i].model.pog.segment);

            if(latlngEquality(path[i].model.pog._latlng,finalPog._latlng))
            {
                break;
            }
        }
        for(var i = 0; i < polys.length; i++)
        {
            if(latlngEquality(path[i].model.pog._latlng,polys[i]._latlngs[polys[i]._latlngs.length - 1]))
            {
                Array.prototype.push.apply(latlngs,polys[i]._latlngs.reverse());
            }
            else{
                Array.prototype.push.apply(latlngs,polys[i]._latlngs);
            }
        }
        var polyline = new L.Polyline(latlngs, {color: 'yellow'}).addTo(map);
        polyArray = [];
        pointArray = [];
        polyArray.push(polyline);
        saveRoute();
    }
    else
    {
        $.notify("Aucun chemin trouvé","warning");
    }
}

//trouver un POG par son id
function findPogById(id)
{
    var res = "";
    pogGroup.eachLayer(function(layer)
    {
        if(layer._leaflet_id === id)
        {
            res = layer;
        }
    });
    return res;
}

// fonction de tansition 
function supprSegment_call()
{	
	if (!supprSeg) {
		supprSeg = true;
        pointArray = [];
        latlngArray = [];		
		drawnItems.eachLayer(function (layer){
            layer.on("click",function (e){
			supprSegment(e);
			});
        });
    }
	else
	{
		//desactivation du on click lorsqu'on appui sur le bouton
		supprSeg = false;
		drawnItems.eachLayer(function (layer){
            layer.off("click",function (e){
			
			});
        });
	}
}



// fonction de suppréssion d'un segment d'un tronçon 
function supprSegment(e)
{	
	var points_click = e.latlng;
	
	//si la polyline où l'on clique possède 4 points ou plus.
	if(count_object_property(e.target._latlngs) > 3)
	{	
		//on récupère les coordonnées du point passé en paramètre
		//calcul pour savoir si un point se trouve dans la ligne tracée entre les deux autres points 
		var compteur = 0
		var pt1;
		var pt2;
		var d1;
		var d2;
		var di;
		var add;
		var pos_detect;
		
		$.each(e.target._latlngs, function(pos_point, val) 
		{		
			//on récupère le point courant et le point suivant 
			pt1 = e.target._latlngs[compteur];
			
			if(compteur != (e.target._latlngs.length-1)) 
			{
				pt2 = e.target._latlngs[compteur+1];
				
				//calcul pour trouver a quel segment de polyline le point appartient.
				
				d1 = pt1.distanceTo(points_click);
				d2 = pt2.distanceTo(points_click);
				di = pt1.distanceTo(pt2);
				
				d1 = Number((d1).toFixed(0));
				d2 = Number((d2).toFixed(0));
				di = Number((di).toFixed(0));
				
				add = d1+d2;
				add = di - add;
				
				//console.log("point : " + compteur + "  (d1+d2)-di " + add);
				
				if(add >= -2 && add <= 2)
				{			
					//segment a supprimer (identification par sa position)
					pos_detect = compteur;
				}
			}
			
		compteur++;
		});
		
		//une fois qu'on a trouvé le point, on récupère les x premiers points de la polyline qu'on stocke dans une variable
		var points;
		var points2;
		
		//on récupère les points correspondant
		points = e.target._latlngs.slice(0,pos_detect+1);
		points2 = e.target._latlngs.slice(pos_detect+1,e.target._latlngs.length);

		//on set les points dans la première polyline
		e.target._latlngs = points;
		//on redessine la polyline	
		e.target.redraw();
		
		//ensuite on set les latlngs de l'ancienne dans une nouvelle polyline
		
		polyline = L.polyline(points2, {color: 'blue'});	
		//ajout dans le drawnItems.
		drawnItems.addLayer(polyline);
		//ajout a la map
		polyline.addTo(map);
		
		//sauvegarde des tronçons dans la base de données.
		
		var array_save = [];
		
		array_save.push(points);
		array_save.push(points2);
		
		saveMultiplePolyServer(array_save);
	}
	else
	{
		if(supprSeg)
		{
			$.notify("Ce tronçon ne contient pas assez de segments. (3 minimum)", "error");
		}
	}
	
}

// fonction de séparation d'une polyline 
function SegmentSlice(poly_key, tab_pos)
{	
	//on récupère une polyline et on la découpe en fonction du tab_pos donné en paramètre.
	var slice_result = {};
	var slice_count = 0; 
	
	//séparation du tableau en plusieurs sous sections
	
	//création d'une nouvelle variable
	var res_tab = [];
	
	$.each(tab_pos, function(key, val) {	
		if(val != 0 && val != drawnItems._layers[poly_key]._latlngs.length-1)
		{
			slice_result[val] = drawnItems._layers[poly_key]._latlngs.slice(slice_count,val+1);
			slice_count = val;
		}
	}); 
	
	//on ajoute au tableau slice_result la dernière partie des points manquant
	
	slice_result[slice_count+drawnItems._layers[poly_key]._latlngs.length] = drawnItems._layers[poly_key]._latlngs.slice(slice_count,drawnItems._layers[poly_key]._latlngs.length);
	//on supprime l'ancienne polyline et on recrée les autres avec le tableau slice_result
	
	map.removeLayer(drawnItems._layers[poly_key]);
	
	var get_elevation_array = [];
	var count = 0;
	$.each(slice_result, function(key, val) {
		points_current_get_elevation = val;
		
		//structure du tableau : tab[0].lat et tab[0].lng
		get_elevation_array[count] = {};
		$.each(val, function(pos, point) {
			get_elevation_array[count]["lat"] = point.lat;
			get_elevation_array[count]["lng"] = point.lng;
		});
		
		count++;
		
		//pour chaque groupe de points, on set l'elevation avec la fonction get_elevation_polys
		//get_elevation_polys(get_elevation_array);
		
		var URL = elevationMultipleSegmentURL + '&latLngCollection=';
		var result = {};

		for (var i = 0; i < get_elevation_array.length; i++) 
		{
			var lat = get_elevation_array[i].lat;
			var lng = get_elevation_array[i].lng;
			URL += lat + "," + lng;
			if (i !== get_elevation_array.length - 1) {
				URL += ",";
			}
		}
	
		URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
		elevationScript = document.createElement('script');
		elevationScript.type = 'text/javascript';
		elevationScript.src = URL;
		$("body").append(elevationScript);
		
		//save_points_elevation
		
		//pb avec les élévations des points
		//console.log(save_points_elevation);
	});
	
	//on supprime la polyline dans la base de données si elle existe
	if(drawnItems._layers[poly_key].id !== undefined)
	{
		var poly_id = drawnItems._layers[poly_key].id;
 	
		var tid = {};
		tid[poly_id] = poly_id;
		DeleteTrons(tid);
	}
	
	//création des nouvelles polylines
	
	//plus besoin de redessiner les polylines, comme le contenu est rechargé depuis la fonction loadSegment
	/*
	//slice_result contient le nuage de points, on le set dans les polylines
	$.each(slice_result, function(key, val) 
	{
		polyline = L.polyline(val, {color: 'blue'});	
		//ajout dans le drawnItems.
		//drawnItems.addLayer(polyline);
		//ajout a la map
		polyline.addTo(map);		
	}); */
	//on retire le layer du groupe
	drawnItems.removeLayer(drawnItems._layers[poly_key]);
	//on envois les modifications en base de données : 
	saveMultiplePolyServer(slice_result);
	
	//remise à zéro de la variable !!!!
	points_tab_index = {};
	
}

// suppression d'un tronçon 
function DeleteTrons(Tronids)
{
	$.ajax({
        type: "POST",
        url: Routing.generate('site_carto_delete_tron'),
        data: {"idTron" : Tronids},
        cache: false,
        success: function(){

		//rechargement de la map
		drawnItems.eachLayer(function (layer){
			map.removeLayer(layer);
		});
		pogGroup.eachLayer(function (layer){
			map.removeLayer(layer);
		});
			if(!is_reloading)
			{	
				loadSegments();
				is_reloading = true;
			}
        }		
    });
}

// save une liste de polyline 

function saveMultiplePolyServer(tab)
{
	tab = JSON.stringify(tab);
	$.ajax({
        type: "POST",
        url: Routing.generate('site_carto_save_multiple_segment'),
        data: {"tab" : tab},
        cache: false,
        success: function(data){
			drawnItems.eachLayer(function (layer){
				map.removeLayer(layer);
			});
			pogGroup.eachLayer(function (layer){
				map.removeLayer(layer);
			});
			if(!is_reloading)
			{
				//blocage pour ne pas recharger la map plusieurs fois !
				loadSegments();
				is_reloading = true;
			}
		$.notify("Segments sauvegardé", "success");
		
        }
    });

}

//???
function pogAlreadyComputed(pogArray,pog)
{
    var res = false;
    jQuery.each(pogArray,function(k,v){
        if(latlngEquality(v._latlng,pog._latlng))
        {
            res = true;
            return false;
        }
    });
    return res;
};

//fonction utilitaire pour compter le nombre de propriétés d'un objet
function count_object_property(val)
{
	var count = 0;
	for (var k in val) {
		if (val.hasOwnProperty(k)) 
		{
		   ++count;
		}
	}
	return count;
}

//récupérer l'élévation d'une polyline
function get_elevation_polys(polys_point)
{
	/*var URL = elevationMultipleSegmentURL + '&latLngCollection=';
	var result = {};

	for (var i = 0; i < polys_point.length; i++) 
	{
		var lat = polys_point[i].lat;
		var lng = polys_point[i].lng;
		URL += lat + "," + lng;
		if (i !== polys_point.length - 1) {
			URL += ",";
		}
	}
	
	URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
	elevationScript = document.createElement('script');
	elevationScript.type = 'text/javascript';
	elevationScript.src = URL;
	$("body").append(elevationScript);*/
}

//récupérer l'élévation de plusieurs segments
function getElevationMultipleSegment(response)
{
/*
    blockItineraireSave();
    denivelen = 0;
    denivelep = 0;
	
    for(var i = 0; i < points_current_get_elevation.length; i++)
    {
        points_current_get_elevation[i].elevation = response.elevationProfile[i].height;
        points_current_get_elevation[i].distance = response.elevationProfile[i].distance;
    }
    for(var i = 0; i < points_current_get_elevation.length - 1; i++)
    {
        var diff = points_current_get_elevation[i].elevation - points_current_get_elevation[i + 1].elevation;
        diff < 0 ? denivelep += diff * -1 : denivelen += diff * -1;
    }
    $("#long").val(points_current_get_elevation[points_current_get_elevation.length - 1].distance + "km");
    $("#denivp").text("Dénivelé positif : " + denivelep + "m");
    $("#denivn").text("Dénivelé négatif : " + denivelen + "m");
	
	//clone de l'objet pour pouvoir le reutiliser par la suite.	
	save_points_elevation = $.extend(true, {}, points_current_get_elevation);
	*/
}

//fonction pour enregistrer la modification d'un itinéraire en base de donnée
function updateiti(poly)
{
    var id = poly.id;
    var URL = elevationURLAJAX + '&latLngCollection=';
    var points = [];
    var denivelepUpdate = 0;
    var denivelenUpdate = 0;
    poly.markers = [];
    for (var i = 0; i < poly._latlngs.length; i++) {
        var marker = new L.Marker([poly._latlngs[i].lat, poly._latlngs[i].lng], {
            icon: new L.DivIcon({
                iconSize: new L.Point(12, 12),
                className: 'leaflet-div-icon leaflet-editing-icon'
            })
        });
        poly.markers.push(marker);
        markerGroup.addLayer(marker);
        points.push(new Point(poly._latlngs[i].lat, poly._latlngs[i].lng));
        URL += latlngs[i].lat + "," + latlngs[i].lng;
        if (i !== poly._latlngs.length - 1) {
            URL += ",";
        }
    }
    URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    $.when(function()
    {
        $.get(URL,
            function (data, status) {
                for(var i = 0; i < data.elevationProfile.length; i++)
                {
                    points.elevation = data.elevationProfile[i].height;
                    points.distance = data.elevationProfile[i].distance;
                }
                for(var i = 0; i < points.length - 1; i++)
                {
                    var diff = points[i].elevation - points[i + 1].elevation;
                    diff < 0 ? denivelepUpdate += diff * -1 : denivelenUpdate += diff * -1;
                }
            }
        );
    }).then(function()
    {
        $.post(Routing.generate('site_carto_updateItineraireTrace'),
            {
                points: JSON.stringify(points),
                longueur: points[points.length - 1].distance,
                denivelep: denivelepUpdate,
                denivelen: denivelenUpdate,
                id : id
            },
            function (data, status) {

                $.notify("Itinéraire mis à jour", "success");
            }
        ).fail(function () {
                $.notify("Erreur lors de la mise à jour", "error");
            });
    });


}