<?php
namespace mate\Resource;

class ResourceFactory
{
    /**
     * creates resource out of parameter $resource if its type is known.
     * Otherwise just gives back the parameter $resource again.
     * @param mixed $resource
     * @param array $options
     * @return mixed|ResourceAbstract
     */
    public static function create(&$resource, $options = array())
    {
        $class = self::getResourceClass($resource);
        if($class !== NULL) {
            // break reference by default to avoid recursions
            // will be set to true by returnValue() (ResourceAbstract)
            if(!isset($options['breakReference']) || $options['breakReference'] == true) {
                $set = $resource;
            } else {
                $set =& $resource;
            }
            return new $class($set, $options);
        }
        return $resource;
    }

    /**
     * function which are checking for resource types
     * @return array
     * @deprecated
     */
    public static function getResourceConditions()
    {
        return array(
            __NAMESPACE__ . '\ArrayResource'  => function ($resource, $type) {
                return $type === "array";
            },
            __NAMESPACE__ . '\CsvResource'    => function ($resource, $type) {
                if($type === "string") {
                    if(is_file($resource)
                        && pathinfo($resource, PATHINFO_EXTENSION) == 'csv'
                    )
                        return true;
                }
                return false;
            },
            __NAMESPACE__ . '\JsonResource'   => function ($resource, $type) {
                if($type === "string") {
                    if(is_file($resource)
                        && pathinfo($resource, PATHINFO_EXTENSION) == 'json'
                    ) {
                        return true;
                    }
                    if((substr($resource, 0, 1) === '{'
                        || substr($resource, 0, 1) === '[')
                    ) {
                        json_decode($resource);
                        return (json_last_error() == JSON_ERROR_NONE);
                    }
                }
                return false;

            },
            __NAMESPACE__ . '\XMLResource'    => function ($resource, $type) {
                if($resource instanceof \SimpleXMLElement) {
                    return true;
                }
                if($type === "string") {
                    if(is_file($resource)) {
                        $pathinfo = pathinfo($resource);
                        if($pathinfo['extension'] == 'xml') {
                            return true;
                        }
                    }
                    libxml_use_internal_errors(true);
                    $readString = simplexml_load_string($resource);
                    if($readString !== false) {
                        libxml_use_internal_errors(false);
                        return true;
                    }
                    libxml_use_internal_errors(false);
                }

                return false;
            },
            __NAMESPACE__ . '\ObjectResource' => function ($resource, $type) {
                return $type === "object";
            },
            __NAMESPACE__ . '\EdiResource'    => function ($resource) {

            }
        );
    }

    /**
     * get resource class name depending on the provided resource
     * returns NULL if no resource can be created with the given resource
     * @param $resource
     * @return null|string
     */
    public static function getResourceClass($resource)
    {
        $type = gettype($resource);

        // ARRAY
        if($type === "array") {
            return __NAMESPACE__ . '\ArrayResource';
        }

        // STRING / FILE PATH types
        if($type === "string") {

            // FILES

            if(is_file($resource)) {
                $extension = pathinfo($resource, PATHINFO_EXTENSION);

                switch ($extension) {
                    case "csv":
                        return __NAMESPACE__ . '\CsvResource';
                    case "json":
                        return __NAMESPACE__ . '\JsonResource';
                    case "xml":
                        return __NAMESPACE__ . '\XMLResource';
                    case "edi":
                        return __NAMESPACE__ . '\EdiResource';
                    default:
                        return null;
                }
            }

            // CASUAL STRINGS

            // JsonResource
            if((substr($resource, 0, 1) === '{' || substr($resource, 0, 1) === '[')) {
                json_decode($resource);
                if(json_last_error() == JSON_ERROR_NONE) {
                    return __NAMESPACE__ . '\JsonResource';
                }
            }

            // EdiResource
            if(substr($resource, 0, 9) == "UNA:+.? '") {
                return __NAMESPACE__ . '\EdiResource';
            }

            // XMLResource
            if(substr($resource, 0, 5) == "<?xml") {
                libxml_use_internal_errors(true);
                $readString = simplexml_load_string($resource);
                libxml_use_internal_errors(false);
                if($readString !== false) {
                    return __NAMESPACE__ . '\XMLResource';
                }
            }
        }

        // OBJECT
        if($type === "object") {

            // XMLResource
            if($resource instanceof \SimpleXMLElement) {
                return __NAMESPACE__ . '\XMLResource';
            }

            // ObjectResource
            return __NAMESPACE__ . '\ObjectResource';
        }

        return NULL;
    }

}