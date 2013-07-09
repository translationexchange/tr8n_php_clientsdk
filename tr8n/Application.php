<?php

namespace tr8n;

require_once "Tr8n.php";
require_once "Base.php";
require_once "Language.php";
require_once "Source.php";
require_once "Component.php";
require_once "Tr8nException.php";

class Application extends Base {

    public $host, $key, $secret, $name, $description, $definition, $version, $updated_at;
    public $languages, $translation_keys, $sources, $components;

    public static function init($host, $key, $secret, $options = array()) {
        if (is_null($options['definition'])) $options['definition'] = true;

        \Tr8n::logger()->info("Initializing application...");

        $app = Application::executeRequest("application", array('client_id' => $key, 'definition' => $options['definition']),
                           array('host' => $host, 'client_secret' => $secret, 'class' => 'tr8n\Application', 'attributes' => array(
                                    'host' => $host,
                                    'key' => $key,
                                    'secret' => $secret)
                           )
        );


        \Tr8n::logger()->info($app->languages);

        return $app;
    }

    function __construct($attributes) {
        parent::__construct($attributes);

        if (!$attributes['definition']) {
            $this->definition = array();
        }
        if ($attributes['languages']) {
            $this->languages = array();
            foreach($attributes['languages'] as $l) {
                array_push($this->languages, new Language(array_merge($l, array("application" => $this))));
            }
        }
        if ($attributes['sources']) {
            $this->sources = array();
            foreach($attributes['sources'] as $l) {
                array_push($this->sources, new Source(array_merge($l, array("application" => $this))));
            }
        }
        if ($attributes['components']) {
            $this->components = array();
            foreach($attributes['components'] as $l) {
                array_push($this->components, new Component(array_merge($l, array("application" => $this))));
            }
        }
    }

    public function language($locale = null) {
        return new Language(array());
    }

    /*
     *
     * API Related methods
     *
     */
    public function get($path, $params = array(), $options = array()) {
        return $this->api($path, $params, $options);
    }

    public function post($path, $params = array(), $options = array()) {
        $options["POST"] = true;
        return $this->api($path, $params, $options);
    }

    public function api($path, $params = array(), $options = array()) {
        $options["client_id"] = $this->client_id;
        $options["t"] = microtime(true);

        return self::executeRequest($path, $params, $options);
    }

}

?>