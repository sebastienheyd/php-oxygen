<?php
class GMaps
{
    protected $googleUrl = 'http://maps.googleapis.com/maps/api/geocode/json?address=';
    protected $address;
    protected $request;
    protected $json;

    public function __construct($address, $sensor)
    {
        $this->address = $address;
        
        $this->request = $this->googleUrl.urlencode($address);

        $this->request .= ($sensor) ? '&sensor=true' : '&sensor=false';

        $json = file_get_contents($this->request)  or die("url not loading");        
        $this->json = json_decode($json);        

        if($this->_getStatus() !== 'OK' && $this->_getStatus() !== 'ZERO_RESULTS')
        {
            throw new Exception('Geocoding ERROR status = '.$this->_getStatus());
        }
    }

    /**
     * @param string $address
     * @param boolean $sensor
     * @return GMaps
     */
    public static function getInstance($address, $sensor = false)
    {
        return new self($address, $sensor);
    }

    /**
     * Return the request status
     * 
     * @link https://developers.google.com/maps/documentation/geocoding/#StatusCodes
     */
    private function _getStatus()
    {
        return strval($this->json->status);
    }

    /**
     * Get the address latitude
     * 
     * @return float|false       Return the latitude, else false if not found
     */
    public function getLatitude()
    {
        if($this->_getStatus() !== 'OK') return false;
        return floatval($this->json->results[0]->geometry->location->lat);
    }

    /**
     * Get the address longitude
     * 
     * @return float|false       Return the longitude, else false if not found
     */
    public function getLongitude()
    {
        if($this->_getStatus() !== 'OK') return false;
        return floatval($this->json->results[0]->geometry->location->lng);
    }

    /**
     * Return the point accuracy from 0 (not found) to 9 (very precise).
     * 
     * @link https://developers.google.com/maps/documentation/geocoding/#Types
     * 
     * @return int|false        Return the point accuracy else false if not found
     */
    public function getAccuracy()
    {
        if($this->_getStatus() !== 'OK') return false;

        $type = strval($this->json->results[0]->types[0]);

        $locationType = strval($this->json->results[0]->geometry->location_type);

        if($locationType === 'ROOFTOP') return 9;

        $types = array( 
                        'country'                       => 1,
                        'political'                     => 1,
                        'natural_feature'               => 2,
                        'colloquial_area'               => 2,
                        'administrative_area_level_1'   => 2,
                        'park'                          => 3,
                        'postal_code'                   => 3,
                        'administrative_area_level_2'   => 3,
                        'administrative_area_level_3'   => 3,
                        'locality'                      => 4,
                        'sublocality'                   => 4,
                        'neighborhood'                  => 4,
                        'postal_code'                   => 5,
                        'route'                         => 6,
                        'intersection'                  => 7,
                        'street_address'                => 8,
                        'premise'                       => 9,
                        'airport'                       => 9,
                        'subpremise'                    => 9,
                        'post_box'                      => 9,
                        'street_number'                 => 9,
                        'floor'                         => 9,
                        'room'                          => 9,
                        'point_of_interest'             => 9
            );

        if(isset($types[$type])) return $types[$type];
        
        return 0;
    }
}
