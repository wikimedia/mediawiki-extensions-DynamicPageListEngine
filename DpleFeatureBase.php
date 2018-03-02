<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * @brief Class DpleFeature.
 *
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](http://www.mediawiki.org/wiki/User:RV1971)
 *
 */

/**
 * @brief Base class from which feature classes should be derived.
 *
 * Provides the managemet of @ref $features_ and a number of methods
 * used in several features.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureBase {
	/* == private variables == */

	/**
	 * @brief Array of feature objects constructed so far.
	 *
	 * After construction of all feature objects, this reference makes
	 * available to each feature object the complete list of features
	 * used for the present dynamic page list.
	 */
	private $features_;

	/* == magic methods == */

	/**
	 * @brief Constructor.
	 *
	 * @param array $features Array of feature objects constructed so
	 * far.
	 */
	public function __construct( array &$features ) {
		/** Save the reference to the features array in $features_,
		 *	thus allowing a feature to access other features, once all
		 *	features have been constructed. */
		$this->features_ = &$features;
	}

	/// Get @ref $features_.
	public function &getFeatures() {
		return $this->features_;
	}

	/**
	 * @brief Get a specific feature.
	 *
	 * @param string $class The feature class.
	 *
	 * @return A reference to the feature object, if there is one,
	 * else NULL.
	 */
	public function &getFeature( $class ) {
		static $unset;

		if ( isset( $this->features_[$class] ) ) {
			return $this->features_[$class];
		} else {
			return $unset;
		}
	}

	/**
	 * @brief Get the database cost generated by this feature
	 * instance.
	 *
	 * A unit of 1 should correspond to an efficient join with another
	 * table.
	 */
	public function getCost() {
		global $wgDpleCondCostMap;

		/** The default implementation returns the value from @ref
		 *	$wgDpleCondCostMap for the present class,
		 *	or 0 if there is no entry. Most features potentially
		 *	generating a nonzero cost will override this, and in most
		 *	cases use this in its computation. */
		return isset( $wgDpleCondCostMap[get_class( $this )] )
			? $wgDpleCondCostMap[get_class( $this )] : 0;
	}

	/* == parameter parsing and related methods == */

	/**
	 * @brief Transform an array of pages to an array of Title objects.
	 *
	 * @param null|string|array $pages Page title string or array thereof.
	 *
	 * @param int $ns Default namespace to use for pages which do not
	 * specify one.
	 *
	 * @return array Possibly empty array of Title objects.
	 */
	public function arrayToTitles( $pages, $ns ) {
		$titles = [];

		foreach ( (array)$pages as $page ) {
			/** When processing title strings, decode html entities (such as
			 *	&amp;. */
			$title = Title::makeTitleSafe( $ns, html_entity_decode( $page ) );

			/** Silently ignore pages that do not exist. */
			if( isset( $title ) ) {
				$titles[] = $title;
			}
		}

		return $titles;
	}

	/**
	 * @brief Parse an `include|exclude|only` parameter.
	 *
	 * @param null|string $param Parameter value.
	 *
	 * @param string $default Default value.
	 *
	 * @return string One of `include|exclude|only`. $default if
	 * $param is null or invalid.
	 */
	public function parseIncludeExclude( $param,
		$default = 'exclude' ) {
		switch ( $param ) {
			case 'exclude':
			case 'include':
			case 'only':
				return $param;

			default:
				return $default;
		}
	}

	/**
	 * @brief Parse a namespace specification.
	 *
	 * @param int|string $param Namespace name or namespace index.
	 *
	 * @return int Namespace index.
	 */
	public function parseNamespace( $param ) {
		if( is_numeric( $param ) ) {
			/** A numeric parameter (including a numeric literal) is
			 *	interpreted as a namespace index. */
			$index = intval( $param );

			if( !MWNamespace::exists( $index ) ) {
				throw new Scribunto_LuaError( wfMessage(
						'dple-error-invalid-ns-index', $index )->text() );
			}

			return $index;
		}

		global $wgContLang;

		$index = $wgContLang->getNsIndex( $param );

		if( $index === false ) {
			return 0;
		}

		return $index;
	}

	/**
	 * @brief Parse an arbitrary text, returning NULL for empty
	 * strings.
	 *
	 * @param string|null $param Text.
	 *
	 * @return string|null Value or NULL.
	 */
	public function parseText( $param ) {
		if ( isset( $param ) && $param != '' ) {
			return $param;
		}
	}

	/**
	 * @brief Parse a substring of a title, transforming it to a
	 * substring of a DB key.
	 *
	 * Empty strings are interpreted as NULL.
	 *
	 * @param string|null $param Substring.
	 *
	 * @return string|null Value with spaces replaced by underscores.
	 */
	public function parseTitleSubstring( $param ) {
		if ( isset( $param ) && $param != '' ) {
			return strtr( $param, ' ', '_' );
		}
	}

	/**
	 * @brief Parse a user specification.
	 *
	 * Do not check if the user actually exists.
	 *
	 * @param int|string $param User name or user id.
	 *
	 * @return User User object.
	 */
	public function parseUser( $param ) {
		/** A numeric parameter (including a numeric literal) is
		 *	interpreted as a user index. */
		if( is_numeric( $param ) ) {
			return User::newFromId( intval( $param ) );
		}

		return User::newFromName( $param );
	}

	/* == operations == */

	/**
	 * @brief Modify a query.
	 *
	 * @param DpleQuery $query Query object.
	 */
	public function modifyQuery( DpleQuery &$query ) {
	}

	/**
	 * @brief Return the names of the result converters defined in
	 * this class.
	 *
	 * @return array Array of the names of all methods that can be
	 * used to convert results. Each such method should get one
	 * parameter of type ResultWrapper and return the result of the
	 * conversion.
	 */
	public function getResultConverters() {
		return [];
	}
}
