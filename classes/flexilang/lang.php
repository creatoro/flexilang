<?php defined('SYSPATH') or die('No direct script access.');

class Flexilang_Lang {

    /**
     * @var  boolean  this will force the default language to be prepended or not
     */
    public static $default_prepended = TRUE;

    /**
     * @var  boolean  enable or disable i18n routes
     */
    public static $i18n_routes = FALSE;

    /**
     * Finds the current language or falls back to default if no available
     * language found
     *
     * @param   string  $uri  the URI
     * @return  string
     * @uses    Request::detect_uri
     * @uses    Lang::available_languages
     * @uses    Lang::find_default()
     */
    public static function find_current($uri = NULL)
    {
        if ($uri === NULL)
        {
            // Get the URI
            $uri = Request::detect_uri();
        }

        // Normalize URI
        $uri = ltrim($uri, '/');

        // Set available languages
        $available_languages = Lang::available_languages();

         if ( ! preg_match('~^(?:'.implode('|', $available_languages).')(?=/|$)~i', $uri, $matches))
         {
             // Find the best default language
             $matches[0] = Lang::find_default();
         }

        // Return the detected language
        return strtolower($matches[0]);
    }

    /**
     * @var  array  available languages
     */
    public static $available_languages;

    /**
     * Sets the available languages
     *
     * @return  array
     * @uses    Lang::config
     */
    public static function available_languages()
    {
        if ( ! Lang::$available_languages)
        {
            // Set available languages
            Lang::$available_languages = array_keys(Lang::config());
        }

        return Lang::$available_languages;
    }

    /**
     * @var  array  config
     */
    public static $config;

    /**
     * Loads the configuration
     *
     * @return array
     */
    public static function config()
    {
        if ( ! Lang::$config)
        {
            // Load config
            Lang::$config = (array) Kohana::$config->load('lang');
        }

        return Lang::$config;
    }

    /**
     * @var  string  name of the cookie that contains language
     */
    public static $cookie = 'lang';

    /**
     * @var  string  hard-coded default language, must match a language key from lang config file
     */
    public static $default = 'en';

    /**
     * Looks for the best default language available and returns it
     *
     * A language cookie and HTTP Accept-Language headers are taken into account.
     *
     *     $lang = Lang::default();
     *
     * @return  string  language key, e.g. "en", "fr", "nl", etc.
     * @uses    Lang::available_languages
     * @uses    Cookie::get
     * @uses    Lang::$cookie
     * @uses    Request::accept_lang
     * @uses    Lang::$default
     */
	public static function find_default()
	{
		// All available languages
		$available_languages = Lang::available_languages();

		// Look for language cookie first
		if ($lang = Cookie::get(Lang::$cookie))
		{
			if (isset($available_languages[$lang]))
			{
				// Valid language found in cookie
				return $lang;
			}

			// Delete cookie with invalid language
			Cookie::delete(Lang::$cookie);
		}

		// Parse HTTP Accept-Language headers
		foreach (Request::accept_lang() as $lang => $quality)
		{
			if (isset($available_languages[$lang]))
			{
				// Return the first language found (the language with the highest quality)
				return $lang;
			}
		}

		// Return the hard-coded default language as final fallback
		return Lang::$default;
	}

    /**
     * Return the short code for the current language
     *
     * @param   string  $language  the language to extract the short code from
     * @return  string
     * @uses    i18n::$lang
     */
    public static function shortcode($language = NULL)
    {
        if ($language === NULL)
        {
            // Set language to current language
            $language = i18n::$lang;
        }

        return substr($language, 0, strpos($language, '-'));
    }

    /**
     * Return the i18n code for the current language
     *
     * @param   string  $language  the language to get the i18n code for
     * @return  string
     * @uses    i18n::$lang
     */
    public static function i18ncode($language = NULL)
    {
        if ($language === NULL)
        {
            // Return the current language's i18n code
            return i18n::$lang;
        }

        return Kohana::$config->load('lang.'.$language.'.i18n_code');
    }

} // End Flexilang_Lang