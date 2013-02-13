<?php

class Route extends Kohana_Route
{
	/**
	 * @var  config
	 */
	protected static $_language_config;

	/**
	 * Translates a route parameter into target language or falls back to
	 * default language
	 *
	 * @param   string  $value            value to translate
	 * @param   string  $target           target language
	 * @return  string                    value translated into target language
	 * @uses    Route::$_language_config
	 * @uses    Kohana::$config->load
	 * @uses    I18n::$source
	 * @uses    Arr::get
	 */
	public static function map($value, $target)
	{
		if ( ! isset(Route::$_language_config[$target]) OR $target !== Lang::shortcode())
		{
			// Load configuration if not yet loaded or the target language is
			// not the current language which means translation is needed
			Route::$_language_config[$target] = (array) Kohana::$config->load('lang.'.$target.'.translations');
		}

		if ($target === Lang::shortcode(I18n::$source) OR $value === NULL)
		{
			// No translation needed or value not supplied, return as is
			return $value;
		}

		// Return translated value if possible or fall back to source value
		return Arr::get(Route::$_language_config[$target], $value, $value);
	}

	/**
	 * Translates a route parameter into application (source) language or falls
	 * back to translated value
	 *
	 * @param   string  $value            value to translate
	 * @return  string                    value translated into application language (source language as defined by I18n::$source)
	 * @uses    Route::$_language_config
	 * @uses    Kohana::$config->load
	 * @uses    Lang::shortcode
	 * @uses    I18n::$lang
	 * @uses    I18n::$source
	 */
	public static function remap($value)
	{
		if ( ! isset(Route::$_language_config[Lang::shortcode()]))
		{
			// Load configuration
			Route::$_language_config[Lang::shortcode()] = (array) Kohana::$config->load('lang.'.Lang::shortcode().'.translations');
		}

		if (I18n::$lang === I18n::$source OR $value === NULL)
		{
			// No translation needed or value not supplied, return as is
			return $value;
		}

		if ($match = array_search($value, Route::$_language_config[Lang::shortcode()]))
		{
			// Source of translated value is found, return source
			return $match;
		}

		// The source of translated value is not found, return as is
		return $value;
	}

	/**
	 * @var  array  parameters and values to translate
	 */
	protected $_translate = array();

	/**
	 * @var  string  source URI
	 */
	protected $_uri_source;

	/**
	 * @var  array  source regex
	 */
	protected $_regex_source;

	/**
	 * Sets what parameters and text values of a route are translated
	 *
	 * @param   array  $translate     parameters and values to translate
	 * @return  $this or array
	 * @uses    Lang::$i18n_routes
	 * @uses    Lang::find_current()
	 * @uses    Route::compile
	 */
	public function translate(array $translate = NULL)
	{
		if ( ! Lang::$i18n_routes OR $translate === NULL)
		{
			// Nothing to translate
			return $this->_translate;
		}

		// Set translate
		$this->_translate = $translate ? $translate : array();

		// Translate the URI to the current language
		$this->translate_route(Lang::find_current());

		if ($this->_uri_source !== $this->_uri OR $this->_regex_source !== $this->_regex)
		{
			// URI and / or regex was translated, update route_regex
			$this->_route_regex = Route::compile($this->_uri, $this->_regex);
		}

		return $this;
	}

	/**
	 * Translates text values of URI and regex to target language
	 *
	 * @param   string   $target        target Language
	 * @param   boolean  $update_regex  update regex as well?
	 * @return  void
	 * @uses    I18n::$source
	 * @uses    Route::map
	 */
	protected function translate_route($target, $update_regex = TRUE)
	{
		if ( ! isset($this->_uri_source))
		{
			// Set the source URI and regex
			$this->_uri_source   = $this->_uri;
			$this->_regex_source = $this->_regex;
		}

		// Check if translation is needed
		if ($target !== Lang::shortcode(I18n::$source))
		{
			// Set URI and regex
			$uri   = $this->_uri_source;
			$regex = $this->_regex_source;

			foreach ($this->_translate as $label => $translate)
			{
				// Check if the label is a route parameter
				if ($label[0] !== '<')
				{
					// Create a regex pattern for the label
					$pattern = '/(?<!\<)'.$label.'/';

					// Translate the label
					$translation = Route::map($label, $target);

					// Check if label if different from replacement
					if ($translation !== $label)
					{
						// Add translation to URI
						$uri = preg_replace($pattern, $translation, $uri);

						if ($update_regex AND is_array($regex))
						{
							// Add translation to regex if needed
							$regex = preg_replace($pattern, $translation, $regex);
						}
					}
				}
			}

			// Set the URI and regex to the translated versions
			$this->_uri   = $uri;
			$this->_regex = $regex;
		}
		else
		{
			// No translation needed as the target language is the source language
			$this->_uri   = $this->_uri_source;
			$this->_regex = $this->_regex_source;
		}
	}

