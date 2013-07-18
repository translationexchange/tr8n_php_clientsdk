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

namespace Tr8n\Utils;

class StringUtils {

    public static function startsWith($match, $str) {
        if (is_array($match)) {
            foreach($match as $option) {
                if (self::startsWith($option, $str)) return true;
            }
            return false;
        }
        return preg_match('/^'.$match.'/', $str);
    }

    public static function endsWith($match, $str) {
        if (is_array($match)) {
            foreach($match as $option) {
                if (self::endsWith($option, $str)) return true;
            }
            return false;
        }
        return preg_match('/'.$match.'$/', $str);
    }

    public static function splitSentences($text, $opts = array()) {
        $sentence_regex = '/[^.!?\s][^.!?]*(?:[.!?](?![\'"]?\s|$)[^.!?]*)*[.!?]?[\'"]?(?=\s|$)/';

        $matches = array();
        preg_match_all($sentence_regex, strip_tags($text), $matches);
        $matches = array_unique($matches[0]);

        return $matches;
    }

}