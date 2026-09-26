<?php
/**
 * Server-side rendering of the Posts Grid Pagination block.
 *
 * Inner blocks are rendered before their parent, so the pagination asks the
 * shared Query service for the grid's query (memoized: it runs only once per
 * request) using the `tfpg/postsPerPage` context provided by the grid.
 *
 * Links are real URLs, so pagination works without JS; with JS they are
 * intercepted and loaded in place by the Interactivity Router.
 *
 * @package TFPG
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (unused).
 * @var WP_Block $block      Block instance.
 */

use TFPG\Query;

defined( 'ABSPATH' ) || exit;

$tfpg_query   = Query::get_grid_query( (int) ( $block->context['tfpg/postsPerPage'] ?? 6 ) );
$tfpg_total   = (int) $tfpg_query->max_num_pages;
$tfpg_current = max( 1, (int) $tfpg_query->get( 'paged' ) );

if ( $tfpg_total < 2 ) {
	return;
}

$tfpg_filters    = Query::get_active_filters();
$tfpg_prev_label = '' !== ( $attributes['previousLabel'] ?? '' ) ? $attributes['previousLabel'] : __( 'Previous', 'tfpg-posts-grid-filter' );
$tfpg_next_label = '' !== ( $attributes['nextLabel'] ?? '' ) ? $attributes['nextLabel'] : __( 'Next', 'tfpg-posts-grid-filter' );
$tfpg_link_attrs = 'data-wp-on--click="actions.goToPage" data-wp-on--mouseenter="actions.prefetchPage" data-wp-on--focus="actions.prefetchPage"';

$tfpg_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'      => 'tfpg-pagination',
		'aria-label' => __( 'Posts pagination', 'tfpg-posts-grid-filter' ),
	)
);
?>
<nav <?php echo $tfpg_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<?php if ( $tfpg_current > 1 ) : ?>
		<a class="tfpg-pagination__link tfpg-pagination__prev" href="<?php echo esc_url( Query::build_url( $tfpg_filters, $tfpg_current - 1 ) ); ?>" rel="prev" <?php echo $tfpg_link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
			<span aria-hidden="true">&larr;</span> <?php echo esc_html( $tfpg_prev_label ); ?>
		</a>
	<?php else : ?>
		<span class="tfpg-pagination__link tfpg-pagination__prev is-disabled" aria-hidden="true">
			<span>&larr;</span> <?php echo esc_html( $tfpg_prev_label ); ?>
		</span>
	<?php endif; ?>

	<?php if ( ! empty( $attributes['showPageNumbers'] ) ) : ?>
		<ul class="tfpg-pagination__pages" role="list">
			<?php foreach ( Query::pagination_range( $tfpg_current, $tfpg_total ) as $tfpg_item ) : ?>
				<li>
					<?php if ( 'dots' === $tfpg_item ) : ?>
						<span class="tfpg-pagination__dots" aria-hidden="true">&hellip;</span>
					<?php elseif ( $tfpg_item === $tfpg_current ) : ?>
						<span class="tfpg-pagination__link tfpg-pagination__number is-current" aria-current="page">
							<span class="tfpg-visually-hidden"><?php esc_html_e( 'Page', 'tfpg-posts-grid-filter' ); ?></span>
							<?php echo (int) $tfpg_item; ?>
						</span>
					<?php else : ?>
						<a class="tfpg-pagination__link tfpg-pagination__number" href="<?php echo esc_url( Query::build_url( $tfpg_filters, $tfpg_item ) ); ?>" <?php echo $tfpg_link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
							<span class="tfpg-visually-hidden"><?php esc_html_e( 'Page', 'tfpg-posts-grid-filter' ); ?></span>
							<?php echo (int) $tfpg_item; ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<span class="tfpg-pagination__summary">
			<?php
			/* translators: 1: current page, 2: total pages. */
			echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'tfpg-posts-grid-filter' ), $tfpg_current, $tfpg_total ) );
			?>
		</span>
	<?php endif; ?>

	<?php if ( $tfpg_current < $tfpg_total ) : ?>
		<a class="tfpg-pagination__link tfpg-pagination__next" href="<?php echo esc_url( Query::build_url( $tfpg_filters, $tfpg_current + 1 ) ); ?>" rel="next" <?php echo $tfpg_link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
			<?php echo esc_html( $tfpg_next_label ); ?> <span aria-hidden="true">&rarr;</span>
		</a>
	<?php else : ?>
		<span class="tfpg-pagination__link tfpg-pagination__next is-disabled" aria-hidden="true">
			<?php echo esc_html( $tfpg_next_label ); ?> <span>&rarr;</span>
		</span>
	<?php endif; ?>
</nav>
