(function ($, Drupal, once) {

  const searchapilocation = Drupal.searchapilocation || {};

  searchapilocation.maps = [];
  searchapilocation.markers = [];
  searchapilocation.circles = [];
  searchapilocation.config = [];

  let geocoder;

  let lat;
  let lng;
  let latLng;
  let myOptions;
  let singleClick;


  Drupal.behaviors.facetsIndexFormatter = {
    attach(context, settings) {
      geocoder = new google.maps.Geocoder();
    },
  };

  /**
   * Set the latitude and longitude values to the input fields
   * And optionaly update the address field
   *
   * @var latLng
   *   a location (latLng) object from google maps api
   * @var i
   *   the index from the maps array we are working on
   * @var op
   *   the op that was performed
   */
  searchapilocation.codeLatLng = function (latLng, i, op) {

    // Update the lat and lng input fields
    $(`#sal-${i}-lat`).val(latLng.lat());
    $(`#sal-${i}-lng`).val(latLng.lng());

    // Update the address field
    if ((op === 'marker' || op === 'geocoder') && geocoder) {
      geocoder.geocode({ latLng }, (results, status) => {
        if (status == google.maps.GeocoderStatus.OK) {
          $(`#${i}-address`).val(results[0].formatted_address);
        }
        else {
          $(`#${i}-address`).val('');
          if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
            alert(Drupal.t('Geocoder failed due to: ') + status);
          }
        }
      });
    }
  };

  /**
   * Get the location from the address field
   *
   * @param i
   *   the index from the maps array we are working on
   */
  searchapilocation.codeAddress = function (i) {
    const address = $(`#${i}-address`).val();

    geocoder.geocode({ address }, (results, status) => {
      if (status == google.maps.GeocoderStatus.OK) {
        searchapilocation.maps[i].setCenter(results[0].geometry.location);
        searchapilocation.setMapMarker(results[0].geometry.location, i);
        searchapilocation.codeLatLng(results[0].geometry.location, i, 'textinput');
      }
      else {
        alert(Drupal.t('Geocode was not successful for the following reason: ') + status);
      }
    });
  };

  /**
   * Set/Update a marker on a map
   *
   * @param latLng
   *   a location (latLng) object from google maps api
   * @param i
   *   the index from the maps array we are working on
   */
  searchapilocation.setMapMarker = function (latLng, i) {
    // Remove old marker and circle.
    if (searchapilocation.markers[i]) {
      searchapilocation.markers[i].setMap(null);
      searchapilocation.circles[i].setMap(null);
    }

    // Add marker.
    searchapilocation.markers[i] = new google.maps.Marker({
      map: searchapilocation.maps[i],
      draggable: true,
      animation: google.maps.Animation.DROP,
      position: latLng,
      icon: searchapilocation.config[i].marker_image,
    });

    // Add circle.
    searchapilocation.circles[i] = new google.maps.Circle({
      map: searchapilocation.maps[i],
      clickable: false,
      strokeColor: searchapilocation.config[i].radius_border_color,
      strokeWeight: searchapilocation.config[i].radius_border_weight,
      fillColor: searchapilocation.config[i].radius_background_color,
      fillOpacity: searchapilocation.config[i].radius_background_transparency,
      radius: $(`#sal-${i}-slider`).slider('value') * 1000,
      center: latLng,
    });

    // Fit the map to te circle.
    searchapilocation.maps[i].fitBounds(searchapilocation.circles[i].getBounds());

    // If called from <a>-Tag.
    return false;
  };

  // Work on each map.
  $.each(drupalSettings.search_api_location, (i, search_api_location_map) => {
    $(once('process', `#sal-${i}-map`)).each(() => {
      searchapilocation.config[i] = search_api_location_map;

      lat = parseFloat(search_api_location_map.lat);
      lng = parseFloat(search_api_location_map.lng);

      latLng = new google.maps.LatLng(lat, lng);

      // Set map options.
      myOptions = {
        zoom: 12,
        center: latLng,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        scrollwheel: false,
      };

      // Create map.
      searchapilocation.maps[i] = new google.maps.Map(document.getElementById(`sal-${i}-map`), myOptions);

      // Create slider.
      $(`#sal-${i}-slider`).slider({
        value: $(`#sal-${i}-radius`).val(),
        min: 1,
        max: 5000,
        step: 1,
        slide(event, ui) {
          $(`#sal-${i}-radius`).val(ui.value);
          searchapilocation.setMapMarker(searchapilocation.markers[i].getPosition(), i);
        },

        stop(event, ui) {
          $(`#sal-${i}-radius`).val(ui.value);
          $(`#sal-${i}-slider`).closest('form').submit();
        },
      });

      $(`#sal-${i}-slider`).val($(`#sal-${i}-slider`).slider('value'));

      if (lat && lng) {
        // Set initial marker
        searchapilocation.setMapMarker(latLng, i);
        searchapilocation.codeLatLng(latLng, i, 'geocoder');
      }

      $(`#${i}-geocode`).click((e) => {
        searchapilocation.codeAddress(i);
      });

      +      // Trigger on enter key.
        $(`#${i}-address`).keypress((ev) => {
          if (ev.which == 13) {
            ev.preventDefault();
            searchapilocation.codeAddress(i);
          }
        });

      // Listener to click.
      google.maps.event.addListener(searchapilocation.maps[i], 'click', (me) => {
        // Set a timeOut so that it doesn't execute if dbclick is detected.
        singleClick = setTimeout(() => {
          searchapilocation.codeLatLng(me.latLng, i, 'marker');
          searchapilocation.setMapMarker(me.latLng, i);
        }, 500);
      });


      // Detect double click to avoid setting marker.
      google.maps.event.addListener(searchapilocation.maps[i], 'dblclick', (me) => {
        clearTimeout(singleClick);
      });

      // Listener to drag-end.
      google.maps.event.addListener(searchapilocation.markers[i], 'dragend', (me) => {
        searchapilocation.codeLatLng(me.latLng, i, 'marker');
        searchapilocation.setMapMarker(me.latLng, i);
      });
    });
  });
}(jQuery, Drupal, once));
