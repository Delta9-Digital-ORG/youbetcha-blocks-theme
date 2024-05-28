import React from 'react';
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
import { props } from '@eightshift/frontend-libs/scripts';
import { ImageOptions } from '../../../components/image/components/image-options';

export const SiteNavigationOptions = ({ attributes, setAttributes }) => {
	return (
		<PanelBody title={__('Site navigation', 'you-betcha-cannabis-theme')}>
			<ImageOptions
				{...props('logo', attributes, { setAttributes })}
				label={__('Logo', 'you-betcha-cannabis-theme')}
				hideFullSizeToggle
				hideRoundedCornersToggle
				noBottomSpacing
			/>
		</PanelBody>
	);
};
