<?php
/**
 * Build and optionally apply a production/local cross-block association manifest.
 *
 * Usage:
 * studio wp --path <site> eval-file wp-content/themes/platejka_rework/tools/cross-block-sync.php dry-run
 *
 * @package platejka_rework
 */

defined( 'ABSPATH' ) || exit;

/** @return array<string,string> Legacy layout to page-builder slug. */
function platejka_cross_sync_layout_map(): array {
	return array(
		'destinations'     => 'destinations',
		'wtt'              => 'cross-exchange',
		'company_metrics'  => 'cross-company-metrics',
		'problems_solved'  => 'problems',
		'client_reviews'   => 'review',
		'external_ratings' => 'cross-external-ratings',
		'documents'        => 'documents',
		'key_facts'        => 'cross-key-facts',
		'transfer_goals'   => 'cross-transfer-goals',
		'swift_problems'   => 'cross-swift-problems',
		'guarantees'       => 'guarantees',
		'audience'         => 'cross-audience',
		'data_table'       => 'table',
	);
}

/** @return array<string,string> CSS marker to legacy layout. */
function platejka_cross_sync_class_map(): array {
	return array(
		'cb-destinations'                 => 'destinations',
		'cb-exchange'                     => 'wtt',
		'cb-metrics'                      => 'company_metrics',
		'cb-card-section--problems_solved' => 'problems_solved',
		'cb-reviews'                      => 'client_reviews',
		'cb-ratings'                      => 'external_ratings',
		'cb-documents'                    => 'documents',
		'cb-facts'                        => 'key_facts',
		'cb-card-section--transfer_goals' => 'transfer_goals',
		'cb-card-section--swift_problems' => 'swift_problems',
		'cb-guarantees'                   => 'guarantees',
		'cb-card-section--audience'       => 'audience',
		'cb-data-table'                   => 'data_table',
	);
}

/** @return array<int,string> Included legacy layouts in rendered order. */
function platejka_cross_sync_parse_html( string $html ): array {
	if ( '' === $html ) {
		return array();
	}

	$layouts = array();
	preg_match_all( '/<section\b[^>]*\bclass=(?:"([^"]*)"|\'([^\']*)\')[^>]*>/i', $html, $matches, PREG_SET_ORDER );
	foreach ( $matches as $match ) {
		$classes = preg_split( '/\s+/', trim( (string) ( $match[1] ?: $match[2] ) ) ) ?: array();
		foreach ( platejka_cross_sync_class_map() as $marker => $layout ) {
			if ( in_array( $marker, $classes, true ) && ! in_array( $layout, $layouts, true ) ) {
				$layouts[] = $layout;
				break;
			}
		}
	}

	return $layouts;
}

/** @param array<string,mixed> $row */
function platejka_cross_sync_row_enabled( array $row ): bool {
	foreach ( array( 'enabled', 'show_block', 'vklyuchit_blok' ) as $field ) {
		if ( array_key_exists( $field, $row ) ) {
			return (bool) $row[ $field ];
		}
	}
	return true;
}

/**
 * Preserve existing rows and insert only missing production associations before FAQ/call.
 *
 * @param array<int,array<string,mixed>> $rows
 * @param array<int,string>              $production_layouts
 * @return array<int,array<string,mixed>>
 */
function platejka_cross_sync_merge_builder_rows( array $rows, array $production_layouts ): array {
	$map     = platejka_cross_sync_layout_map();
	$targets = array();
	foreach ( $production_layouts as $layout ) {
		if ( isset( $map[ $layout ] ) && ! in_array( $map[ $layout ], $targets, true ) ) {
			$targets[] = $map[ $layout ];
		}
	}

	$present = array();
	foreach ( $rows as $index => $row ) {
		$slug = is_array( $row ) ? (string) ( $row['acf_fc_layout'] ?? '' ) : '';
		if ( '' !== $slug ) {
			$present[ $slug ] = $index;
		}
	}

	foreach ( $targets as $slug ) {
		if ( isset( $present[ $slug ] ) && empty( $rows[ $present[ $slug ] ]['enabled'] ) ) {
			$rows[ $present[ $slug ] ]['enabled'] = 1;
		}
	}

	$missing = array_values( array_filter( $targets, static fn( string $slug ): bool => ! isset( $present[ $slug ] ) ) );
	if ( empty( $missing ) ) {
		return array_values( $rows );
	}

	$insert_at = count( $rows );
	foreach ( $rows as $index => $row ) {
		if ( in_array( $row['acf_fc_layout'] ?? '', array( 'faq', 'call' ), true ) ) {
			$insert_at = $index;
			break;
		}
	}
	$new_rows = array_map(
		static fn( string $slug ): array => array( 'acf_fc_layout' => $slug, 'enabled' => 1, 'anchor' => '' ),
		$missing
	);

	return array_values( array_merge( array_slice( $rows, 0, $insert_at ), $new_rows, array_slice( $rows, $insert_at ) ) );
}

