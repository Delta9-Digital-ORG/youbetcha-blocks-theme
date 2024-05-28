import React from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
import { props } from '@eightshift/frontend-libs/scripts';
import { ImageOptions } from '../../../components/image/components/image-options';
import { SocialNetworksOptions } from '../../../components/social-networks/components/social-networks-options';

export const SiteFooterOptions = ({ attributes, setAttributes }) => {
	return (
		<PanelBody title={__('Site navigation', 'you-betcha-cannabis-theme')}>
			<ImageOptions
				{...props('logo', attributes, { setAttributes })}
				label={__('Logo', 'you-betcha-cannabis-theme')}
				hideRoundedCornersToggle
				hideFullSizeToggle
				reducedBottomSpacing
			/>

			<SocialNetworksOptions
				{...props('socialNetworks', attributes, { setAttributes })}
				hideModeSelector
				noBottomSpacing
			/>
		</PanelBody>
	);
};