	/**
	 * Saves or loads the route cache per language based on the current setting
	 * of I18n::lang. If your routes will remain the same for a long period
	 * of time, use this to reload the routes from the cache rather than
	 * redefining them on every page load.
	 *
	 *     if ( ! Route::cache())
	 *     {
	 *         // Set routes here
	 *         Route::cache(TRUE);
	 *     }
	 *
	 * @param   boolean $save   cache the current routes
	 * @return  void    when saving routes
	 * @return  boolean when loading routes
	 * @uses    I18n::$lang
	 * @uses    Kohana::cache
	 * @uses    Route::$_routes
	 * @uses    Kohana_Exception
	 * @uses    Route::$cache
	 */
	public static function cache($save = FALSE, $append = FALSE)
	{
		// Set route cache key
		$key = 'Route::cache('.I18n::$lang.')';

		if ($save === TRUE)
		{
			try
			{
				// Cache all defined routes
				Kohana::cache($key, Route::$_routes);
			}
			catch (Exception $e)
			{
				// We most likely have a lambda in a route, which cannot be cached
				throw new Kohana_Exception('One or more routes could not be cached (:message)', array(
					':message' => $e->getMessage(),
				));
			}
		}
		else
		{
			if ($routes = Kohana::cache($key))
			{
				if ($append)
				{
					// Append cached routes
					Route::$_routes += $routes;
				}
				else
				{
					// Replace existing routes
					Route::$_routes = $routes;
				}

				// Routes were cached
				return Route::$cache = TRUE;
			}
			else
			{
				// Routes were not cached
				return Route::$cache = FALSE;
			}
		}
	}

	/**
	 * Extension of Route::url that adds a fourth parameter for setting a
	 * language key and translates the URI to the requested language
	 *
	 * The current language (I18n::$lang) is used by default.
	 *
	 *     echo Route::url('default', array('controller' => 'foo', 'action' => 'bar'), NULL, 'fr');   // custom language
	 *     echo Route::url('default', array('controller' => 'foo', 'action' => 'bar'));               // current language
	 *
	 * @param   string  $name      route name
	 * @param   array   $params    URI parameters
	 * @param   mixed   $protocol  protocol string or boolean, adds protocol and domain
	 * @param   mixed   $lang      The target language
	 * @return  string
	 * @since   3.0.7
	 * @uses    Route::get
	 * @uses    URL::site
	 */
	public static function url($name, array $params = NULL, $protocol = NULL, $lang = NULL)
	{
		$route = Route::get($name);

		// Create a URI with the route and convert it to a URL
		if ($route->is_external())
			return Route::get($name)->uri($params, $lang);
		else
			return URL::site(Route::get($name)->uri($params, $lang), $protocol);
	}

	/**
	 * Tests if the route matches a given URI. A successful match will return
	 * all of the routed parameters (in English!) as an array. A failed match
	 * will return boolean FALSE.
	 *
	 * @return  array               on success
	 * @return  FALSE               on failure
	 * @uses    Lang::$i18n_routes
	 * @uses    Route::matches
	 * @uses    Route::remap
	 * @uses    Request::$lang
	 * @uses    Lang::$default
	 * @uses    HTTP_Exception_404
	 */
	public function matches(Request $request)
	{
		// Get the URI from the Request
		$uri = trim($request->uri(), '/');

		if ( ! Lang::$i18n_routes)
		{
			// i18n routes are off
			return parent::matches($request);
		}

		// Set params
		$params = parent::matches($request);

		if ($params !== FALSE)
		{
			foreach ($params as $label => &$param)
			{
				if (isset($this->_translate['<'.$label.'>']) OR isset($this->_translate[$param]))
				{
					// If param might be translated see if it needs to be
					// converted back to application (source) language
					$source_param = Route::remap($param);

					if (Request::$lang !== Lang::$default AND isset($this->_translate['<'.$label.'>']) AND $source_param === $param AND $param !== $this->_defaults[$label])
					{
						// To avoid duplicate content throw 404
						throw new HTTP_Exception_404('The requested URL :uri was not found on this server.', array(
							':uri' => $uri
						));
					}

					// Set translated param
					$param = $source_param;
				}
			}

			// Return URI converted back to application (source) language
			return $params;
		}
		else
		{
			// No match
			return FALSE;
		}
	}

	/**
	 * Generates a translated URI for the current route based on the parameters given
	 *
	 * @param   array               $params  parameters for URI
	 * @param   string              $lang    target language, defaults to I18n::$lang
	 * @return  string                       URI in target language
	 * @uses    Lang::$i18n_routes
	 * @uses    Route::uri
	 * @uses    I18n::$lang
	 * @uses    Route::map
	 */
	public function uri(array $params = NULL, $lang = NULL)
	{
		// Forced language
		$forced_language = ($lang !== NULL);

		if ($lang === NULL)
		{
			// Set target language to current language
			$lang = Lang::shortcode(I18n::$lang);
		}

		if ( ! Lang::$i18n_routes)
		{
			// i18n routes are off, build URI
			$uri = parent::uri($params);

			if (Lang::$default_prepended OR (Request::$lang !== Lang::$default AND $lang !== Lang::$default) OR ($forced_language AND $lang !== Lang::$default))
			{
				// Prepend the target language to the URI if needed
				$uri = $lang.'/'.ltrim($uri, '/');
			}

			// Return URI with or without language
			return $uri;
		}

		// Make sure text values are in correct language
		$this->translate_route($lang, FALSE);

		if ($params !== NULL)
		{
			// Translation required
			foreach ($params as $label => &$param)
			{
				if (isset($this->_translate['<'.$label.'>']) OR isset($this->_translate[$param]))
				{
					// Translate param
					$param = Route::map($param, $lang);
				}
			}
		}

		// Build URI
		$uri = parent::uri($params);

		if (Lang::$default_prepended OR (Request::$lang !== Lang::$default AND $lang !== Lang::$default) OR ($forced_language AND $lang !== Lang::$default))
		{
			// Prepend the target language to the URI if needed
			$uri = $lang.'/'.ltrim($uri, '/');
		}

		return $uri;
	}

} // End Route