/**
 * @param array<int,string>              $production_layouts
 * @param array<int,array<string,mixed>> $builder_rows
 * @param array<int,array<string,mixed>> $source_rows
 * @return array<string,mixed>
 */
function platejka_cross_sync_plan_page( string $slug, int $post_id, array $production_layouts, array $builder_rows, array $source_rows ): array {
	$source_by_layout = array();
	foreach ( $source_rows as $row ) {
		if ( is_array( $row ) && is_string( $row['acf_fc_layout'] ?? null ) ) {
			$source_by_layout[ $row['acf_fc_layout'] ] = $row;
		}
	}

	$resolvable = array();
	$unresolved = array();
	foreach ( $production_layouts as $layout ) {
		if ( isset( $source_by_layout[ $layout ] ) && platejka_cross_sync_row_enabled( $source_by_layout[ $layout ] ) ) {
			$resolvable[] = $layout;
		} else {
			$unresolved[] = $layout;
		}
	}

	$proposed       = platejka_cross_sync_merge_builder_rows( $builder_rows, $production_layouts );
	$current_slugs  = array_values( array_filter( array_column( $builder_rows, 'acf_fc_layout' ), 'is_string' ) );
	$proposed_slugs = array_values( array_filter( array_column( $proposed, 'acf_fc_layout' ), 'is_string' ) );
	$additions      = array_values( array_diff( $proposed_slugs, $current_slugs ) );
	$production_targets = array_values( array_unique( array_filter( array_map( static fn( string $layout ): string => platejka_cross_sync_layout_map()[ $layout ] ?? '', $production_layouts ) ) ) );
	$enabled        = array();
	foreach ( $builder_rows as $row ) {
		$target = (string) ( $row['acf_fc_layout'] ?? '' );
		if ( in_array( $target, $production_targets, true ) && ! platejka_cross_sync_row_enabled( $row ) ) {
			$enabled[] = $target;
		}
	}

	return array(
		'slug'                   => $slug,
		'post_id'                => $post_id,
		'production_layouts'     => array_values( $production_layouts ),
		'current_builder_layouts' => $current_slugs,
		'proposed_builder_layouts' => $proposed_slugs,
		'add_builder_rows'       => $additions,
		'enable_builder_rows'    => $enabled,
		'resolvable_source_rows' => $resolvable,
		'unresolved_source_rows' => $unresolved,
		'would_change'           => $builder_rows !== $proposed,
		'current_builder_rows'   => $builder_rows,
		'proposed_builder_rows'  => $proposed,
	);
}

