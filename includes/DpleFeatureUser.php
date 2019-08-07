<?php

/**
 * @brief Class DpleFeatureUser.
 *
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 */

/**
 * @brief Selection by modifying user.
 *
 * Recognizes the parameters `createdby, notcreatedby, modifiedby,
 * notmodifiedby, lastmodifiedby` and `notlastmodifiedby`, each of which
 * may be a title substring or an array thereof.
 *
 * If `createdby, modifiedby, lastmodifiedby` is an array, the result
 * is (obviously) the union of the record sets satisfying its
 * elements, unlike other parameters (including `notcreatedby`) where
 * the result is the intersection.
 *
 * If the parameters contradict each other (e.g. the same user is
 * nominated in `createdby` and in `notcreatedby`), the results are
 * undefined.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @todo Implement [not]majormodifiedby to select pages that have [no]
 * major edits from specified users.
 */
class DpleFeatureUser extends DpleFeatureBase
implements DpleFeatureInterface {
	/* == private variables == */

	/** @brief Array of User objects for users that should have
	 *	created the pages to select.*/
	private $createdby_;

	/** @brief Array of User objects for users that should not have
	 *	created the pages to select.*/
	private $notCreatedby_;

	/** @brief Array of User objects for users that should have
	 *	modified the pages to select.*/
	private $modifiedby_;

	/** @brief Array of User objects for users that should not have
	 *	modified the pages to select.*/
	private $notModifiedby_;

	/** @brief Array of User objects for users that should be the
	 *	users that have modified the pages to select.*/
	private $lastmodifiedby_;

	/** @brief Array of User objects for users that should not be the
	 *	users that have modified the pages to select.*/
	private $notLastmodifiedby_;


	/* == magic methods == */

	/// Constructor. Evaluate parameters.
	public function __construct( array $params, array &$features ) {
		parent::__construct( $features );

		foreach (
			[
				'createdby' => 'createdby_',
				'notcreatedby' => 'notCreatedby_',
				'modifiedby' => 'modifiedby_',
				'notmodifiedby' => 'notModifiedby_',
				'lastmodifiedby' => 'lastmodifiedby_',
				'notlastmodifiedby' => 'notLastmodifiedby_'
				] as $key => $member ) {

			if ( isset( $params[$key] ) ) {
				$this->$member =
					array_map( array( $this, 'parseUser' ),
							   (array)$params[$key] );
			}
		}
	}

	/* == accessors == */

	/// Get @ref $createdby_.
	public function getCreatedby() {
		return $this->createdby_;
	}

	/// Get @ref $notCreatedby_.
	public function getNotCreatedby() {
		return $this->notCreatedby_;
	}

	/// Get @ref $modifiedby_.
	public function getModifiedby() {
		return $this->modifiedby_;
	}

	/// Get @ref $notModifiedby_.
	public function getNotModifiedby() {
		return $this->notModifiedby_;
	}

	/// Get @ref $lastmodifiedby_.
	public function getLastmodifiedby() {
		return $this->lastmodifiedby_;
	}

	/// Get @ref $notLastmodifiedby_.
	public function getNotLastmodifiedby() {
		return $this->notLastmodifiedby_;
	}

	/// Get the database cost generated by this feature instance.
	public function getCost() {
		global $wgDpleCondCostMap;

		/** When selecting by `[not]modifiedby`, the record sets
		 * selected from the joined tables may be very large, unlike
		 * selection by `lastmodifiedby`. Therefore, the cost per
		 * condition for this is stored in an extra entry with an
		 * "Expensive" appended to the key.
		 */
		$highCostKey = get_class( $this ) . 'Expensive';

		$highCost = isset( $wgDpleCondCostMap[$highCostKey] )
			? $wgDpleCondCostMap[$highCostKey]
			: parent::getCost();

		return ((int)(bool)$this->createdby_
			+ (int)(bool)$this->notCreatedby_
			+ (int)(bool)$this->lastmodifiedby_
			+ (int)(bool)$this->notLastmodifiedby_) * parent::getCost()
			+ ((int)(bool)$this->modifiedby_
				+ (int)(bool)$this->notModifiedby_) * $highCost;
	}

	/* == operations == */

	/// Modify a given query. @copydetails DpleFeatureBase::modifyQuery()
	public function modifyQuery( DpleQuery &$query ) {
		$dbr = $query->getDbr();
		$tableName = $dbr->tableName( 'revision' );

		/** Add condition based on @ref $createdby_. */
		if ( $this->createdby_ ) {
			$table = "$tableName AS rev_c";

			$query->addTables( $table );

			$cond = $this->buildIn( $this->createdby_ );

			$query->addJoinCond(
				$table, 'INNER JOIN',
				[ 'page_id = rev_c.rev_page',
				  'rev_c.rev_parent_id = 0' ] );

			$query->addConds( "rev_c.rev_user $cond" );
		}

		/** Add condition based on @ref $notCreatedby_. */
		if ( $this->notCreatedby_ ) {
			$table = "$tableName AS rev_nc";

			$query->addTables( $table );

			$cond = 'NOT' . $this->buildIn( $this->notCreatedby_ );

			$query->addJoinCond(
				$table, 'INNER JOIN',
				[ 'page_id = rev_nc.rev_page',
				  'rev_nc.rev_parent_id = 0' ] );

			$query->addConds( "rev_nc.rev_user $cond" );
		}

		/** Add condition based on @ref $lastmodifiedby_. */
		if ( $this->lastmodifiedby_ ) {
			$table = "$tableName AS rev_l";

			$query->addTables( $table );

			$cond = $this->buildIn( $this->lastmodifiedby_ );

			$query->addJoinCond(
				$table, 'INNER JOIN',
				[ 'page_latest = rev_l.rev_id' ] );

			$query->addConds( "rev_l.rev_user $cond" );
		}

		/** Add condition based on @ref $notLastmodifiedby_. */
		if ( $this->notLastmodifiedby_ ) {
			$table = "$tableName AS rev_nl";

			$query->addTables( $table );

			$cond = 'NOT' . $this->buildIn( $this->notLastmodifiedby_ );

			$query->addJoinCond(
				$table, 'INNER JOIN',
				[ 'page_latest = rev_nl.rev_id' ] );

			$query->addConds( "rev_nl.rev_user $cond" );
		}

		/** Add condition based on @ref $modifiedby_. */
		if ( $this->modifiedby_ ) {
			$table = "$tableName AS rev_m";

			$query->addTables( $table );

			$cond = $this->buildIn( $this->modifiedby_ );

			$query->addJoinCond(
				$table, 'INNER JOIN',
				[ 'page_id = rev_m.rev_page' ] );

			$query->addConds( "rev_m.rev_user $cond" );

			$query->setOption( 'DISTINCT' );
		}

		/** Add condition based on @ref $notModifiedby_. */
		if ( $this->notModifiedby_ ) {
			$table = "$tableName AS rev_nm";

			$query->addTables( $table );

			$cond = $this->buildIn( $this->notModifiedby_ );

			$query->addJoinCond(
				$table, 'LEFT OUTER JOIN',
				[ 'page_id = rev_nm.rev_page',
				  "rev_nm.rev_user $cond" ] );

			$query->addConds( [ 'rev_nm.rev_page' => null ] );

			$query->setOption( 'DISTINCT' );
		}
	}

	/**
	 * @brief Build an IN expression for user IDs.
	 *
	 * @param array $users Array of User objects.
	 *
	 * @return string ' IN (...)' where ... is a list of user IDs.
	 */
	public function buildIn( array $users ) {
		$list = [];

		foreach ( $users as $user ) {
			$list[] = $user->getId();
		}

		return ' IN (' . implode( ',', $list ) . ')';
	}
}
