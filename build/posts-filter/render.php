<?php
/**
 * Server-side rendering of the Posts Filter block.
 *
 * Renders a real GET form (so filtering works without JS) enhanced with
 * Interactivity API directives: toggling a checkbox updates the shared
 * `state.filters` and navigates to the matching URL, which refreshes every
 * Posts Grid on the page — wherever it is placed.
 *
 * The filter is itself a router region so its per-term counts are refreshed
 * from the server after each change.
 *
 * @package TFPG
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (unused).
 * @var WP_Block $block      Block instance.
 */

use TFPG\Blocks;
use TFPG\Query;

defined( 'ABSPATH' ) || exit;

Blocks::init_store();

$tfpg_groups = array();

if ( ! empty( $attributes['showCategories'] ) ) {
	$tfpg_groups['category'] = '' !== ( $attributes['categoriesLabel'] ?? '' ) ? $attributes['categoriesLabel'] : __( 'Categories', 'tfpg-posts-grid-filter' );
}
if ( ! empty( $attributes['showTags'] ) ) {
	$tfpg_groups['tag'] = '' !== ( $attributes['tagsLabel'] ?? '' ) ? $attributes['tagsLabel'] : __( 'Tags', 'tfpg-posts-grid-filter' );
}

if ( ! $tfpg_groups ) {
	return;
}

$tfpg_show_counts = ! empty( $attributes['showCounts'] );
$tfpg_filters     = Query::get_active_filters();
$tfpg_params      = Query::params();
$tfpg_region_id   = Blocks::next_region_id( 'filter' );

$tfpg_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                       => 'tfpg-filter',
		'data-wp-interactive'         => Blocks::STORE,
		'data-wp-router-region'       => $tfpg_region_id,
		'data-wp-class--is-loading'   => 'state.isLoading',
		'data-wp-on-window--popstate' => 'callbacks.syncFiltersFromUrl',
	)
);
?>
<div <?php echo $tfpg_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<form class="tfpg-filter__form" method="get" action="<?php echo esc_url( Query::get_base_url() ); ?>" data-wp-on--submit="actions.submitForm">
		<?php foreach ( Query::get_foreign_query_args() as $tfpg_name => $tfpg_value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $tfpg_name ); ?>" value="<?php echo esc_attr( $tfpg_value ); ?>" />
		<?php endforeach; ?>

		<?php foreach ( $tfpg_groups as $tfpg_key => $tfpg_label ) : ?>
			<?php
			$tfpg_terms  = Query::get_filter_terms( $tfpg_key );
			$tfpg_counts = $tfpg_show_counts ? Query::get_term_counts( $tfpg_key ) : array();
			?>
			<fieldset class="tfpg-filter__group tfpg-filter__group--<?php echo esc_attr( $tfpg_key ); ?>">
				<legend class="tfpg-filter__legend"><?php echo esc_html( $tfpg_label ); ?></legend>

				<?php if ( ! $tfpg_terms ) : ?>
					<p class="tfpg-filter__empty"><?php esc_html_e( 'Nothing to filter by yet.', 'tfpg-posts-grid-filter' ); ?></p>
				<?php else : ?>
					<ul class="tfpg-filter__options" role="list">
						<?php foreach ( $tfpg_terms as $tfpg_term ) : ?>
							<?php
							$tfpg_count    = (int) ( $tfpg_counts[ $tfpg_term->slug ] ?? 0 );
							$tfpg_selected = in_array( $tfpg_term->slug, $tfpg_filters[ $tfpg_key ], true );
							$tfpg_classes  = 'tfpg-filter__option';
							if ( $tfpg_show_counts && 0 === $tfpg_count && ! $tfpg_selected ) {
								$tfpg_classes .= ' has-no-results';
							}
							// Per-option context read by `state.isTermSelected` and `actions.toggleTerm`.
							$tfpg_context = wp_interactivity_data_wp_context(
								array(
									'taxonomy' => $tfpg_key,
									'slug'     => $tfpg_term->slug,
								)
							);
							?>
							<li class="<?php echo esc_attr( $tfpg_classes ); ?>" <?php echo $tfpg_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by wp_interactivity_data_wp_context(). ?> data-wp-class--is-selected="state.isTermSelected">
								<label class="tfpg-filter__label">
									<input
										class="tfpg-filter__checkbox"
										type="checkbox"
										name="<?php echo esc_attr( $tfpg_params['filters'][ $tfpg_key ] ); ?>[]"
										value="<?php echo esc_attr( $tfpg_term->slug ); ?>"
										data-wp-bind--checked="state.isTermSelected"
										data-wp-on--change="actions.toggleTerm"
									/>
									<span class="tfpg-filter__name"><?php echo esc_html( $tfpg_term->name ); ?></span>
									<?php if ( $tfpg_show_counts ) : ?>
										<span class="tfpg-filter__count">
											<span aria-hidden="true"><?php echo (int) $tfpg_count; ?></span>
											<span class="tfpg-visually-hidden">
												<?php
												/* translators: %d: number of posts. */
												echo esc_html( sprintf( _n( '(%d post)', '(%d posts)', $tfpg_count, 'tfpg-posts-grid-filter' ), $tfpg_count ) );
												?>
											</span>
										</span>
									<?php endif; ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</fieldset>
		<?php endforeach; ?>

		<div class="tfpg-filter__actions">
			<button type="submit" class="tfpg-filter__apply wp-element-button" data-wp-bind--hidden="state.isHydrated">
				<?php esc_html_e( 'Apply filters', 'tfpg-posts-grid-filter' ); ?>
			</button>
			<a
				class="tfpg-filter__clear"
				href="<?php echo esc_url( Query::build_url( array() ) ); ?>"
				data-wp-bind--hidden="!state.hasActiveFilters"
				data-wp-on--click="actions.clearFilters"
			>
				<?php esc_html_e( 'Clear all', 'tfpg-posts-grid-filter' ); ?>
			</a>
		</div>
	</form>
</div>
