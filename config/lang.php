<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * List of all supported languages. Array keys match language segment from the URI.
 * A default fallback language can be set by Lang::$default.
 *
 * Options for each language:
 *  i18n_code    - The target language for the I18n class
 *  locale       - Locale name(s) for setting all locale information (http://php.net/setlocale)
 *  translations - Translated route parameters for i18n routes, only needed for non-default languages
 */
return array(

    'en' => array(
        'i18n_code'    => 'en-us',
        'locale'       => array('en_US.utf-8'),
    ),
	'de' => array(
		'i18n_code'    => 'de-de',
		'locale'       => array('de_DE.utf-8'),
        'translations' => array(),
	),
    'no_language_uri' => array(
        'sitemap.xml',
    ),

);