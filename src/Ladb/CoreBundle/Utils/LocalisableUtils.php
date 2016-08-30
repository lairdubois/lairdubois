<?php

namespace Ladb\CoreBundle\Utils;

use Ivory\GoogleMap\Overlays\InfoWindow;
use Ivory\GoogleMap\Events\MouseEvent;
use Ivory\GoogleMap\Services\Geocoding\GeocoderRequest;
use Ladb\CoreBundle\Model\LocalisableExtendedInterface;
use Ladb\CoreBundle\Model\LocalisableInterface;

class LocalisableUtils extends AbstractContainerAwareUtils {

	const NAME = 'ladb_core.localisable_utils';

	/////

	public function geocodeLocation(LocalisableInterface $localisable) {
		if (!is_null($localisable->getLocation())) {

			$geocoder = $this->get('ivory_google_map.geocoder');
			$request = new GeocoderRequest();
			$request->setAddress($localisable->getLocation());
			$request->setLanguage('fr');
			$response = $geocoder->geocode($request);

			if (count($response->getResults()) > 0) {

				$result = $response->getResults()[0];

				// Location
				$location = $result->getGeometry()->getLocation();
				$localisable->setLatitude($location->getLatitude());
				$localisable->setLongitude($location->getLongitude());

				if ($localisable instanceof LocalisableExtendedInterface) {

					// PostalCode /////

					$postalCodes = $result->getAddressComponents('postal_code');
					if (count($postalCodes) > 0) {
						$localisable->setPostalCode($postalCodes[0]->getLongName());
					}

					// Locality /////

					$localities = $result->getAddressComponents('locality');
					if (count($localities) > 0) {
						$localisable->setLocality($localities[0]->getLongName());
					}

					// Country /////

					$countries = $result->getAddressComponents('country');
					if (count($countries) > 0) {
						$localisable->setCountry($countries[0]->getLongName());
					}

					// GeographicalAreas /////

					$geographicalAreaParts = array();

					$localities = $result->getAddressComponents('locality');
					foreach ($localities as $locality) {
						$geographicalAreaParts[] = $locality->getLongName();
					}
					$administrativeAreaLevel2s = $result->getAddressComponents('administrative_area_level_2');
					foreach ($administrativeAreaLevel2s as $administrativeAreaLevel2) {
						$geographicalAreaParts[] = $administrativeAreaLevel2->getLongName();
					}
					$administrativeAreaLevel1s = $result->getAddressComponents('administrative_area_level_1');
					foreach ($administrativeAreaLevel1s as $administrativeAreaLevel1) {
						$geographicalAreaParts[] = $administrativeAreaLevel1->getLongName();
					}
					$countries = $result->getAddressComponents('country');
					foreach ($countries as $country) {
						$geographicalAreaParts[] = $country->getLongName();
					}

					if (!empty($geographicalAreaParts)) {
						$localisable->setGeographicalAreas(implode(',', $geographicalAreaParts));
					} else {
						$localisable->setGeographicalAreas(null);
					}

					// FormattedAddress /////

					$localisable->setFormattedAddress($result->getFormattedAddress());

				}

				return true;
			}

		} else {
			$localisable->setLatitude(null);
			$localisable->setLongitude(null);
			if ($localisable instanceof LocalisableExtendedInterface) {
				$localisable->setGeographicalAreas(null);
				$localisable->setPostalCode(null);
				$localisable->setLocality(null);
				$localisable->setFormattedAddress($localisable->getLocation());
			}
		}

		return false;
	}

	public function getMap($localisables, $zoom = 4, $autoCenterMap = true, $infoWindowTemplate = null) {
		if (is_null($localisables)) {
			return null;
		}
		if ($localisables instanceof LocalisableInterface) {
			if (is_null($localisables->getLatitude()) || is_null($localisables->getLongitude())) {
				return null;
			}
			$localisables = array( $localisables );
		}
		$map = null;
		if (is_array($localisables)) {

			$map = $this->get('ivory_google_map.map');
			$map->setMapOption('zoom', $zoom);

			foreach ($localisables as $localisable) {

				if ($localisable instanceof LocalisableInterface) {

					$markerIcon = method_exists($localisable, 'getMarkerIcon') && !is_null($localisable->getMarkerIcon()) ? $localisable->getMarkerIcon() : 'default';

					// Create the marker
					$marker = $this->get('ivory_google_map.marker');
					$marker->setIcon('/bundles/ladbcore/ladb/images/markers/'.$markerIcon.'.png');
					$marker->setPosition(new \Ivory\GoogleMap\Base\Coordinate($localisable->getLatitude(), $localisable->getLongitude()));
					if ($autoCenterMap) {
						$map->setCenter($localisable->getLatitude(), $localisable->getLongitude(), true);
					} else {
						$map->setCenter(30, 1.7, true);
					}
					$map->addMarker($marker);

					if (!is_null($infoWindowTemplate)) {
						$infoWindow = new InfoWindow();
						$infoWindow->setPrefixJavascriptVariable('info_window_');
						$infoWindow->setContent($this->get('templating')->render($infoWindowTemplate, array( 'localisable' => $localisable )));
						$infoWindow->setOpen(false);
						$infoWindow->setAutoOpen(true);
						$infoWindow->setOpenEvent(MouseEvent::CLICK);
						$infoWindow->setAutoClose(true);
						$infoWindow->setOptions(array(
							'maxWidth' => 350,
							'zIndex'   => 10,
						));
						$marker->setInfoWindow($infoWindow);
					}

				}

			}

		}
		return $map;
	}

}