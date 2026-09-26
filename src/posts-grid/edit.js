/**
 * Posts Grid block — editor UI.
 *
 * The editor shows a live React preview built from the REST API (through the
 * core-data store) rather than a ServerSideRender iframe, so the Pagination
 * inner block stays fully editable inside the grid.
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	Notice,
	PanelBody,
	Placeholder,
	RangeControl,
	Spinner,
	ToggleGroupControl as StableToggleGroupControl,
	ToggleGroupControlOption as StableToggleGroupControlOption,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Fallback for WordPress < 6.9, where the control was still experimental.
	__experimentalToggleGroupControl as ExperimentalToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- See above.
	__experimentalToggleGroupControlOption as ExperimentalToggleGroupControlOption,
} from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';

import { POST_TYPE, TAXONOMIES, getPreviewQuery } from '../shared/constants';

const ToggleGroupControl =
	StableToggleGroupControl || ExperimentalToggleGroupControl;
const ToggleGroupControlOption =
	StableToggleGroupControlOption || ExperimentalToggleGroupControlOption;

const PAGINATION_BLOCK = 'tfpg/posts-pagination';
const TEMPLATE = [ [ PAGINATION_BLOCK ] ];
const COLUMN_OPTIONS = [ 2, 3, 4 ];
const MAX_PER_PAGE = 50;

/**
 * Strips HTML tags from a rendered REST field.
 *
 * @param {string} html HTML string.
 * @return {string} Plain text.
 */
const stripTags = ( html = '' ) =>
	new window.DOMParser().parseFromString( html, 'text/html' ).body
		.textContent || '';

/**
 * One card of the editor preview. Mirrors the markup of render.php so the
 * front-end stylesheet styles both.
 *
 * @param {Object} props
 * @param {Object} props.post         REST post object.
 * @param {Map}    props.categoryById Category names by term ID.
 * @param {Map}    props.tagById      Tag names by term ID.
 */
function PostCardPreview( { post, categoryById, tagById } ) {
	const media = useSelect(
		( select ) =>
			post.featured_media
				? select( coreStore ).getEntityRecord(
						'postType',
						'attachment',
						post.featured_media,
						{ context: 'view' }
					)
				: null,
		[ post.featured_media ]
	);

	const imageUrl =
		media?.media_details?.sizes?.medium_large?.source_url ||
		media?.media_details?.sizes?.large?.source_url ||
		media?.source_url;

	const categories = ( post[ TAXONOMIES.category ] || [] )
		.map( ( id ) => categoryById.get( id ) )
		.filter( Boolean );
	const tags = ( post[ TAXONOMIES.tag ] || [] )
		.map( ( id ) => tagById.get( id ) )
		.filter( Boolean );
	const excerpt = post.excerpt?.raw || stripTags( post.excerpt?.rendered );

	return (
		<li className="tfpg-card">
			<div className="tfpg-card__media">
				{ imageUrl ? (
					<img className="tfpg-card__image" src={ imageUrl } alt="" />
				) : (
					<span className="tfpg-card__image tfpg-card__image--placeholder" />
				) }
			</div>
			<div className="tfpg-card__body">
				{ categories.length > 0 && (
					<p className="tfpg-card__categories">
						{ categories.join( ' · ' ) }
					</p>
				) }
				<h3 className="tfpg-card__title">
					{ decodeEntities( post.title?.rendered || '' ) ||
						__( '(no title)', 'tfpg-posts-grid-filter' ) }
				</h3>
				<div className="tfpg-card__excerpt">
					<p>{ decodeEntities( excerpt ) }</p>
				</div>
				{ tags.length > 0 && (
					<ul className="tfpg-card__tags">
						{ tags.map( ( tag ) => (
							<li key={ tag } className="tfpg-card__tag">
								#{ tag }
							</li>
						) ) }
					</ul>
				) }
			</div>
		</li>
	);
}

/**
 * Builds an id => name map from a list of REST terms.
 *
 * @param {Object[]|null} terms REST term objects.
 * @return {Map<number, string>} Term names by ID.
 */
