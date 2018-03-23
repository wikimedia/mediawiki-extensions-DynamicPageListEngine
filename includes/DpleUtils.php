<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * @brief Class DpleUtils.
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
 * @brief Utility functions.
 *
 * @ingroup Extensions
 * @ingroup Extensions-DynamicPageListEngine
 */
class DpleUtils {
	/// Convert a Title object to an array of properties
	public static function title2array( Title $title ) {
		/** Extract all those Title properties which are cheap
		 * (i.e. do not require database access):
		 * - namespace
		 * - nsText
		 * - text
		 * - prefixedText
		 * - baseText
		 * - subpageText
		 * - canTalk
		 * - isContentPage
		 * - isSubpage
		 * - isTalkPage
		 * - isRedirect
		 */
		return [
			'id' => $title->getArticleId(),
			'namespace' => $title->getNamespace(),
			'nsText' => $title->getNsText(),
			'text' => $title->getText(),
			'prefixedText' => $title->getPrefixedText(),
			'baseText' => $title->getBaseText(),
			'subpageText' => $title->getSubpageText(),
			'canTalk' => $title->canTalk(),
			'isContentPage' => $title->isContentPage(),
			'isRedirect' => $title->isRedirect(),
			'isSubpage' => $title->isSubpage(),
			'isTalkPage' => $title->isTalkPage()
			];
	}

	/**
	 * @brief Get the target of a redirect page.
	 *
	 * @param Title|null $title Title of (potential) redirect page.
	 *
	 * @return NULL if $title is not a redirect or the target cannot be
	 * found. Otherwise an array of properties of the target page.
	 */
	public static function resolveRedirect( $title ) {
		if ( !isset( $title ) || !$title->isRedirect() ) {
			return;
		}

		$title = WikiPage::factory( $title )->getContent()
			->getRedirectTarget();

		if ( !isset( $title ) ) {
			return;
		}

		return static::title2array( $title );
	}
}
