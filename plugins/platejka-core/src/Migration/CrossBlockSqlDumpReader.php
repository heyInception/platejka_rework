<?php
/**
 * Read the legacy cross-block subset from a MySQL dump without executing SQL.
 *
 * @package Platejka_Core
 */

namespace Platejka\Core\Migration;

use RuntimeException;

final class CrossBlockSqlDumpReader {
	private string $path;

	public function __construct( string $path ) {
		$this->path = $path;
	}

	/**
	 * @return array{
	 *     options:array<string,string>,
	 *     posts:array<int,array{ID:int,post_name:string,post_status:string,post_type:string}>,
	 *     postmeta:array<int,array<string,string>>,
	 *     warnings:array<int,array<string,mixed>>
	 * }
	 */
	public function read(): array {
		if ( ! is_readable( $this->path ) || ! is_file( $this->path ) ) {
			throw new RuntimeException( 'SQL dump is not readable: ' . $this->path );
		}

		$handle = fopen( $this->path, 'rb' );
		if ( false === $handle ) {
			throw new RuntimeException( 'SQL dump could not be opened: ' . $this->path );
		}

		$snapshot = array(
			'options'  => array(),
			'posts'    => array(),
			'postmeta' => array(),
			'warnings' => array(),
		);
		$state = $this->initialStatementState();

		try {
			while ( false !== ( $line = fgets( $handle ) ) ) {
				if ( ! $state['active'] ) {
					if ( ! preg_match( '/^\s*INSERT\s+INTO\s+\x60?([^\x60\s]+)\x60?\s*\(([^)]*)\)\s+VALUES\s*(.*)$/i', $line, $matches ) ) {
						continue;
					}

					$state = array(
						'active'    => true,
						'table'     => $this->tableType( $matches[1] ),
						'columns'   => $this->parseColumns( $matches[2] ),
						'in_tuple'  => false,
						'depth'     => 0,
						'in_string' => false,
						'escaped'   => false,
						'tuple'     => '',
					);
					$this->consumeValues( $matches[3] . "\n", $state, $snapshot );
					continue;
				}

				$this->consumeValues( $line, $state, $snapshot );
			}
		} finally {
			fclose( $handle );
		}

		if ( $state['active'] ) {
			$snapshot['warnings'][] = array( 'type' => 'unterminated_insert', 'table' => $state['table'] );
		}

		ksort( $snapshot['posts'] );
		ksort( $snapshot['postmeta'] );
		return $snapshot;
	}

	/** @return array<string,mixed> */
	private function initialStatementState(): array {
		return array(
			'active'    => false,
			'table'     => '',
			'columns'   => array(),
			'in_tuple'  => false,
			'depth'     => 0,
			'in_string' => false,
			'escaped'   => false,
			'tuple'     => '',
		);
	}

	/**
	 * @param array<string,mixed> $state Statement state.
	 * @param array<string,mixed> $snapshot Parsed output.
	 */
	private function consumeValues( string $chunk, array &$state, array &$snapshot ): void {
		$length = strlen( $chunk );
		for ( $index = 0; $index < $length; ++$index ) {
			$character = $chunk[ $index ];

			if ( $state['in_tuple'] ) {
				if ( $state['in_string'] ) {
					if ( $state['escaped'] ) {
						$state['escaped'] = false;
					} elseif ( '\\' === $character ) {
						$state['escaped'] = true;
					} elseif ( "'" === $character ) {
						$state['in_string'] = false;
					}
				} elseif ( "'" === $character ) {
					$state['in_string'] = true;
				} elseif ( '(' === $character ) {
					++$state['depth'];
				} elseif ( ')' === $character ) {
					--$state['depth'];
					if ( 0 === $state['depth'] ) {
						if ( '' !== $state['table'] ) {
							$this->consumeTuple( $state['table'], $state['columns'], $state['tuple'], $snapshot );
						}
						$state['in_tuple']  = false;
						$state['in_string'] = false;
						$state['escaped']   = false;
						$state['tuple']     = '';
						continue;
					}
				}

				if ( '' !== $state['table'] ) {
					$state['tuple'] .= $character;
				}
				continue;
			}

			if ( '(' === $character ) {
				$state['in_tuple']  = true;
				$state['depth']     = 1;
				$state['in_string'] = false;
				$state['escaped']   = false;
				$state['tuple']     = '';
				continue;
			}

			if ( ';' === $character ) {
				$state = $this->initialStatementState();
				return;
			}
		}
	}

