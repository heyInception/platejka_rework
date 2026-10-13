<?php
/**
 * Build a deterministic, conflict-aware migration manifest for legacy cross-blocks.
 *
 * @package Platejka_Core
 */

namespace Platejka\Core\Migration;

final class CrossBlockMigrationPlanner {
	private const DEDICATED = array(
		'wtt'              => 'cross-exchange',
		'company_metrics'  => 'cross-company-metrics',
		'external_ratings' => 'cross-external-ratings',
		'key_facts'        => 'cross-key-facts',
		'transfer_goals'   => 'cross-transfer-goals',
		'swift_problems'   => 'cross-swift-problems',
		'audience'         => 'cross-audience',
	);

	private const REUSABLE = array(
		'problems_solved' => 'problems',
		'guarantees'      => 'guarantees',
		'data_table'      => 'table',
	);

	private const REPEATERS = array(
		'company_metrics'  => array( 'metrics' ),
		'external_ratings' => array( 'platforms' ),
		'key_facts'        => array( 'facts' ),
		'transfer_goals'   => array( 'items' ),
		'swift_problems'   => array( 'items' ),
		'audience'         => array( 'items' ),
		'problems_solved'  => array( 'items' ),
		'guarantees'       => array( 'items' ),
	);

	private const MEDIA_FIELDS = array( 'icon', 'image', 'logo', 'photo', 'file' );

	/**
	 * Convert the filtered SQL snapshot into canonical production values.
	 *
	 * @param array<string,mixed> $snapshot Parsed dump snapshot.
	 * @return array{defaults:array<string,array<string,mixed>>,pages:array<string,array<string,mixed>>,warnings:array<int,array<string,mixed>>}
	 */
	public static function extractProduction( array $snapshot ): array {
		$warnings = is_array( $snapshot['warnings'] ?? null ) ? array_values( $snapshot['warnings'] ) : array();
		$defaults = array();
		$options  = is_array( $snapshot['options'] ?? null ) ? $snapshot['options'] : array();
		$layouts  = self::serializedList( $options['options_skvoznye_bloki'] ?? '' );

		foreach ( $layouts as $index => $legacy ) {
			if ( ! isset( self::DEDICATED[ $legacy ] ) ) {
				continue;
			}
			$prefix     = 'options_skvoznye_bloki_' . $index . '_';
			$visibility = self::visibility( $options, $prefix );
			if ( true !== $visibility ) {
				$warnings[] = array( 'type' => null === $visibility ? 'incomplete_layout' : 'disabled_layout', 'scope' => 'defaults', 'layout' => $legacy );
				continue;
			}
			$defaults[ self::DEDICATED[ $legacy ] ] = self::extractValues( $options, $prefix, $legacy, false );
		}
		// The source exchange calculator is static and has no options-row in the
		// production dump. Seed its only editable field explicitly.
		$defaults += array( 'cross-exchange' => array( 'title' => 'Обмен валют' ) );
		$ordered_defaults = array();
		foreach ( self::DEDICATED as $target ) {
			if ( isset( $defaults[ $target ] ) ) {
				$ordered_defaults[ $target ] = $defaults[ $target ];
			}
		}
		$defaults = $ordered_defaults;

		$pages = array();
		foreach ( is_array( $snapshot['posts'] ?? null ) ? $snapshot['posts'] : array() as $post_id => $post ) {
			if ( ! is_array( $post ) || 'publish' !== ( $post['post_status'] ?? '' ) || 'page' !== ( $post['post_type'] ?? '' ) ) {
				continue;
			}
			$slug = (string) ( $post['post_name'] ?? '' );
			if ( '' === $slug || 'test-cross-blocks' === $slug ) {
				continue;
			}
			$meta         = is_array( $snapshot['postmeta'][ $post_id ] ?? null ) ? $snapshot['postmeta'][ $post_id ] : array();
			$page_layouts = self::serializedList( $meta['skvoznye_bloki'] ?? '' );
			$approved     = array();
			foreach ( $page_layouts as $index => $legacy ) {
				$target = self::DEDICATED[ $legacy ] ?? self::REUSABLE[ $legacy ] ?? '';
				if ( '' === $target ) {
					continue;
				}
				$prefix     = 'skvoznye_bloki_' . $index . '_';
				$visibility = self::visibility( $meta, $prefix );
				if ( true !== $visibility ) {
					$warnings[] = array( 'type' => null === $visibility ? 'incomplete_layout' : 'disabled_layout', 'scope' => 'page', 'slug' => $slug, 'layout' => $legacy );
					continue;
				}
				$approved[ $target ] = array(
					'source_layout' => $legacy,
					'visibility'    => true,
					'values'        => self::adaptReusableValues( $target, self::extractValues( $meta, $prefix, $legacy, true ) ),
				);
			}
			if ( $approved ) {
				$pages[ $slug ] = array(
					'production_id' => (int) $post_id,
					'layouts'       => $approved,
				);
			}
		}

		return array( 'defaults' => $defaults, 'pages' => $pages, 'warnings' => $warnings );
	}

