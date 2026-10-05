<?php
/**
 * Public read-only transfer calculation endpoint.
 *
 * @package PlatejkaCore
 */

namespace Platejka\Core\Rest;

use Platejka\Core\Calculator\Calculator;

/** Validate input at the REST boundary and expose the core result. */
final class CalculationController extends \WP_REST_Controller {
	/** @var Calculator|null Explicit calculator, otherwise use current rate source. */
	private ?Calculator $calculator;

	/** @param Calculator|null $calculator Calculator with a controlled rate map. */
	public function __construct( ?Calculator $calculator = null ) {
		$this->namespace  = 'platejka/v1';
		$this->rest_base  = 'calculation';
		$this->calculator = $calculator;
	}

	/** Register a public POST route on rest_api_init. */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'currency' => array(
							'type'              => 'string',
							'required'          => true,
							'enum'              => array( 'USD', 'EUR', 'CNY' ),
							'validate_callback' => array( $this, 'validateCurrency' ),
							'sanitize_callback' => array( $this, 'sanitizeCurrency' ),
						),
						'amount'   => array(
							'type'              => 'number',
							'required'          => true,
							'minimum'           => 0,
							'exclusiveMinimum'  => true,
							'validate_callback' => array( $this, 'validateAmount' ),
							'sanitize_callback' => 'rest_sanitize_request_arg',
						),
					),
				),
			)
		);
	}

	/**
	 * @param mixed            $value   Input currency.
	 * @param \WP_REST_Request $request Request.
	 * @param string           $param   Argument name.
	 * @return true|\WP_Error Schema validation result.
	 */
	public function validateCurrency( $value, \WP_REST_Request $request, string $param ) {
		return rest_validate_request_arg( is_string( $value ) ? strtoupper( $value ) : $value, $request, $param );
	}

	/**
	 * @param string           $value   Valid currency.
	 * @param \WP_REST_Request $request Request.
	 * @param string           $param   Argument name.
	 * @return string Normalized currency.
	 */
	public function sanitizeCurrency( string $value, \WP_REST_Request $request, string $param ): string {
		return rest_sanitize_request_arg( strtoupper( $value ), $request, $param );
	}

	/**
	 * @param mixed            $value   Input amount.
	 * @param \WP_REST_Request $request Request.
	 * @param string           $param   Argument name.
	 * @return true|\WP_Error Validation result.
	 */
	public function validateAmount( $value, \WP_REST_Request $request, string $param ) {
		$valid = rest_validate_request_arg( $value, $request, $param );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( ! is_finite( (float) $value ) ) {
			return new \WP_Error( 'invalid_amount', __( 'Amount must be a finite positive number.', 'platejka-core' ), array( 'status' => 400 ) );
		}
		return true;
	}

	/**
	 * @param \WP_REST_Request $request Validated request.
	 * @return \WP_REST_Response|\WP_Error Calculation response.
	 */
	public function create_item( $request ) {
		$currency = (string) $request->get_param( 'currency' );
		$amount   = (float) $request->get_param( 'amount' );
		$result   = null === $this->calculator
			? platejka_core_calculate( $currency, $amount )
			: $this->calculator->calculate( $currency, $amount );
		if ( isset( $result['error'] ) ) {
			$error = $result['error'];
			return new \WP_Error( $error['code'], $error['message'], array( 'status' => 'rate_unavailable' === $error['code'] ? 503 : 400 ) );
		}
		return rest_ensure_response( $result );
	}
}