	/**
	 * @param array<int,string>   $columns Column names.
	 * @param array<string,mixed> $snapshot Parsed output.
	 */
	private function consumeTuple( string $table, array $columns, string $tuple, array &$snapshot ): void {
		$values = $this->parseTuple( $tuple );
		if ( null === $values || count( $values ) !== count( $columns ) ) {
			$snapshot['warnings'][] = array(
				'type'   => 'malformed_tuple',
				'table'  => $table,
				'values' => null === $values ? null : count( $values ),
			);
			return;
		}

		$row = array_combine( $columns, $values );
		if ( false === $row ) {
			return;
		}

		if ( 'options' === $table ) {
			$key = (string) ( $row['option_name'] ?? '' );
			if ( ! $this->isLegacyKey( $key, true ) ) {
				return;
			}
			if ( array_key_exists( $key, $snapshot['options'] ) ) {
				$snapshot['warnings'][] = array( 'type' => 'duplicate_option', 'key' => $key );
			}
			$snapshot['options'][ $key ] = (string) ( $row['option_value'] ?? '' );
			return;
		}

		if ( 'postmeta' === $table ) {
			$post_id = (int) ( $row['post_id'] ?? 0 );
			$key     = (string) ( $row['meta_key'] ?? '' );
			if ( $post_id < 1 || ! $this->isLegacyKey( $key, false ) ) {
				return;
			}
			if ( isset( $snapshot['postmeta'][ $post_id ] ) && array_key_exists( $key, $snapshot['postmeta'][ $post_id ] ) ) {
				$snapshot['warnings'][] = array( 'type' => 'duplicate_postmeta', 'post_id' => $post_id, 'key' => $key );
			}
			$snapshot['postmeta'][ $post_id ][ $key ] = (string) ( $row['meta_value'] ?? '' );
			return;
		}

		$post_id   = (int) ( $row['ID'] ?? 0 );
		$post_type = (string) ( $row['post_type'] ?? '' );
		if ( $post_id < 1 || 'page' !== $post_type ) {
			return;
		}
		$snapshot['posts'][ $post_id ] = array(
			'ID'          => $post_id,
			'post_name'   => (string) ( $row['post_name'] ?? '' ),
			'post_status' => (string) ( $row['post_status'] ?? '' ),
			'post_type'   => $post_type,
		);
	}

	/** @return array<int,string|null>|null */
	private function parseTuple( string $tuple ): ?array {
		$values    = array();
		$value     = '';
		$in_string = false;
		$quoted    = false;
		$length    = strlen( $tuple );

		for ( $index = 0; $index < $length; ++$index ) {
			$character = $tuple[ $index ];
			if ( $in_string ) {
				if ( '\\' === $character ) {
					if ( $index + 1 >= $length ) {
						return null;
					}
					$value .= $this->decodeEscape( $tuple[ ++$index ] );
				} elseif ( "'" === $character ) {
					$in_string = false;
				} else {
					$value .= $character;
				}
				continue;
			}

			if ( "'" === $character ) {
				$in_string = true;
				$quoted    = true;
				continue;
			}
			if ( ',' === $character ) {
				$values[] = $this->normalizeValue( $value, $quoted );
				$value    = '';
				$quoted   = false;
				continue;
			}
			if ( ctype_space( $character ) ) {
				continue;
			}
			$value .= $character;
		}

		if ( $in_string ) {
			return null;
		}
		$values[] = $this->normalizeValue( $value, $quoted );
		return $values;
	}

	/** @return string|null */
	private function normalizeValue( string $value, bool $quoted ) {
		if ( $quoted ) {
			return $value;
		}
		$value = trim( $value );
		return 'NULL' === strtoupper( $value ) ? null : $value;
	}

	private function decodeEscape( string $character ): string {
		$map = array(
			'0'  => "\0",
			'b'  => "\x08",
			'n'  => "\n",
			'r'  => "\r",
			't'  => "\t",
			'Z'  => "\x1a",
			'\\' => '\\',
			"'"  => "'",
			'"'  => '"',
			'%'  => '%',
			'_'  => '_',
		);
		return $map[ $character ] ?? $character;
	}

	/** @return array<int,string> */
	private function parseColumns( string $columns ): array {
		return array_map(
			static fn( string $column ): string => trim( $column, " \t\n\r\0\x0B" . chr( 96 ) ),
			explode( ',', $columns )
		);
	}

	private function tableType( string $table ): string {
		foreach ( array( 'postmeta', 'options', 'posts' ) as $type ) {
			if ( preg_match( '/(?:^|_)' . $type . '$/i', $table ) ) {
				return $type;
			}
		}
		return '';
	}

	private function isLegacyKey( string $key, bool $option ): bool {
		$prefix = $option ? 'options_skvoznye_bloki' : 'skvoznye_bloki';
		return str_starts_with( $key, $prefix ) || str_starts_with( $key, '_' . $prefix );
	}
}