	/**
	 * Plan defaults and page writes against a normalized local state snapshot.
	 *
	 * @param array<string,mixed> $production Extracted production state.
	 * @param array<string,mixed> $local_state Current local ACF state.
	 * @return array<string,mixed>
	 */
	public static function plan( array $production, array $local_state ): array {
		$default_plans = array();
		$page_plans    = array();
		$conflicts     = array();
		$warnings      = is_array( $production['warnings'] ?? null ) ? array_values( $production['warnings'] ) : array();
		$local_defaults = is_array( $local_state['defaults'] ?? null ) ? $local_state['defaults'] : array();

		$global_attachments = is_array( $local_state['attachments'] ?? null ) ? $local_state['attachments'] : array();
		foreach ( is_array( $production['defaults'] ?? null ) ? $production['defaults'] : array() as $slug => $value ) {
			$current = is_array( $local_defaults[ $slug ] ?? null ) ? $local_defaults[ $slug ] : array();
			if ( self::isEmpty( $current ) ) {
				$value = self::resolveMedia( $value, '', $global_attachments, 'defaults', (string) $slug, $warnings );
				$default_plans[] = array(
					'slug'        => $slug,
					'action'      => 'seed',
					'current'     => $current,
					'target'      => $value,
					'fingerprint' => self::fingerprint( $current ),
				);
			}
		}

		$local_pages = is_array( $local_state['pages'] ?? null ) ? $local_state['pages'] : array();
		foreach ( is_array( $production['pages'] ?? null ) ? $production['pages'] : array() as $slug => $source_page ) {
			$local = $local_pages[ $slug ] ?? null;
			if ( ! is_array( $local ) || 'publish' !== ( $local['status'] ?? '' ) ) {
				$conflicts[] = array( 'type' => 'missing_local_page', 'slug' => $slug );
				continue;
			}
			$rows = $local['rows'] ?? null;
			if ( ! self::validBuilder( $rows ) ) {
				$conflicts[] = array( 'type' => 'unsafe_builder', 'slug' => $slug, 'post_id' => (int) ( $local['id'] ?? 0 ) );
				continue;
			}

			$targets = array();
			foreach ( is_array( $source_page['layouts'] ?? null ) ? $source_page['layouts'] : array() as $target_slug => $source_layout ) {
				if ( ! self::isAllowedTarget( (string) $target_slug ) || ! is_array( $source_layout ) ) {
					continue;
				}
				$defaults = is_array( $production['defaults'][ $target_slug ] ?? null )
					? $production['defaults'][ $target_slug ]
					: ( is_array( $local_defaults[ $target_slug ] ?? null ) ? $local_defaults[ $target_slug ] : array() );
				if ( 'guarantees' === $target_slug && is_array( $defaults['default'] ?? null ) ) {
					$defaults = $defaults['default'];
				}
				$existing = self::firstTargetRow( $rows, (string) $target_slug );
				$local_overrides = self::rowOverrides( $existing, (string) $target_slug );
				$targets[ $target_slug ] = self::buildOverrides(
					(string) $slug,
					(string) $target_slug,
					is_array( $source_layout['values'] ?? null ) ? $source_layout['values'] : array(),
					$defaults,
					$local_overrides,
					is_array( $local['attachments'] ?? null ) ? $local['attachments'] : array(),
					$conflicts,
					$warnings
				);
			}

			$merged = self::mergeBuilderRows( $rows, $targets );
			$current_enabled = ! empty( $local['builder_enabled'] );
			$changed = self::stable( $rows ) !== self::stable( $merged['rows'] ) || ! $current_enabled;
			if ( ! $changed ) {
				continue;
			}
			$page_plans[] = array(
				'slug'                   => $slug,
				'post_id'                => (int) ( $local['id'] ?? 0 ),
				'production_id'          => (int) ( $source_page['production_id'] ?? 0 ),
				'current_builder_enabled'=> $current_enabled,
				'target_builder_enabled' => true,
				'current_rows'           => $rows,
				'target_rows'            => $merged['rows'],
				'changes'                => array_diff_key( $merged, array( 'rows' => true ) ),
				'fingerprint'            => self::fingerprint( array( 'builder_enabled' => $current_enabled, 'rows' => $rows ) ),
			);
		}

		return array(
			'defaults'  => $default_plans,
			'pages'     => $page_plans,
			'conflicts' => $conflicts,
			'warnings'  => $warnings,
			'totals'    => array(
				'default_writes' => count( $default_plans ),
				'page_writes'    => count( $page_plans ),
				'conflicts'      => count( $conflicts ),
				'warnings'       => count( $warnings ),
			),
		);
	}

