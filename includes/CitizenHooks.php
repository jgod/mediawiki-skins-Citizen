<?php
/**
 * Citizen - A responsive skin developed for the Star Citizen Wiki
 *
 * This file is part of Citizen.
 *
 * Citizen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Citizen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Citizen.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @file
 */

namespace Citizen;

use ConfigException;
use Exception;
use MediaWiki\MediaWikiServices;
use RequestContext;
use ThumbnailImage;

/**
 * Hook handlers for Citizen skin.
 *
 * Hook handler method names should be in the form of:
 *    on<HookName>()
 */
class CitizenHooks {
	/**
	 * ResourceLoaderGetConfigVars hook handler for setting a config variable
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 *
	 * @param array &$vars Array of variables to be added into the output of the startup module.
	 * @return bool
	 */
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		try {
			$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'Citizen' );
		} catch ( Exception $e ) {
			return false;
		}

		try {
			$vars['wgCitizenSearchDescriptionSource'] = $config->get( 'CitizenSearchDescriptionSource' );
		} catch ( ConfigException $e ) {
			// Should not happen
			$vars['wgCitizenSearchDescriptionSource'] = 'textextracts';
		}

		try {
			$vars['wgCitizenMaxSearchResults'] = $config->get( 'CitizenMaxSearchResults' );
		} catch ( ConfigException $e ) {
			// Should not happen
			$vars['wgCitizenMaxSearchResults'] = 6;
		}

		return true;
	}

	/**
	 * Lazyload images
	 * Modified from the Lazyload extension
	 * Looks for thumbnail and swap src to data-src
	 *
	 * @param ThumbnailImage $thumb
	 * @param array &$attribs
	 * @param array &$linkAttribs
	 * @return bool
	 */
	public static function onThumbnailBeforeProduceHTML( $thumb, &$attribs, &$linkAttribs ) {
		try {
			$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'Citizen' );
			$lazyloadEnabled = $config->get( 'CitizenEnableLazyload' );
			$thumbSize = $config->get( 'CitizenThumbnailSize' );
		} catch ( ConfigException $e ) {
			wfLogWarning( sprintf( 'Could not get config for "$wgThumbnailSize". Defaulting to "10". %s',
				$e->getMessage() ) );
			$lazyloadEnabled = false;
			$thumbSize = 10;
		}

		// Replace thumbnail if lazyload is enabled
		if ( $lazyloadEnabled === true ) {
			$file = $thumb->getFile();

			if ( $file !== null ) {
				$request = RequestContext::getMain()->getRequest();

				if ( defined( 'MW_API' ) && $request->getVal( 'action' ) === 'parse' ) {
					return true;
				}

				// Set lazy class for the img
				if ( isset( $attribs['class'] ) ) {
					$attribs['class'] .= ' lazy';
				} else {
					$attribs['class'] = 'lazy';
				}

				// Native API
				$attribs['loading'] = 'lazy';

				$attribs['data-src'] = $attribs['src'];
				$attribs['data-width'] = $attribs['width'];
				$attribs['data-height'] = $attribs['height'];

				// Replace src with small size image
				$attribs['src'] =
					preg_replace( '#/\d+px-#', sprintf( '/%upx-', $thumbSize ), $attribs['src'] );

				// So that the 10px thumbnail is enlarged to the right size
				$attribs['width'] = $attribs['data-width'];
				$attribs['height'] = $attribs['data-height'];

				// Clean up
				unset( $attribs['data-width'], $attribs['data-height'] );

				if ( isset( $attribs['srcset'] ) ) {
					$attribs['data-srcset'] = $attribs['srcset'];
					unset( $attribs['srcset'] );
				}
			}
		}

		return true;
	}
}
