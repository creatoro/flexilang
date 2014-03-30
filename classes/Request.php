<?php

class Request extends Kohana_Request
{
	/**
	 * @var  string  the language of the main request
	 */
	public static $lang;

	/**
	 * Extension of the main request factory. If none given, the URI will
	 * be automatically detected. If the URI contains no language segment, the user
	 * will be redirected to the same URI with the default language prepended.
	 * If the URI does contain a language segment, I18n and locale will be set.
	 * Also, a cookie with the current language will be set. Finally, the language
	 * segment is chopped off the URI and normal request processing continues.
	 *
	 * @param   string  $uri URI of the request
	 * @param   Cache   $cache
	 * @param   array   $injected_routes an array of routes to use, for testing
	 * @return  Request
	 * @uses    Lang::config
	 * @uses    Request::detect_uri
	 * @uses    Lang::find_current
	 * @uses    Lang::$default_prepended
	 * @uses    Lang::$default
	 * @uses    Request::lang_redirect
	 * @uses    Request::$lang
	 * @uses    I18n::$lang
	 * @uses    Cookie::get
	 * @uses    Lang::$cookie
	 * @uses    Cookie::set
	 */
	public static function factory($uri = TRUE, $client_params = array(), $allow_external = TRUE, $injected_routes = array())
	{
		// Load config
		$config = Lang::config();

		if ($uri === TRUE)
		{
			// We need the current URI
			$uri = Request::detect_uri();
		}

		if (strpos($uri, 'https://') === 0 OR strpos($uri, 'http://') === 0 OR strpos($uri, 'ftp://') === 0)
		{
			return parent::factory($uri, $client_params, $allow_external, $injected_routes);
		}

		$uri = trim($uri, '/');

		// Get current language from URI
		$current_language = Lang::find_current($uri);

		if ( ! Lang::$default_prepended AND $current_language === Lang::$default AND strpos($uri, Lang::$default) === 0)
		{
			// If default is not prepended and current language is the default,
			// then redirect to the same URI, but with default language removed
			Request::lang_redirect(NULL, ltrim($uri, '/' . Lang::$default));
		}
		else if (Lang::$default_prepended AND $current_language === Lang::$default AND strpos($uri, Lang::$default) !== 0)
		{
			// If the current language is the default which needs to be
			// prepended, but it's missing, then redirect to same URI but with
			// language prepended
			Request::lang_redirect($current_language, '/' . $uri);
		}
		else if (!(strpos($uri, $current_language) === 0))
		{
			Request::lang_redirect($current_language, '/' . $uri);
		}

		// Language found in the URI
		Request::$lang = $current_language;

		// Store target language in I18n
		I18n::$lang = $config['languages'][Request::$lang]['i18n_code'];

		// Set locale
		setlocale(LC_ALL, $config['languages'][Request::$lang]['locale']);

		if (Cookie::get(Lang::$cookie) !== Request::$lang)
		{
			// Update language cookie if needed
			Cookie::set(Lang::$cookie, Request::$lang);
		}

		if (Lang::$default_prepended OR Request::$lang !== Lang::$default)
		{
			// Remove language from URI if default is prepended or the language is not the default
			$uri = (string) substr($uri, strlen(Request::$lang) + 1);
		}

		// Continue normal request processing with the URI without language
		return parent::factory($uri, $client_params, $allow_external, $injected_routes);
	}

	/**
	 * Redirects with or without language
	 *
	 * @param   string  $lang
	 * @param   string  $uri
	 * @return  void
	 * @uses    URL::base
	 */
	public static function lang_redirect($lang, $uri)
	{
		// Use the default server protocol
		$protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

		// Set headers
		header($protocol.' 302 Found');
		header('Location: ' . URL::base(TRUE, TRUE) . $lang . $uri);

		// Stop execution
		exit;
	}

} // End Request
