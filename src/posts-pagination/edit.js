/**
 * Posts Grid Pagination block — editor UI.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import { POST_TYPE, getPreviewQuery } from '../shared/constants';

const MAX_PREVIEW_NUMBERS = 3;

export default function Edit( { attributes, setAttributes, context } ) {
	const { showPageNumbers, previousLabel, nextLabel } = attributes;
	const postsPerPage = context[ 'tfpg/postsPerPage' ] || 6;

	// Same query object as the grid preview, so this reuses its request.
	const totalPages = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecordsTotalPages?.(
				'postType',
				POST_TYPE,
				getPreviewQuery( postsPerPage )
			),
		[ postsPerPage ]
	);

	const pages = Math.max(
		1,
		Math.min( totalPages || 2, MAX_PREVIEW_NUMBERS )
	);
	const blockProps = useBlockProps( { className: 'tfpg-pagination' } );
	const defaultPrevious = __( 'Previous', 'tfpg-posts-grid-filter' );
	const defaultNext = __( 'Next', 'tfpg-posts-grid-filter' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'tfpg-posts-grid-filter' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show page numbers',
							'tfpg-posts-grid-filter'
						) }
						help={
							showPageNumbers
								? undefined
								: __(
										'Shows “Page X of Y” instead.',
										'tfpg-posts-grid-filter'
									)
						}
						checked={ showPageNumbers }
						onChange={ ( value ) =>
							setAttributes( { showPageNumbers: value } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Previous label',
							'tfpg-posts-grid-filter'
						) }
						placeholder={ defaultPrevious }
						value={ previousLabel }
						onChange={ ( value ) =>
							setAttributes( { previousLabel: value } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Next label', 'tfpg-posts-grid-filter' ) }
						placeholder={ defaultNext }
						value={ nextLabel }
						onChange={ ( value ) =>
							setAttributes( { nextLabel: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<nav { ...blockProps }>
				<span className="tfpg-pagination__link tfpg-pagination__prev is-disabled">
					<span aria-hidden="true">&larr;</span>{ ' ' }
					{ previousLabel || defaultPrevious }
				</span>
				{ showPageNumbers ? (
					<ul className="tfpg-pagination__pages">
						{ Array.from( { length: pages }, ( _, index ) => (
							<li key={ index }>
								<span
									className={
										'tfpg-pagination__link tfpg-pagination__number' +
										( index === 0 ? ' is-current' : '' )
									}
								>
									{ index + 1 }
								</span>
							</li>
						) ) }
						{ totalPages > MAX_PREVIEW_NUMBERS && (
							<li>
								<span className="tfpg-pagination__dots">
									&hellip;
								</span>
							</li>
						) }
					</ul>
				) : (
					<span className="tfpg-pagination__summary">
						{ __( 'Page 1 of N', 'tfpg-posts-grid-filter' ) }
					</span>
				) }
				<span className="tfpg-pagination__link tfpg-pagination__next">
					{ nextLabel || defaultNext }{ ' ' }
					<span aria-hidden="true">&rarr;</span>
				</span>
			</nav>
		</>
	);
}
