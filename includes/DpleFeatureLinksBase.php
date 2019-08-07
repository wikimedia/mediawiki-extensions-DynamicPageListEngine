<?php

/**
 * @brief Class DpleFeatureLinksBase.
 *
 * @file
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 *
 * @author [RV1971](https://www.mediawiki.org/wiki/User:RV1971)
 */

/**
 * @brief Base class for selection by links.
 *
 * Recognizes two parameters specified when invoking the constructor,
 * which may be strings or arrays.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleFeatureLinksBase extends DpleFeatureBase {
	/* == private variables == */

	private $paramName_; ///< Name of the parameter to recognize.

	private $tableName_; ///< Name of the table to join.

	private $tableAlias_; ///< Table alias to use in the query.

	/// Column to use for exclusion condition in outer join.
	private $tableColumn_;

	private $joinConds_;///< Array of join conditions.

	/// Array of Title objects related to the pages to select.
	private $linkedTitles_;

	private $linkedCount_ = 0; ///< count( $linkedTitles_ ).

	/// Array of Title objects related to the pages to exclude.
	private $notLinkedTitles_;

	private $notLinkedCount_ = 0; ///< count( $notLinkedCount_ ).

	/* == magic methods == */

	/**
	 * @brief Constructor. Evaluate parameters.
	 *
	 * @param array $params Array of parameters.
	 *
	 * @param array $features Array of feature objects constructed so
	 * far.
	 *
	 * @param string $paramName Name of the parameter to
	 * recognize. The parameter "not$paramName" will be recognized as
	 * well.
	 *
	 * @param int $ns Default namespace for linked pages.
	 *
	 * @param string $tableName Name of the table to join.
	 *
	 * @param string $tableAlias Table alias to use in the query.
	 *
	 * @param string $tableColumn Column to use for exclusion
	 * condition in outer join.
	 *
	 * @param array $joinConds Join conditions.
	 */
	public function __construct( array $params, array &$features,
		$paramName, $ns,
		$tableName, $tableAlias, $tableColumn, $joinConds ) {
		parent::__construct( $features );

		$this->paramName_ = $paramName;
		$this->tableName_ = $tableName;
		$this->tableAlias_ = $tableAlias;
		$this->tableColumn_ = $tableColumn;
		$this->joinConds_ = $joinConds;

		if ( isset( $params[$this->paramName_] ) ) {
			$this->linkedTitles_ =
				$this->arrayToTitles( $params[$this->paramName_], $ns );

			$this->linkedCount_ = count( $this->linkedTitles_ );
		}

		if ( isset( $params["not$this->paramName_"] ) ) {
			$this->notLinkedTitles_ =
				$this->arrayToTitles( $params["not$this->paramName_"], $ns );

			$this->notLinkedCount_ = count( $this->notLinkedTitles_ );
		}
	}

	/* == accessors == */

	/// Get @ref $paramName_.
	public function getParamName() {
		return $this->paramName_;
	}

	/// Get @ref $tableName_.
	public function getTableName() {
		return $this->tableName_;
	}

	/// Get @ref $tableAlias_.
	public function getTableAlias() {
		return $this->tableAlias_;
	}

	/// Get @ref $tableColumn_.
	public function getTableColumn() {
		return $this->tableColumn_;
	}

	/// Get @ref $linkedTitles_.
	public function getLinkedTitles() {
		return $this->linkedTitles_;
	}

	/// Get @ref $linkedCount_.
	public function getLinkedCount() {
		return $this->linkedCount_;
	}

	/// Get @ref $notLinkedTitles_.
	public function getNotLinkedTitles() {
		return $this->notLinkedTitles_;
	}

	/// Get @ref $notLinkedCount_.
	public function getNotLinkedCount() {
		return $this->notLinkedCount_;
	}

	/// Get the database cost generated by this feature instance.
	public function getCost() {
		return ($this->linkedCount_ + $this->notLinkedCount_)
			* parent::getCost();
	}

	/* == operations == */

	/**
	 * @brief Modify a given query.
	 *
	 * Add table aliases `{$tableAlias_}1, {$tableAlias_}2, ...` to
	 * DpleQuery::$tables_.
	 *
	 * @param DpleQuery $query Query object.
	 */
	public function modifyQuery( DpleQuery &$query ) {
		$dbr = $query->getDbr();
		$tableName = $dbr->tableName( $this->tableName_ );
		$n = 1;

		/** Add conditions based on @ref $linkedTitles_. */
		for ( $i = 0; $i < $this->linkedCount_; $i++ ) {
			$table = "$tableName AS {$this->tableAlias_}$n";

			$query->addTables( $table );

			$query->addJoinCond( $table, 'INNER JOIN',
				$this->transformJoinConds_( $dbr, $n,
					$this->linkedTitles_[$i] ) );

			$n++;
		}

		/** Add conditions based on @ref $notLinkedTitles_. */
		for ( $i = 0; $i < $this->notLinkedCount_; $i++ ) {
			$table = "$tableName AS {$this->tableAlias_}$n";

			$query->addTables( $table );

			$query->addJoinCond( $table, 'LEFT OUTER JOIN',
				$this->transformJoinConds_( $dbr, $n,
					$this->notLinkedTitles_[$i] ) );

			$query->addConds(
				[ "{$this->tableAlias_}{$n}.{$this->tableColumn_}" => null ] );
			$n++;
		}
	}

	/* == private methods == */

	/**
	 * @brief Replace strings in an array of join conditions.
	 *
	 * @param DatabaseBase $dbr Database object.
	 *
	 * @param int $n Sequential number of joined table.
	 *
	 * @param Title $title Title of page to join with.
	 */
	private function transformJoinConds_( $dbr, $n, Title $title ) {
		/** Apply the following replacements to the join condition
		 *	strings:
		 * - `$table` => joined table alias
		 * - `$id` => article ID of the title to join
		 * - `$ns` => namespace index of the title to join
		 * - `$dbkey` => DB key of the title to join
		 */
		$replace = [
			'$table' => "{$this->tableAlias_}$n",
			'$id' => $title->getArticleID(),
			'$ns' => $title->getNamespace(),
			'$dbkey' => $dbr->addQuotes( $title->getDBKey() ) ];

		return str_replace( array_keys( $replace ), array_values( $replace ),
			$this->joinConds_ );
	}
}
