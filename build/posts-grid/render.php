<?php
/**
 * Server-side rendering of the Posts Grid block.
 *
 * The grid is a router region of the shared `tfpg` store: when the filters or
 * the page change, the Interactivity Router fetches the new URL and swaps this
 * element with its freshly rendered counterpart. All markup is therefore
 * produced here, in one place, for both the first load and every update.
 *
 * @package TFPG
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the Pagination block).
 * @var WP_Block $block      Block instance.
 */

use TFPG\Blocks;
use TFPG\Content_Model;
use TFPG\Query;

defined( 'ABSPATH' ) || exit;

Blocks::init_store();

$tfpg_columns  = in_array( (int) ( $attributes['columns'] ?? 3 ), array( 2, 3, 4 ), true ) ? (int) $attributes['columns'] : 3;
$tfpg_query    = Query::get_grid_query( (int) ( $attributes['postsPerPage'] ?? 6 ) );
$tfpg_total    = (int) $tfpg_query->found_posts;
$tfpg_filtered = (bool) array_filter( Query::get_active_filters() );

$tfpg_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                     => 'tfpg-posts-grid tfpg-posts-grid--columns-' . $tfpg_columns,
		'style'                     => '--tfpg-columns:' . $tfpg_columns . ';',
		'tabindex'                  => '-1',
		'data-wp-interactive'       => Blocks::STORE,
		'data-wp-router-region'     => Blocks::next_region_id( 'grid' ),
		'data-wp-class--is-loading' => 'state.isLoading',
		'data-wp-bind--aria-busy'   => 'state.isLoading',
	)
);

if ( $tfpg_query->have_posts() ) {
	$tfpg_status = sprintf(
		/* translators: %d: number of posts. */
		_n( '%d post found.', '%d posts found.', $tfpg_total, 'tfpg-posts-grid-filter' ),
		$tfpg_total
	);
} else {
	$tfpg_status = __( 'No posts found.', 'tfpg-posts-grid-filter' );
}
?>
<div <?php echo $tfpg_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<p class="tfpg-posts-grid__status tfpg-visually-hidden" role="status"><?php echo esc_html( $tfpg_status ); ?></p>

	<?php if ( $tfpg_query->have_posts() ) : ?>
		<ul class="tfpg-posts-grid__list" role="list">
			<?php foreach ( $tfpg_query->posts as $tfpg_post ) : ?>
				<?php
				$tfpg_permalink  = get_permalink( $tfpg_post );
				$tfpg_categories = get_the_terms( $tfpg_post, Content_Model::TAX_CATEGORY );
				$tfpg_tags       = get_the_terms( $tfpg_post, Content_Model::TAX_TAG );
				?>
				<li class="tfpg-card">
					<div class="tfpg-card__media">
						<?php if ( has_post_thumbnail( $tfpg_post ) ) : ?>
							<?php
							$tfpg_image = new WP_HTML_Tag_Processor(
								get_the_post_thumbnail(
									$tfpg_post,
									'medium_large',
									array(
										'class' => 'tfpg-card__image',
										'alt'   => '',
										'sizes' => '(max-width: 600px) 100vw, (max-width: 1024px) 50vw, ' . (int) ceil( 100 / $tfpg_columns ) . 'vw',
									)
								)
							);
							// Some themes inject inline sizing styles into post thumbnails
							// (e.g. Twenty Twenty-One); the card's CSS owns the layout.
							if ( $tfpg_image->next_tag( 'img' ) ) {
								$tfpg_image->remove_attribute( 'style' );
							}
							echo $tfpg_image->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated image markup.
							?>
						<?php else : ?>
							<span class="tfpg-card__image tfpg-card__image--placeholder"></span>
						<?php endif; ?>
					</div>

					<div class="tfpg-card__body">
						<?php if ( $tfpg_categories && ! is_wp_error( $tfpg_categories ) ) : ?>
							<p class="tfpg-card__categories">
								<span class="tfpg-visually-hidden"><?php esc_html_e( 'Categories:', 'tfpg-posts-grid-filter' ); ?></span>
								<?php echo esc_html( implode( ' · ', wp_list_pluck( $tfpg_categories, 'name' ) ) ); ?>
							</p>
						<?php endif; ?>

						<h3 class="tfpg-card__title">
							<a href="<?php echo esc_url( $tfpg_permalink ); ?>"><?php echo esc_html( get_the_title( $tfpg_post ) ); ?></a>
						</h3>

						<div class="tfpg-card__excerpt">
							<p><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $tfpg_post ) ) ); ?></p>
						</div>

						<?php if ( $tfpg_tags && ! is_wp_error( $tfpg_tags ) ) : ?>
							<ul class="tfpg-card__tags" role="list">
								<li class="tfpg-visually-hidden"><?php esc_html_e( 'Tags:', 'tfpg-posts-grid-filter' ); ?></li>
								<?php foreach ( $tfpg_tags as $tfpg_tag ) : ?>
									<li class="tfpg-card__tag">#<?php echo esc_html( $tfpg_tag->name ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php
		// Pagination inner block (rendered by WordPress before this template).
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered block markup.
		?>
	<?php else : ?>
		<div class="tfpg-posts-grid__empty">
			<p><?php esc_html_e( 'No posts match the selected filters.', 'tfpg-posts-grid-filter' ); ?></p>
			<?php if ( $tfpg_filtered ) : ?>
				<a class="tfpg-posts-grid__clear" href="<?php echo esc_url( Query::build_url( array() ) ); ?>" data-wp-on--click="actions.clearFilters">
					<?php esc_html_e( 'Clear all filters', 'tfpg-posts-grid-filter' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
