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

namespace tr8n;

class TranslationKey extends Base {
    protected $application, $language, $translations;
    protected $id, $key, $label, $description, $locale, $level, $locked;
    protected $tokens;

	public function __construct($attributes=array()) {
        parent::__construct($attributes);

		$this->key = $this->generateKey($this->label, $this->description);
        if (!$this->locale) $this->locale = Config::instance()->default_locale;

        $this->translations = array();
        if (array_key_exists('translations', $attributes)) {
            foreach($attributes["translations"] as $locale => $translations) {
                $language = $this->application->language($locale);
                if (!$this->translations[$locale]) $this->translations[$locale] = array();
                foreach($translations as $translation_hash) {
                    $t = new Translation(array_merge($translation_hash, array("translation_key"=>$this, "locale"=>$language->locale, "language"=>$language)));
                    array_push($this->translations[$locale], $t);
                }
            }

        }
	}
	
	private function generateKey($label, $description) {
		return md5($label . ";;;" . $description);
	}

    public function key() {
        return $this->key;
    }

    public function language() {
        if ($this->language) return $this->language;
        return ($this->locale ? $this->application->language($this->locale) : $this->application->default_language());
    }


    function fetchTranslationsForLanguage($language, $options = array()) {
        if ($this->id && $this->hasTranslationsForLanguage($language))
            return $this;

        if (array_key_exists("dry", $options) && $options["dry"]) {
            return $this->application->cacheTranslationKey($this);
        }

        $tkey = $this->application->post("translation_key/translations",
                                array("key"=>$this->key, "label"=>$this->label, "description"=>$this->description, "locale" => $this->language()->locale),
                                array("class"=>'\Tr8n\TranslationKey', "attributes"=>array("application"=>$this->application, "language"=>$this->language())));

        return $this->application->cacheTranslationKey($tkey);
    }

    /*
     * Re-assigns the ownership of the application and translation key
     */
    public function setApplication($application) {
        $this->application = $application;
        foreach($this->translations as $locale=>$translations) {
            foreach($translations as $translation) {
                $translation->setTranslationKey($this);
            }
        }
    }

    /*
     * Set translations for a specific language
     */
    public function setTranslations($language, $translations) {
        foreach($translations as $translation) {
            $translation->setTranslationKey($this);
        }

        $this->translations[$language->locale] = $translations;
    }

    ###############################################################
    ## Translations Rules Evaluation
    ###############################################################
    public function translations($language) {
        if ($this->translations === null) return array();
        if (!array_key_exists($language->locale(), $this->translations)) return array();
        return $this->translations[$language->locale()];
    }

    public function hasTranslationsForLanguage($language) {
        return count($this->translations($language->locale())) > 0;
    }

    protected function findFirstValidTranslation($language, $token_values) {
        foreach($this->translations($language) as $translation) {
            if ($translation->isValidTranslation($token_values)) {
                return $translation;
            }
        }

        return null;
    }

    public function translate($language, $token_values = array(), $options = array()) {
		if (Config::instance()->isDisabled()) {
            return $this->substituteTokens($this->label, $token_values, $this->language, $options);
        }

        $translation = $this->findFirstValidTranslation($language, $token_values);
        $decorator = \Tr8n\Docorators\Base::decorator();

        if ($translation) {
            $processed_label = $this->substituteTokens($translation->label, $token_values, $this->language, $options);
            return $decorator->decorate($this, $language, $processed_label, array_merge($options, array("translated" => true)));
        }

        $processed_label =  $this->substituteTokens($this->label, $token_values, $this->application->defaultLanguage(), $options);
        return $decorator->decorate($this, $language, $processed_label, array_merge($options, array("translated" => false)));
	}

    ###############################################################
    ## Token Substitution
    ###############################################################
    public function tokens() {
        if (!$this->tokens) {
            $this->tokens = array();
            foreach(Config::instance()->tokenTypes() as $token_type) {
                $tokens = \Tr8n\Tokens\Base::registerTokens($this->label, $token_type);
                foreach($tokens as $token) {
                    $this->tokens[$token->name()] = $token;
                }
            }
        }

        return $this->tokens;
    }

    public function isTokenAllowed($token) {
       return array_key_exists($token->name(), $this->tokens());
    }

    public function substituteTokens($label, $token_values, $language, $options = array()) {
        $tokens = \Tr8n\Tokens\Base::registerTokens($label, 'data');
        foreach($tokens as $token) {
            if (!$this->isTokenAllowed($token)) continue;
            $lang = (get_class($token) == 'Tr8n\Tokens\TransformToken' ? $this->language() : $language);
            $label = $token->substitute($label, $token_values, $lang, $options);
        }

        // decoration tokens can be nested, so process tokens in a loop until no more tokens are left
        $tokens = \Tr8n\Tokens\Base::registerTokens($label, 'decoration', array("exclude_nested" => true));
        while (count($tokens) > 0) {
            foreach($tokens as $token) {
                if (!$this->isTokenAllowed($token)) continue;
                $label = $token->substitute($label, $token_values, $language, $options);
            }
            $tokens = \Tr8n\Tokens\Base::registerTokens($label, 'decoration', array("exclude_nested" => true));
        }

        return $label;
    }

}