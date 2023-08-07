/**
 * @file
 * Provides the faceted map functionality.
 */

(function ($) {
  Drupal.behaviors.mapFacets = {
    attach() {
      L.Heatmap = L.GeoJSON.extend({
        options: {
          type: 'clusters',
        },

        initialize() {
          const _this = this;
          _this._layers = {};
          _this._getData();
        },

        onAdd(map) {
          const _this = this;
          // Call the parent function
          L.GeoJSON.prototype.onAdd.call(_this, map);
          map.on('moveend', () => {
            _this.clusterMarkers.clearLayers();
            window.location.href = drupalSettings.facets.map.url.replace('__GEOM__', _this._mapViewToWkt() + hash.lastHash);
            _this._getData();
          });
        },

        _computeHeatmapObject() {
          const _this = this;
          _this.facetHeatmap = {};
          const facetHeatmapArray = JSON.parse(drupalSettings.facets.map.results);
          // Convert array to an object
          $.each(facetHeatmapArray, (index, value) => {
            if ((index + 1) % 2 !== 0) {
              // Set object keys for even items
              _this.facetHeatmap[value] = '';
            }
            else {
              // Set object values for odd items
              _this.facetHeatmap[facetHeatmapArray[index - 1]] = value;
            }
          });
          this._computeIntArrays();
        },

        _createClusters() {
          const _this = this;
          _this.clusterMarkers = new L.MarkerClusterGroup({
            maxClusterRadius: 140,
          });
          $.each(_this.facetHeatmap.counts_ints2D, (row, value) => {
            if (value === NULL) {
              return;
            }

            $.each(value, (column, val) => {
              if (val === 0) {
                return;
              }

              const bounds = new L.LatLngBounds([
                [_this._minLat(row), _this._minLng(column)],
                [_this._maxLat(row), _this._maxLng(column)],
              ]);
              _this.clusterMarkers.addLayer(new L.Marker(bounds.getCenter(), {
                count: val,
              }).bindPopup(val.toString()));
            });
          });
          map.addLayer(_this.clusterMarkers);
        },

        _computeIntArrays() {
          const _this = this;
          _this.lengthX = (_this.facetHeatmap.maxX - _this.facetHeatmap.minX) / _this.facetHeatmap.columns;
          _this.lengthY = (_this.facetHeatmap.maxY - _this.facetHeatmap.minY) / _this.facetHeatmap.rows;
          _this._createClusters();
        },

        _minLng(column) {
          return this.facetHeatmap.minX + (this.lengthX * column);
        },

        _minLat(row) {
          return this.facetHeatmap.maxY - (this.lengthY * row) - this.lengthY;
        },

        _maxLng(column) {
          return this.facetHeatmap.minX + (this.lengthX * column) + this.lengthX;
        },

        _maxLat(row) {
          return this.facetHeatmap.maxY - (this.lengthY * row);
        },

        _getData() {
          const _this = this;
          _this._computeHeatmapObject();
        },

        /**
         * Provides the bounding box coordinates of map viewport.
         *
         * @return {string}
         */
        _mapViewToWkt() {
          if (this._map === undefined) {
            return '["-180 -90" TO "180 90"]';
          }
          const bounds = this._map.getBounds();
          const wrappedSw = bounds.getSouthWest().wrap();
          const wrappedNe = bounds.getNorthEast().wrap();
          return `["${wrappedSw.lng} ${bounds.getSouth()}" TO "${wrappedNe.lng} ${bounds.getNorth()}"]`;
        },
      });

      L.heatmap = function (options) {
        return new L.Heatmap(options);
      };

      // Check if L.MarkerCluster is included.
      if (typeof L.MarkerCluster !== 'undefined') {
        L.MarkerCluster.prototype.initialize = function (group, zoom, a, b) {
          L.Marker.prototype.initialize.call(this, a ? (a._cLatLng || a.getLatLng()) : new L.LatLng(0, 0), { icon: this });
          this._group = group;
          this._zoom = zoom;
          this._markers = [];
          this._childClusters = [];
          this._childCount = 0;
          this._iconNeedsUpdate = TRUE;
          this._bounds = new L.LatLngBounds();
          if (a) {
            this._addChild(a);
          }
          if (b) {
            this._addChild(b);
            this._childCount = b.options.count;
          }
        };
      }

      let map = L.map(drupalSettings.facets.map.id).setView([0, 0], 1);
      let hash = new L.Hash(map);
      L.tileLayer('http://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="http://cartodb.com/attributions">CartoDB</a>',
      }).addTo(map);
      L.heatmap({ type: 'clusters' }).addTo(map);
    },
  };
}(jQuery, Drupal));