	/**
	 * Insert, enable, deduplicate and move approved targets before FAQ.
	 *
	 * @param array<int,array<string,mixed>> $rows Existing normalized builder rows.
	 * @param array<string,array<string,mixed>> $targets Target overrides keyed by builder slug.
	 * @return array<string,mixed>
	 */
	public static function mergeBuilderRows( array $rows, array $targets ): array {
		$targets = array_filter( $targets, static fn( $value, $slug ): bool => is_array( $value ) && self::isAllowedTarget( (string) $slug ), ARRAY_FILTER_USE_BOTH );
		$kept = array();
		$duplicates = array();
		$existing = array();
		foreach ( $rows as $row ) {
			$slug = (string) ( $row['acf_fc_layout'] ?? '' );
			if ( isset( $targets[ $slug ] ) ) {
				if ( isset( $existing[ $slug ] ) ) {
					$duplicates[] = $slug;
					continue;
				}
				$existing[ $slug ] = true;
			}
			$kept[] = $row;
		}

		$faq_index = self::faqIndex( $kept );
		$before = array_slice( $kept, 0, $faq_index );
		$after  = array_slice( $kept, $faq_index );
		$moved  = array();
		$added  = array();
		$enabled = array();
		$target_rows = array();

		foreach ( $targets as $slug => $overrides ) {
			$found_index = null;
			foreach ( $before as $index => $row ) {
				if ( $slug === ( $row['acf_fc_layout'] ?? '' ) ) {
					$found_index = $index;
					break;
				}
			}
			if ( null !== $found_index ) {
				$before[ $found_index ] = self::applyTarget( $before[ $found_index ], (string) $slug, $overrides, $enabled );
				continue;
			}

			$after_index = null;
			foreach ( $after as $index => $row ) {
				if ( $slug === ( $row['acf_fc_layout'] ?? '' ) ) {
					$after_index = $index;
					break;
				}
			}
			if ( null !== $after_index ) {
				$row = self::applyTarget( $after[ $after_index ], (string) $slug, $overrides, $enabled );
				array_splice( $after, $after_index, 1 );
				$target_rows[] = $row;
				$moved[] = $slug;
				continue;
			}

			$target_rows[] = self::applyTarget(
				array( 'acf_fc_layout' => $slug, 'enabled' => 0, 'anchor' => '', 'overrides' => array() ),
				(string) $slug,
				$overrides,
				$enabled
			);
			$added[] = $slug;
		}

		return array(
			'rows'               => array_values( array_merge( $before, $target_rows, $after ) ),
			'added'              => array_values( array_unique( $added ) ),
			'enabled'            => array_values( array_unique( $enabled ) ),
			'removed_duplicates' => array_values( array_unique( $duplicates ) ),
			'moved_before_faq'   => array_values( array_unique( $moved ) ),
		);
	}

	/** @param array<string,mixed> $row @param array<string,mixed> $overrides @param array<int,string> $enabled */
	private static function applyTarget( array $row, string $slug, array $overrides, array &$enabled ): array {
		if ( empty( $row['enabled'] ) ) {
			$enabled[] = $slug;
		}
		$row['enabled'] = 1;
		$row['anchor'] ??= '';
		if ( 'guarantees' === $slug ) {
			$row['variant'] ??= 'default';
			$row['overrides_default'] = is_array( $row['overrides_default'] ?? null ) ? $row['overrides_default'] : array();
			$current = is_array( $row['overrides_default']['default'] ?? null ) ? $row['overrides_default']['default'] : array();
			$row['overrides_default']['default'] = self::fillEmpty( $current, $overrides );
			return $row;
		}
		$row['overrides'] = is_array( $row['overrides'] ?? null ) ? $row['overrides'] : array();
		$group = str_replace( '-', '_', $slug );
		$current = is_array( $row['overrides'][ $group ] ?? null ) ? $row['overrides'][ $group ] : array();
		$row['overrides'][ $group ] = self::fillEmpty( $current, $overrides );
		return $row;
	}

