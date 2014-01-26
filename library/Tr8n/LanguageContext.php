<?php
/**
 * Copyright (c) 2014 Michael Berkovich, http://tr8nhub.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Tr8n;

class LanguageContext extends Base {

    /**
     * @var Language
     */
    public $language;

    /**
     * @var string
     */
    public $keyword;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string[]
     */
    public $keys;

    /**
     * @var string
     */
    public $default_key;

    /**
     * @var string
     */
    public $token_expression;

    /**
     * @var string[]
     */
    public $variables;

    /**
     * @var string[]
     */
    public $token_mapping;

    /**
     * @var LanguageContextRule[]
     */
    public $rules;

    /**
     * @param array $attributes
     */
    function __construct($attributes=array()) {
        parent::__construct($attributes);

        $this->rules = array();
        if (isset($attributes['rules'])) {
            foreach($attributes['rules'] as $key => $rule) {
                $this->rules[$key] = new LanguageContextRule(array_merge($rule, array("language_context" => $this)));
            }
        }
    }

    /**
     * @return array
     */
    function config() {
        $context_rules = Config::instance()->contextRules();
        if ($context_rules && isset($context_rules[$this->keyword]))
            return $context_rules[$this->keyword];
        return array();
    }

    /**
     * @param $token
     * @return bool
     */
    function isAppliedToToken($token) {
        return (1==preg_match($this->token_expression, $token));
    }

    /**
     * Fallback rule usually has a key of "other", but that may not be necessary in all cases.
     * @return mixed
     */
    function fallbackRule() {
        if (!isset($this->fallback_rule)) {
            foreach($this->rules as $key => $rule) {
                if ($rule->isFallback()) {
                    $this->fallback_rule = $rule;
                }
            }
        }

        return $this->fallback_rule;
    }

    /**
     * @param $obj
     * @return array
     */
    function vars($obj) {
        $vars = array();
        $config = $this->config();
        foreach($this->variables as $key) {
            if (!isset($config["variables"]) || !isset($config["variables"][$key])) {
                $vars[$key] = $obj;
                continue;
            }

            $method = $config["variables"][$key];
            if (is_string($method)) {
                if (is_object($obj)) {
                    $vars[$key] = $obj->$method;
                } else if (is_array($obj)) {
                    if (isset($obj["object"])) $obj = $obj["object"];
                    if (is_object($obj))
                        $vars[$key] = $obj->$method;
                    else
                        $vars[$key] = $obj[$method];
                } else {
                    $vars[$key] = $method;
                }
            } else if (is_callable($method)) {
                $vars[$key] = $method($obj);
            } else {
                $vars[$key] = $obj;
            }
        }

        return $vars;
    }

    /**
     * @param $obj
     * @return mixed|LanguageContextRule
     */
    function findMatchingRule($obj) {
        $token_vars = $this->vars($obj);
        foreach($this->rules as $key => $rule) {
            if ($rule->isFallback()) {
                continue;
            }
            if ($rule->evaluate($token_vars))
                return $rule;
        }

        return $this->fallbackRule();
    }

    /**
     * @param array $keys
     * @return array
     */
    public function toArray($keys=array()) {
        $info = parent::toArray(array("keyword", "description", "keys", "default_key", "token_expression", "variables", "token_mapping"));
        $info["rules"] = array();
        foreach($this->rules as $name=>$value) {
            $info["rules"][$name] = $value->toArray();
        }
        return $info;
    }

}

