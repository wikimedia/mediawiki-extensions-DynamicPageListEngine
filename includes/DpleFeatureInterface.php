<?php

/**
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 */

/**
 * @brief Interface that all features must implement.
 *
 * Basically, a feature is an object that takes parameters (upon
 * construction) and modifies a DpleQuery object
 * accordingly.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
interface DpleFeatureInterface {
	/**
	 * @param array $params Array of parameters.
	 *
	 * @param array &$features Array of feature objects constructed so
	 * far.
	 */
	public function __construct( array $params, array &$features );

	// Get the database cost generated by this feature instance.
	public function getCost();

	/**
	 * @brief Modify a given query.
	 *
	 * @param DpleQuery &$query Query object.
	 */
	public function modifyQuery( DpleQuery &$query );

	/**
	 * @brief Return the names of the result converters defined in a
	 * class.
	 *
	 * @return array Array of the names of all methods that can be
	 * used to convert results. Each such method should get one
	 * parameter of type ResultWrapper and return the result of the
	 * conversion.
	 */
	public function getResultConverters();
}
