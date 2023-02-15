## Leaflet module
(for Drupal 9 & 10)

### General Information

**Leaflet module**  provides integration with
[Leaflet JS library](http://leafletjs.com).
It is dependent on the Drupal [Geofield](https://www.drupal.org/project/geofield) Module.

### Featured options and functionalities.

- Easy to use API for extended Leaflet Map definition & customisation;
- Leaflet Default Widget, with advanced integration with [Leaflet-Geoman plugin](https://github.com/geoman-io/leaflet-geoman)
  for creating and editing Points and Geometries (Linestring, Polygon) Layers;
- Leaflet Tooltips and Popups;
- Leaflet Multi Maps Base Layers Control;
- Leaflet Overlays Control via Drupal Views Grouping;
- Dynamic Markers Icons and Paths/Geometries Styling, with tokens and
replacement patterns
- Markerclustering,via [Leaflet.markercluster plugin](https://github.com/Leaflet/Leaflet.markercluster);
- Leaflet Gesture handling, via [Leaflet.GestureHandling plugin](https://github.com/elmarquis/Leaflet.GestureHandling);
- Reset Map View Control via [Leaflet.ResetView plugin](https://github.com/drustack/Leaflet.ResetView);
- Fullscreen Control, via [Leaflet.Control.FullScreen plugin](https://github.com/brunob/leaflet.fullscreen);
- User Location Control, via [Leaflet.Locate plugin](https://github.com/domoritz/leaflet-locatecontrol);
- Feature Additional Properties settings for advanced / dynamic customisation of
  Leaflet Map and Features rendering logics;
- Address Search Geocoding with Autocomplete integration (requires  Geocoder module);
- Token and Replacement Patterns in Leaflet components settings;
- Multiple Leaflet maps (mixed of Leaflet Formatters, Views and Widgets) on the
same page;
- Drupal hooks for altering and interacting with its functionalities;
- etc. ...

### Installation and Use
- Require/Download the Leaflet module using Composer, which is simply running
the following command from your project package root   (where the main
composer.json file is sited):

  `composer require drupal/leaflet`

  which will also download the required [Geofield Module](https://www.drupal.org/project/geofield)
dependency and GeoPHP library.
- Enable the **Leaflet** module to be able to use
**Leaflet Map Geofield Widget** and **Leaflet Map Geofield Formatter**;
- Enable **Leaflet Views** (leaflet_views) submodule for **Leaflet Map Views
integration**. You need to add at least one geofield to the Fields list,
and select the Leaflet Map style in the Display Format;

- Enable **Leaflet Markercluster** (leaflet_markercluster) submodule for
[__Leaflet Markercluster Js plugin__](https://github.com/Leaflet/Leaflet.markercluster)
functionalities, both in the Leaflet Map Formatter and Leaflet Map View;

- Add/enable and configure the ["Geoocoder" module](https://www.drupal.org/project/geocoder)
to implement Geocoding Control(with Autocomplete) for Leaflet Map Address search,
in Leaflet Map Widget, Formatter and View;


### API Usage

Rendering a Leaflet map programmatically is as simple as instantiating the
LeafletService and its leafletRenderMap method:

    \Drupal::service('leaflet.service')->leafletRenderMap($map, $features, $height)

which expects 3 parameters:

* $map:
An associative array defining a map. See hook_leaflet_map_info(). The module
defines a default map with a OpenStreet Maps base layer.

* $features:
This is an associative array of all the Leaflet features you
want to plot on the map. A feature can be a point, linestring, polygon,
multipolygon, multipolygon, or json object. Additionally, features can be
grouped into [leaflet layer groups](http://leafletjs.com/reference-1.3.0.html#layergroup),
so they can be controlled together,

* $height:
The map height, expressed in css units.

### Tips & Tricks

- #### Bind events on geojson (json) features
  @see: https://www.drupal.org/project/leaflet/issues/3186029

  $features[] = [
    'type' => 'json',
    'json' => $geojson,
    'events' => [
      'click' => 'Drupal.manageGeojsonClick', // or whatever callback
    ],
  ];
  $this->leaflet->leafletRenderMap($map_info, $features, $height),

### Authors/Credits
* [itamair](https://www.drupal.org/u/itamair) (main maintainer since Drupal 8)
* [levelos](http://drupal.org/user/54135) (creator)
* [pvhee](http://drupal.org/user/108811)
* [rdeboer](http://drupal.org/user/108811)
* and other great people from the magic Drupal community ...
