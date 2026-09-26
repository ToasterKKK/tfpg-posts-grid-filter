/**
 * Posts Filter block — editor UI.
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Disabled,
	Notice,
	PanelBody,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';

import { TAXONOMIES } from '../shared/constants';

const TERM_QUERY = {
	per_page: 100,
	hide_empty: true,
	orderby: 'name',
	order: 'asc',
	_fields: 'id,name,slug,count',
};

/**
 * Static preview of one filter group, mirroring render.php markup.
 *
 * @param {Object}        props
 * @param {string}        props.filterKey  `category` or `tag`.
 * @param {string}        props.label      Group label.
 * @param {Object[]|null} props.terms      REST terms, or null while loading.
 * @param {boolean}       props.showCounts Whether to show post counts.
 */
function FilterGroupPreview( { filterKey, label, terms, showCounts } ) {
	return (
		<fieldset
			className={ `tfpg-filter__group tfpg-filter__group--${ filterKey }` }
		>
			<legend className="tfpg-filter__legend">{ label }</legend>
			{ terms === null && <Spinner /> }
			{ terms?.length === 0 && (
				<p className="tfpg-filter__empty">
					{ __(
						'Nothing to filter by yet.',
						'tfpg-posts-grid-filter'
					) }
				</p>
			) }
			{ terms?.length > 0 && (
				<ul className="tfpg-filter__options">
					{ terms.map( ( term ) => (
						<li key={ term.id } className="tfpg-filter__option">
							{ /* Static chip: the preview is not interactive. */ }
							<span className="tfpg-filter__label">
								<span className="tfpg-filter__name">
									{ decodeEntities( term.name ) }
								</span>
								{ showCounts && (
									<span
										className="tfpg-filter__count"
										title={ sprintf(
											/* translators: %d: number of posts. */
											_n(
												'%d post',
												'%d posts',
												term.count,
												'tfpg-posts-grid-filter'
											),
											term.count
										) }
									>
										{ term.count }
									</span>
								) }
							</span>
						</li>
					) ) }
				</ul>
			) }
		</fieldset>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { showCategories, showTags, showCounts, categoriesLabel, tagsLabel } =
		attributes;

	const { categories, tags, hasGrid } = useSelect(
		( select ) => {
			const core = select( coreStore );

			return {
				categories: showCategories
					? core.getEntityRecords(
							'taxonomy',
							TAXONOMIES.category,
							TERM_QUERY
						)
					: [],
				tags: showTags
					? core.getEntityRecords(
							'taxonomy',
							TAXONOMIES.tag,
							TERM_QUERY
						)
					: [],
				hasGrid:
					select( blockEditorStore ).getBlocksByName(
						'tfpg/posts-grid'
					).length > 0,
			};
		},
		[ showCategories, showTags ]
	);

	const blockProps = useBlockProps( { className: 'tfpg-filter' } );
	const defaultCategoriesLabel = __( 'Categories', 'tfpg-posts-grid-filter' );
	const defaultTagsLabel = __( 'Tags', 'tfpg-posts-grid-filter' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filters', 'tfpg-posts-grid-filter' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Category filter',
							'tfpg-posts-grid-filter'
						) }
						checked={ showCategories }
						onChange={ ( value ) =>
							setAttributes( { showCategories: value } )
						}
					/>
					{ showCategories && (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Category label',
								'tfpg-posts-grid-filter'
							) }
							placeholder={ defaultCategoriesLabel }
							value={ categoriesLabel }
							onChange={ ( value ) =>
								setAttributes( { categoriesLabel: value } )
							}
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Tag filter', 'tfpg-posts-grid-filter' ) }
						checked={ showTags }
						onChange={ ( value ) =>
							setAttributes( { showTags: value } )
						}
					/>
					{ showTags && (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __(
								'Tag label',
								'tfpg-posts-grid-filter'
							) }
							placeholder={ defaultTagsLabel }
							value={ tagsLabel }
							onChange={ ( value ) =>
								setAttributes( { tagsLabel: value } )
							}
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show post counts',
							'tfpg-posts-grid-filter'
						) }
						help={ __(
							'On the front end, counts update live to reflect the other active filters.',
							'tfpg-posts-grid-filter'
						) }
						checked={ showCounts }
						onChange={ ( value ) =>
							setAttributes( { showCounts: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ ! hasGrid && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'This filter controls Posts Grid blocks on the same page. Add a Posts Grid block anywhere on this page.',
							'tfpg-posts-grid-filter'
						) }
					</Notice>
				) }
				{ ! showCategories && ! showTags ? (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Enable the category or tag filter in the block settings.',
							'tfpg-posts-grid-filter'
						) }
					</Notice>
				) : (
					<Disabled>
						<div className="tfpg-filter__form">
							{ showCategories && (
								<FilterGroupPreview
									filterKey="category"
									label={
										categoriesLabel ||
										defaultCategoriesLabel
									}
									terms={ categories }
									showCounts={ showCounts }
								/>
							) }
							{ showTags && (
								<FilterGroupPreview
									filterKey="tag"
									label={ tagsLabel || defaultTagsLabel }
									terms={ tags }
									showCounts={ showCounts }
								/>
							) }
						</div>
					</Disabled>
				) }
			</div>
		</>
	);
}