const toNameMap = ( terms ) =>
	new Map(
		( terms || [] ).map( ( term ) => [
			term.id,
			decodeEntities( term.name ),
		] )
	);

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { columns, postsPerPage } = attributes;

	const { posts, isResolving, categories, tags, hasPagination, hasFilter } =
		useSelect(
			( select ) => {
				const core = select( coreStore );
				const blockEditor = select( blockEditorStore );
				const query = getPreviewQuery( postsPerPage );
				const termQuery = { per_page: -1, _fields: 'id,name' };

				return {
					posts: core.getEntityRecords(
						'postType',
						POST_TYPE,
						query
					),
					isResolving: core.isResolving( 'getEntityRecords', [
						'postType',
						POST_TYPE,
						query,
					] ),
					categories: core.getEntityRecords(
						'taxonomy',
						TAXONOMIES.category,
						termQuery
					),
					tags: core.getEntityRecords(
						'taxonomy',
						TAXONOMIES.tag,
						termQuery
					),
					hasPagination: blockEditor.getBlocks( clientId ).length > 0,
					hasFilter:
						blockEditor.getBlocksByName( 'tfpg/posts-filter' )
							.length > 0,
				};
			},
			[ postsPerPage, clientId ]
		);

	const categoryById = useMemo(
		() => toNameMap( categories ),
		[ categories ]
	);
	const tagById = useMemo( () => toNameMap( tags ), [ tags ] );

	const blockProps = useBlockProps( {
		className: `tfpg-posts-grid tfpg-posts-grid--columns-${ columns }`,
		style: { '--tfpg-columns': columns },
	} );

	const { replaceInnerBlocks } = useDispatch( blockEditorStore );

	// Inner blocks render directly inside the grid wrapper, after the cards,
	// exactly like on the front end. The Pagination block is hidden from the
	// inserter (`"inserter": false`) so a grid can never get two of them; if
	// it is removed, this button brings it back.
	const { children: innerBlocks, ...innerBlocksProps } = useInnerBlocksProps(
		blockProps,
		{
			allowedBlocks: [ PAGINATION_BLOCK ],
			template: TEMPLATE,
			renderAppender: hasPagination
				? false
				: () => (
						<Button
							__next40pxDefaultSize
							variant="secondary"
							icon="plus"
							className="tfpg-posts-grid__add-pagination"
							onClick={ () =>
								replaceInnerBlocks( clientId, [
									createBlock( PAGINATION_BLOCK ),
								] )
							}
						>
							{ __( 'Add pagination', 'tfpg-posts-grid-filter' ) }
						</Button>
					),
		}
	);

	const inspector = (
		<InspectorControls>
			<PanelBody title={ __( 'Layout', 'tfpg-posts-grid-filter' ) }>
				<ToggleGroupControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					isBlock
					label={ __( 'Columns', 'tfpg-posts-grid-filter' ) }
					value={ columns }
					onChange={ ( value ) =>
						setAttributes( { columns: Number( value ) } )
					}
				>
					{ COLUMN_OPTIONS.map( ( option ) => (
						<ToggleGroupControlOption
							key={ option }
							value={ option }
							label={ String( option ) }
						/>
					) ) }
				</ToggleGroupControl>
			</PanelBody>
			<PanelBody title={ __( 'Query', 'tfpg-posts-grid-filter' ) }>
				<RangeControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Posts per page', 'tfpg-posts-grid-filter' ) }
					value={ postsPerPage }
					onChange={ ( value ) =>
						setAttributes( { postsPerPage: value || 1 } )
					}
					min={ 1 }
					max={ MAX_PER_PAGE }
				/>
				{ ! hasFilter && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Tip: add a Posts Filter block anywhere on this page to let visitors filter this grid by category and tag.',
							'tfpg-posts-grid-filter'
						) }
					</Notice>
				) }
			</PanelBody>
		</InspectorControls>
	);

	let preview;

	if ( ! posts && isResolving ) {
		preview = (
			<Placeholder
				icon="grid-view"
				label={ __( 'Posts Grid', 'tfpg-posts-grid-filter' ) }
			>
				<Spinner />
			</Placeholder>
		);
	} else if ( ! posts?.length ) {
		preview = (
			<Placeholder
				icon="grid-view"
				label={ __( 'Posts Grid', 'tfpg-posts-grid-filter' ) }
				instructions={ __(
					'No Grid Posts found. Add some under “Grid Posts” in the admin menu.',
					'tfpg-posts-grid-filter'
				) }
			/>
		);
	} else {
		preview = (
			<>
				<p className="tfpg-posts-grid__editor-note">
					{ sprintf(
						/* translators: %d: number of posts shown in the preview. */
						_n(
							'Preview of the latest %d post. Filters apply on the front end.',
							'Preview of the latest %d posts. Filters apply on the front end.',
							posts.length,
							'tfpg-posts-grid-filter'
						),
						posts.length
					) }
				</p>
				<ul className="tfpg-posts-grid__list">
					{ posts.map( ( post ) => (
						<PostCardPreview
							key={ post.id }
							post={ post }
							categoryById={ categoryById }
							tagById={ tagById }
						/>
					) ) }
				</ul>
			</>
		);
	}

	return (
		<>
			{ inspector }
			<div { ...innerBlocksProps }>
				{ preview }
				{ innerBlocks }
			</div>
		</>
	);
}