/** @return array<int,array{slug:string,link:string}>|WP_Error */
function platejka_cross_sync_fetch_production_pages( string $base_url ) {
	$pages = array();
	$page  = 1;
	do {
		$url      = trailingslashit( $base_url ) . 'wp-json/wp/v2/pages?per_page=100&page=' . $page . '&_fields=slug,link';
		$response = wp_remote_get( $url, array( 'timeout' => 30, 'httpversion' => '1.1', 'user-agent' => 'Mozilla/5.0 Platejka cross-block manifest/1.0' ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $status || ! is_array( $data ) ) {
			return new WP_Error( 'production_pages_failed', 'Production pages endpoint returned HTTP ' . $status . '.' );
		}
		foreach ( $data as $item ) {
			if ( is_array( $item ) && ! empty( $item['slug'] ) && ! empty( $item['link'] ) ) {
				$pages[] = array( 'slug' => (string) $item['slug'], 'link' => (string) $item['link'] );
			}
		}
		$total_pages = max( 1, (int) wp_remote_retrieve_header( $response, 'x-wp-totalpages' ) );
		++$page;
	} while ( $page <= $total_pages );

	return $pages;
}

/** @param array<int,string> $only_slugs @return array<int,array<string,mixed>>|WP_Error */
function platejka_cross_sync_build_manifest( string $base_url, array $only_slugs = array() ) {
	$pages = platejka_cross_sync_fetch_production_pages( $base_url );
	if ( is_wp_error( $pages ) ) {
		return $pages;
	}

	$manifest = array();
	foreach ( $pages as $production_page ) {
		if ( ! empty( $only_slugs ) && ! in_array( $production_page['slug'], $only_slugs, true ) ) {
			continue;
		}
		$response = wp_remote_get( $production_page['link'], array( 'timeout' => 30, 'httpversion' => '1.1', 'user-agent' => 'Mozilla/5.0 Platejka cross-block manifest/1.0' ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$manifest[] = array( 'slug' => $production_page['slug'], 'fetch_error' => is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response ) );
			continue;
		}
		$layouts = platejka_cross_sync_parse_html( wp_remote_retrieve_body( $response ) );
		if ( empty( $layouts ) ) {
			continue;
		}

		$local       = get_page_by_path( $production_page['slug'], OBJECT, 'page' );
		$post_id     = $local instanceof WP_Post ? (int) $local->ID : 0;
		$builder     = $post_id && function_exists( 'get_field' ) ? get_field( 'field_platejka_sections_v1', $post_id ) : array();
		$source      = $post_id && function_exists( 'get_field' ) ? get_field( 'field_67924d166072f', $post_id ) : array();
		$page_plan   = platejka_cross_sync_plan_page( $production_page['slug'], $post_id, $layouts, is_array( $builder ) ? $builder : array(), is_array( $source ) ? $source : array() );
		$page_plan['production_url'] = $production_page['link'];
		$page_plan['local_missing']  = 0 === $post_id;
		$manifest[] = $page_plan;
	}

	return $manifest;
}

/**
 * Resolve a previously crawled read-only production association manifest against local ACF values.
 *
 * @param array<int,array<string,mixed>> $associations
 * @param array<int,string>              $only_slugs
 * @return array<int,array<string,mixed>>
 */
function platejka_cross_sync_build_imported_manifest( array $associations, array $only_slugs = array() ): array {
	$manifest = array();
	foreach ( $associations as $association ) {
		$slug    = sanitize_title( (string) ( $association['slug'] ?? '' ) );
		$layouts = is_array( $association['layouts'] ?? null ) ? array_values( array_intersect( $association['layouts'], array_keys( platejka_cross_sync_layout_map() ) ) ) : array();
		if ( '' === $slug || empty( $layouts ) || ( ! empty( $only_slugs ) && ! in_array( $slug, $only_slugs, true ) ) ) {
			continue;
		}
		$local       = get_page_by_path( $slug, OBJECT, 'page' );
		$post_id     = $local instanceof WP_Post ? (int) $local->ID : 0;
		$builder     = $post_id && function_exists( 'get_field' ) ? get_field( 'field_platejka_sections_v1', $post_id ) : array();
		$source      = $post_id && function_exists( 'get_field' ) ? get_field( 'field_67924d166072f', $post_id ) : array();
		$page_plan   = platejka_cross_sync_plan_page( $slug, $post_id, $layouts, is_array( $builder ) ? $builder : array(), is_array( $source ) ? $source : array() );
		$page_plan['production_url'] = esc_url_raw( (string) ( $association['url'] ?? '' ) );
		$page_plan['local_missing']  = 0 === $post_id;
		$manifest[] = $page_plan;
	}
	return $manifest;
}

/** @param array<int,string> $arguments @return array<string,string|bool> */
function platejka_cross_sync_parse_arguments( array $arguments ): array {
	$options = array();
	foreach ( $arguments as $argument ) {
		if ( ! is_string( $argument ) || '' === $argument ) {
			continue;
		}
		$argument = str_starts_with( $argument, '--' ) ? substr( $argument, 2 ) : $argument;
		$parts    = explode( '=', $argument, 2 );
		$options[ $parts[0] ] = $parts[1] ?? true;
	}
	return $options;
}

/** @param array<int,array<string,mixed>> $manifest */
function platejka_cross_sync_write_backup( array $manifest, string $backup_path ): void {
	if ( ! wp_is_stream( $backup_path ) && ! wp_mkdir_p( dirname( $backup_path ) ) ) {
		WP_CLI::error( 'Could not create the backup directory.' );
	}
	$backup = array();
	foreach ( $manifest as $page ) {
		if ( empty( $page['would_change'] ) || empty( $page['post_id'] ) ) {
			continue;
		}
		$post_id            = (int) $page['post_id'];
		$backup[ $post_id ] = array(
			'platejka_page_builder' => get_field( 'field_platejka_page_builder_v1', $post_id, false ),
			'platejka_sections'     => get_field( 'field_platejka_sections_v1', $post_id, false ),
		);
	}
	$written = file_put_contents( $backup_path, wp_json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	if ( false === $written ) {
		WP_CLI::error( 'Could not write the database backup file.' );
	}
}

/** @param array<int,array<string,mixed>> $manifest */
function platejka_cross_sync_apply_manifest( array $manifest, string $backup_path ): int {
	$validated = array();
	foreach ( $manifest as $page ) {
		if ( empty( $page['would_change'] ) || empty( $page['post_id'] ) || ! is_array( $page['production_layouts'] ?? null ) || ! is_array( $page['current_builder_rows'] ?? null ) || ! is_array( $page['proposed_builder_rows'] ?? null ) ) {
			continue;
		}
		$post_id = (int) $page['post_id'];
		$local   = get_page_by_path( sanitize_title( (string) ( $page['slug'] ?? '' ) ), OBJECT, 'page' );
		if ( ! $local instanceof WP_Post || (int) $local->ID !== $post_id ) {
			WP_CLI::error( 'Approved manifest page identity no longer matches for post ' . $post_id . '.' );
		}
		$current = get_field( 'field_platejka_sections_v1', $post_id );
		$current = is_array( $current ) ? $current : array();
		if ( $current !== $page['current_builder_rows'] ) {
			WP_CLI::error( 'Builder rows changed after dry-run for post ' . $post_id . '; generate a new manifest.' );
		}
		$expected = platejka_cross_sync_merge_builder_rows( $current, $page['production_layouts'] );
		if ( $expected !== $page['proposed_builder_rows'] ) {
			WP_CLI::error( 'Approved manifest target is invalid for post ' . $post_id . '.' );
		}
		$validated[] = array( 'post_id' => $post_id, 'rows' => $expected );
	}

	platejka_cross_sync_write_backup( $manifest, $backup_path );
	foreach ( $validated as $change ) {
		$post_id = $change['post_id'];
		update_field( 'field_platejka_page_builder_v1', 1, $post_id );
		update_field( 'field_platejka_sections_v1', $change['rows'], $post_id );
	}
	return count( $validated );
}

/** @param array<int,string> $arguments */
function platejka_cross_sync_main( array $arguments ): void {
	$options = platejka_cross_sync_parse_arguments( $arguments );
	if ( isset( $options['apply'] ) ) {
		if ( empty( $options['manifest'] ) || empty( $options['backup'] ) ) {
			WP_CLI::error( '--apply requires both --manifest=<approved.json> and --backup=<path.json>.' );
		}
		$manifest = json_decode( (string) file_get_contents( (string) $options['manifest'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $manifest ) ) {
			WP_CLI::error( 'Approved manifest is invalid JSON.' );
		}
		WP_CLI::success( 'Applied builder associations to ' . platejka_cross_sync_apply_manifest( $manifest, (string) $options['backup'] ) . ' pages.' );
		return;
	}

	if ( ! isset( $options['dry-run'] ) ) {
		WP_CLI::error( 'Choose --dry-run or --apply.' );
	}
	$base_url = is_string( $options['production-base'] ?? null ) ? (string) $options['production-base'] : 'https://platejka.com';
	$slugs    = is_string( $options['slugs'] ?? null ) ? array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $options['slugs'] ) ) ) ) : array();
	if ( is_string( $options['production-manifest'] ?? null ) ) {
		$associations = json_decode( (string) file_get_contents( (string) $options['production-manifest'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $associations ) ) {
			WP_CLI::error( 'Production association manifest is invalid JSON.' );
		}
		$manifest = platejka_cross_sync_build_imported_manifest( $associations, $slugs );
	} else {
		$manifest = platejka_cross_sync_build_manifest( $base_url, $slugs );
	}
	if ( is_wp_error( $manifest ) ) {
		WP_CLI::error( $manifest->get_error_message() );
	}
	$output = wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( is_string( $options['output'] ?? null ) ) {
		$written = file_put_contents( (string) $options['output'], $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $written ) {
			WP_CLI::error( 'Could not write dry-run manifest.' );
		}
	}
	WP_CLI::line( $output );
}

$platejka_cross_sync_arguments = isset( $args ) && is_array( $args ) ? $args : array();
$platejka_cross_sync_action = array_map( static fn( $argument ): string => ltrim( (string) $argument, '-' ), $platejka_cross_sync_arguments );
if ( in_array( 'dry-run', $platejka_cross_sync_action, true ) || in_array( 'apply', $platejka_cross_sync_action, true ) ) {
	platejka_cross_sync_main( $platejka_cross_sync_arguments );
}
