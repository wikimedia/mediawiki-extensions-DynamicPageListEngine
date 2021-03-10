<?php

/**
 * @brief Class DpleFeatureOrder.
 *
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 *
 */

/**
 * @brief Sort the result records.
 *
 * Recognizes the parameters `order` (default `descending`) and
 * `ordermethod` (default `categoryadd`). Some values are replaced by
 * fallback values if no category was indicated, see
 * parseOrdermethod(). Defaults and fallback values are chosen for
 * compatibility with
 * [Extension:DynamicPageList](https://www.mediawiki.org/wiki/Extension:DynamicPageList_(Wikimedia%29).
 *
 * In addition to the `ordermethod` values recognized by
 * Extension:DynamicPageList, the value `title` is accepted as
 * well. It sorts by title without namespace prefix.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureOrder extends DpleFeatureBase
implements DpleFeatureInterface {
	/* == private variables == */

	private $order_; // < ascending|descending.
	private $ordermethod_; // < Sorting criteria.
	private $sqlOrder_; // < ASC|DESC.

	/* == magic methods == */

	// Constructor. Evaluate parameters.
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );

		$this->order_ = $this->parseOrder( isset( $params['order'] )
			? $params['order'] : null );

		/** The interpretation of the `ordermethod` parameter depends
		 * on other features; therefore, this parameter is just stored
		 * in @ref $ordermethod_, and invocation of parseOrdermethod()
		 * is deferred to modifyQuery(), when all features have been
		 * constructed.
		 */
		$this->ordermethod_ = isset( $params['ordermethod'] )
			? $params['ordermethod'] : null;

		$this->sqlOrder_ = ( $this->order_ == 'descending' ) ? 'DESC' : 'ASC';
	}

	/* == accessors == */

	// Get @ref $order_.
	public function getOrder() {
		return $this->order_;
	}

	// Get @ref $ordermethod_.
	public function getOrdermethod() {
		return $this->ordermethod_;
	}

	// Get @ref $sqlOrder_.
	public function getSqlOrder() {
		return $this->sqlOrder_;
	}

	/**
	 * @brief Get the database cost generated by this feature
	 * instance.
	 *
	 * If the method is not mentioned in @ref $wgDpleOrderCostMap,
	 * return 0.
	 */
	public function getCost() {
		global $wgDpleOrderCostMap;

		return isset(
			$wgDpleOrderCostMap[$this->ordermethod_] )
			? $wgDpleOrderCostMap[$this->ordermethod_]
			: 0;
	}

	/* == operations == */

	/**
	 * @brief Parse the `order` parameter.
	 *
	 * @param null|string $param Parameter value.
	 *
	 * @return string `ascending|descending`.
	 */
	public function parseOrder( $param ) {
		switch ( $param ) {
			case 'ascending':
			case 'descending':
				return $param;

				/** Default is `descending`. */
			default:
				return 'descending';
		}
	}

	/**
	 * @brief Parse the `ordermethod` parameter.
	 *
	 * @param null|string $param Parameter value.
	 *
	 * @return string Order method.
	 */
	public function parseOrdermethod( $param ) {
		$categoryFeature = $this->getFeature( 'DpleFeatureCategory' );

		$hasCategories = isset( $categoryFeature )
			&& $categoryFeature->getLinkedCount();

		$containsFeature = $this->getFeature( 'DpleFeatureContains' );

		$hasContains = isset( $containsFeature )
			&& $containsFeature->getLinkedCount();

		switch ( $param ) {
			case 'created':
			case 'lastedit':
			case 'length':
			case 'title':
				return $param;

			case 'categoryadd':
				/** If no categories are specified, replace
				 *	`categoryadd` with `created`.
				 */
				return $hasCategories ? 'categoryadd' : 'created';

			case 'categoryaddx':
				/** If no categories are specified, replace
				 *	`categoryaddx` with `created`.
				 */
				return $hasContains ? 'categoryaddx' : 'created';

			case 'categorysortkey':
			case 'sortkey':
				/** If no categories are specified, replace
				 *	`categorysortkey` with `created`.
				 */
				return $hasCategories ? 'categorysortkey' : 'created';

			case 'categorysortkeyx':
			case 'sortkeyx':
				/** If no categories are specified, replace
				 *	`categorysortkeyx` with `created`.
				 */
				return $hasContains ? 'categorysortkeyx' : 'created';

			case 'popularity':
				global $wgDisableCounters;

				if ( !$wgDisableCounters ) {
					return $param;
				} else {
					/** If `popularity` was requested but hit counters
					 *	are disabled, invoke parseOrdermethod() again
					 *	with parameter `categoryadd`.
					 */
					return $this->parseOrdermethod( 'categoryadd' );
				}

				/** Default: invoke parseOrdermethod() again with
				 *	parameter `categoryadd`.
				 */
			default:
				return $this->parseOrdermethod( 'categoryadd' );
		}
	}

	// Modify a given query. @copydetails DpleFeatureBase::modifyQuery()
	public function modifyQuery( DpleQuery &$query ) {
		/** Call parseOrdermethod(). */
		$this->ordermethod_ = $this->parseOrdermethod( $this->ordermethod_ );

		/** Set the ORDER BY clause based on $ordermethod_. */
		switch ( $this->ordermethod_ ) {
			case 'lastedit':
				$sqlSort = 'page_touched';
				break;

			case 'length':
				$sqlSort = 'page_len';
				break;

			case 'created':
				$sqlSort = 'page_id'; // Since they're never reused
									  // and increasing
				break;

			case 'categorysortkey':
				$sqlSort = "cl1.cl_type {$this->sqlOrder_}, cl1.cl_sortkey";
				break;

			case 'categorysortkeyx':
				$sqlSort = "clx1.cl_type {$this->sqlOrder_}, clx1.cl_sortkey";
				break;

			case 'popularity':
				$sqlSort = 'page_counter';
				break;

			case 'title':
				$sqlSort =
					$query->getDbr()->strreplace( 'page_title',
						"'_'", "' '" );
				break;

			case 'categoryadd':
				$sqlSort = 'cl1.cl_timestamp';
				break;

			case 'categoryaddx':
				$sqlSort = 'clx1.cl_timestamp';
				break;
		}

		$query->setOption( 'ORDER BY', "$sqlSort $this->sqlOrder_" );
	}
}