	/** @param array<string,mixed> $source @param array<string,mixed> $fallback @return array<string,mixed> */
	private static function fillEmpty( array $source, array $fallback ): array {
		foreach ( $fallback as $key => $value ) {
			if ( ! array_key_exists( $key, $source ) || self::isEmpty( $source[ $key ] ) ) {
				$source[ $key ] = $value;
			}
		}
		return $source;
	}

	/** @param array<string,mixed> $values @param array<string,mixed> $defaults @param array<string,mixed> $local @param array<int,int|string> $attachments @param array<int,array<string,mixed>> $conflicts @param array<int,array<string,mixed>> $warnings @return array<string,mixed> */
	private static function buildOverrides( string $page, string $slug, array $values, array $defaults, array $local, array $attachments, array &$conflicts, array &$warnings ): array {
		$result = array();
		foreach ( $values as $key => $value ) {
			if ( self::equivalent( $value, $defaults[ $key ] ?? null ) ) {
				continue;
			}
			$value = self::resolveMedia( $value, (string) $key, $attachments, $page, $slug, $warnings );
			if ( array_key_exists( $key, $local ) && ! self::isEmpty( $local[ $key ] ) && ! self::equivalent( $local[ $key ], $value ) ) {
				$conflicts[] = array( 'type' => 'local_override_conflict', 'slug' => $page, 'section' => $slug, 'field' => $key );
				$result[ $key ] = $local[ $key ];
				continue;
			}
			$result[ $key ] = $value;
		}
		return $result;
	}

	/** @param mixed $value @param array<int,int|string> $attachments @param array<int,array<string,mixed>> $warnings @return mixed */
	private static function resolveMedia( $value, string $field, array $attachments, string $page, string $slug, array &$warnings ) {
		if ( is_array( $value ) ) {
			$result = array();
			foreach ( $value as $key => $child ) {
				$result[ $key ] = self::resolveMedia( $child, (string) $key, $attachments, $page, $slug, $warnings );
			}
			return $result;
		}
		if ( ! in_array( $field, self::MEDIA_FIELDS, true ) || ! is_numeric( $value ) || (int) $value < 1 ) {
			return $value;
		}
		$id = (int) $value;
		if ( isset( $attachments[ $id ] ) && (int) $attachments[ $id ] > 0 ) {
			return (int) $attachments[ $id ];
		}
		$warnings[] = array( 'type' => 'unresolved_attachment', 'slug' => $page, 'section' => $slug, 'field' => $field, 'production_id' => $id );
		return '';
	}

	/** @param array<string,string> $flat @return array<string,mixed> */
	private static function extractValues( array $flat, string $prefix, string $legacy, bool $omit_empty ): array {
		$raw = array();
		foreach ( $flat as $key => $value ) {
			if ( ! is_string( $key ) || str_starts_with( $key, '_' ) || ! str_starts_with( $key, $prefix ) ) {
				continue;
			}
			$name = substr( $key, strlen( $prefix ) );
			if ( in_array( $name, array( 'show_block', 'vklyuchit_blok' ), true ) ) {
				continue;
			}
			$raw[ $name ] = self::maybeUnserialize( $value );
		}

		$result = array();
		$repeaters = self::REPEATERS[ $legacy ] ?? array();
		foreach ( $repeaters as $repeater ) {
			$count = isset( $raw[ $repeater ] ) && is_numeric( $raw[ $repeater ] ) ? (int) $raw[ $repeater ] : 0;
			$items = array();
			for ( $index = 0; $index < $count; ++$index ) {
				$item = array();
				$item_prefix = $repeater . '_' . $index . '_';
				foreach ( $raw as $name => $value ) {
					if ( str_starts_with( $name, $item_prefix ) ) {
						$item[ substr( $name, strlen( $item_prefix ) ) ] = $value;
					}
				}
				$items[] = $item;
			}
			if ( $items || ! $omit_empty ) {
				$result[ $repeater ] = $items;
			}
		}

		foreach ( $raw as $name => $value ) {
			$belongs_to_repeater = false;
			foreach ( $repeaters as $repeater ) {
				if ( $name === $repeater || str_starts_with( $name, $repeater . '_' ) ) {
					$belongs_to_repeater = true;
					break;
				}
			}
			if ( $belongs_to_repeater || ( $omit_empty && self::isEmpty( $value ) ) ) {
				continue;
			}
			$result[ $name ] = $value;
		}
		return $result;
	}

