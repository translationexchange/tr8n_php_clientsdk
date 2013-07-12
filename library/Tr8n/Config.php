<?php

#--
# Copyright (c) 2010-2013 Michael Berkovich, tr8nhub.com
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#++

namespace Tr8n;

require_once "Logger.php";
require_once "Application.php";

class Config {

    public $application, $default_locale;
    public $current_user, $current_language, $current_translator, $current_source, $current_component;
    public $current_translation_keys;

    private $rules_engine, $token_classes;

    public static function instance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new Config();
        }
        return $inst;
    }

    public function isEnabled() {
        return true;
    }

    public function isDisabled() {
        return !self::isEnabled();
    }

    public function isLoggerEnabled() {
        return true;
    }

    public function loggerFilePath() {
        return __DIR__."/../../log/tr8n.log";
    }

    public function loggerSeverity() {
        return Logger::DEBUG;
    }

    public function isCachingEnabled() {
        return true;
    }

    public function cacheStore() {
        return "memcache";
    }

    public function decoratorClass() {
        return '\Tr8n\Decorators\HtmlDecorator';
    }

    public function rulesEngine() {
        if ($this->rules_engine == null) {
            $this->rules_engine = array(
                "number" => array(
                    "class"             => '\Tr8n\Rules\NumericRule',
                    "Tokens"            => array("count", "num", "age", "hours", "minutes", "years", "seconds"),
                    "object_method"     => "number"
                ),
                "gender" => array(
                    "class"            => '\Tr8n\Rules\GenderRule',
                    "Tokens"           => array("user", "profile", "actor", "target"),
                    "object_method"    => "gender",
                    "method_values"    =>  array(
                        "female"         => "female",
                        "male"           => "male",
                        "neutral"        => "neutral",
                        "unknown"        => "unknown"
                    )
                ),
                "gender_list" => array(   // requires gender rule to be present
                    "class"            => '\Tr8n\Rules\GenderListRule',
                    "Tokens"           => array("users", "profiles", "actors", "targets"),
                    "object_method"    => "size"
                ),
                "list" => array(
                    "class"            => '\Tr8n\Rules\ListRule',
                    "Tokens"           => array("list", "items", "objects", "elements"),
                    "object_method"    => "size"
                ),
                "date" => array(
                    "class"            => '\Tr8n\Rules\DateRule',
                    "Tokens"           => array("date"),
                    "object_method"    => "to_date"
                ),
                "value" => array(
                    "class"            => '\Tr8n\Rules\ValueRule',
                    "Tokens"           => "*",
                    "object_method"    => "to_s"
                )
            );
        }
        return $this->rules_engine;
    }

    public function ruleClassByType($type) {
        $config = $this->rulesEngine();
        if ($config[$type] === null) return null;
        return $config[$type]["class"];
    }

    public function ruleTypesByTokenName($token_name) {
        $types = array();
        $sanitized_token_name = preg_replace("/[^A-Za-z]/", '', end(array_values(explode("_", $token_name))));

        foreach($this->rulesEngine() as $type => $config) {
            if ($config["Tokens"] == "*" || in_array($sanitized_token_name, $config["Tokens"])) {
                array_push($types, $type);
            }
        }
        return $types;
    }

    public function tokenClasses($type = null) {
        if ($this->token_classes == null) {
            $this->token_classes = array(
                "data" => array('\Tr8n\Tokens\DataToken', '\Tr8n\Tokens\MethodToken', '\Tr8n\Tokens\TransformToken'),
                "decoration" => array('\Tr8n\Tokens\DecorationToken')
            );
        }
        if ($type == null) return $this->token_classes;
        return $this->token_classes[$type];
    }

    /*
     * The token types here must be in the priority of evaluation.
     *
     * Data tokens must always be substituted before decoration tokens, so that the following example would work:
     *
     * [link: {user}] has [bold: {count||message}]
     *
     */
    public function tokenTypes() {
        return array("data", "decoration");
    }

}