	/** @param mixed $raw @return array<int,string> */
	private static function serializedList( $raw ): array {
		$value = self::maybeUnserialize( $raw );
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $value ), static fn( string $item ): bool => '' !== $item ) );
	}

	/** @param mixed $value @return mixed */
	private static function maybeUnserialize( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}
		$decoded = @unserialize( $value, array( 'allowed_classes' => false ) );
		return false === $decoded && 'b:0;' !== $value ? $value : $decoded;
	}

	/** @param array<string,mixed> $values */
	private static function visibility( array $values, string $prefix ): ?bool {
		foreach ( array( 'show_block', 'vklyuchit_blok' ) as $name ) {
			if ( array_key_exists( $prefix . $name, $values ) ) {
				return in_array( $values[ $prefix . $name ], array( true, 1, '1', 'on', 'yes' ), true );
			}
		}
		return null;
	}

	/** @param mixed $rows */
	private static function validBuilder( $rows ): bool {
		if ( ! is_array( $rows ) || array() === $rows ) {
			return false;
		}
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! is_string( $row['acf_fc_layout'] ?? null ) || '' === $row['acf_fc_layout'] ) {
				return false;
			}
		}
		return true;
	}

	/** @param array<int,array<string,mixed>> $rows @return array<string,mixed> */
	private static function firstTargetRow( array $rows, string $slug ): array {
		foreach ( $rows as $row ) {
			if ( $slug === ( $row['acf_fc_layout'] ?? '' ) ) {
				return $row;
			}
		}
		return array();
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private static function rowOverrides( array $row, string $slug ): array {
		if ( 'guarantees' === $slug ) {
			$container = is_array( $row['overrides_default'] ?? null ) ? $row['overrides_default'] : array();
			return is_array( $container['default'] ?? null ) ? $container['default'] : array();
		}
		$overrides = is_array( $row['overrides'] ?? null ) ? $row['overrides'] : array();
		$group = str_replace( '-', '_', $slug );
		return is_array( $overrides[ $group ] ?? null ) ? $overrides[ $group ] : $overrides;
	}

	/** @param array<string,mixed> $values @return array<string,mixed> */
	private static function adaptReusableValues( string $target, array $values ): array {
		if ( ! in_array( $target, array( 'problems', 'guarantees' ), true ) ) {
			return $values;
		}
		$adapted = array();
		if ( array_key_exists( 'title', $values ) ) {
			$adapted['title'] = $values['title'];
		}
		if ( array_key_exists( 'intro', $values ) ) {
			$adapted['description'] = $values['intro'];
		}
		if ( is_array( $values['items'] ?? null ) ) {
			$adapted['items'] = array_map(
				static function ( $item ): array {
					if ( ! is_array( $item ) ) {
						return array();
					}
					return array(
						'title' => (string) ( $item['title'] ?? '' ),
						'text'  => (string) ( $item['text'] ?? '' ),
						'image' => $item['icon'] ?? '',
					);
				},
				$values['items']
			);
		}
		return $adapted;
	}

	/** @param array<int,array<string,mixed>> $rows */
	private static function faqIndex( array $rows ): int {
		foreach ( $rows as $index => $row ) {
			if ( 'faq' === ( $row['acf_fc_layout'] ?? '' ) ) {
				return $index;
			}
		}
		return count( $rows );
	}

	private static function isAllowedTarget( string $slug ): bool {
		return in_array( $slug, array_merge( array_values( self::DEDICATED ), array_values( self::REUSABLE ) ), true );
	}

	/** @param mixed $value */
	private static function isEmpty( $value ): bool {
		return null === $value || false === $value || '' === $value || array() === $value;
	}

	/** @param mixed $left @param mixed $right */
	private static function equivalent( $left, $right ): bool {
		if ( is_array( $left ) || is_array( $right ) ) {
			return self::stable( $left ) === self::stable( $right );
		}
		if ( is_bool( $left ) || is_bool( $right ) || is_numeric( $left ) && is_numeric( $right ) ) {
			return (string) (float) $left === (string) (float) $right;
		}
		return $left === $right;
	}

	/** @param mixed $value */
	private static function fingerprint( $value ): string {
		return hash( 'sha256', self::stable( $value ) );
	}

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION );
	}
}
