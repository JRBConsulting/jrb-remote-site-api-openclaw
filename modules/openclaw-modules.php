<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Loader for JRB Remote Site API.
 */
class JRB_Remote_Module_Loader {

	public static function init() {
		$modules = array(
			'media',
			'fluentcrm',
			'fluentsupport',
			'fluentcommunity',
			'fluentforms',
		);

		foreach ( $modules as $module ) {
			$file = plugin_dir_path( __FILE__ ) . 'openclaw-module-' . $module . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				$class = 'JRB_Remote_' . self::camel_case( $module ) . '_Module';
				if ( class_exists( $class ) ) {
					$class::init();
				}
			}
		}
	}

	private static function camel_case( $str ) {
		$parts = explode( '-', $str );
		if ( count( $parts ) === 1 ) {
			if ( strpos( $str, 'fluent' ) === 0 ) {
				$suffix = substr( $str, 6 );
				return 'Fluent' . strtoupper( $suffix );
			}
			return ucfirst( $str );
		}
		return implode( '', array_map( 'ucfirst', $parts ) );
	}

	public static function verify_token( $request ) {
		$token = $request->get_header( 'X-OpenClaw-Token' );
		if ( ! $token ) {
			$token = $request->get_param( 'token' );
		}

		$stored_token = get_option( 'jrb_remote_api_token' );

		if ( ! $stored_token || ! hash_equals( (string) $stored_token, (string) $token ) ) {
			return new WP_Error( 'rest_forbidden', 'Invalid API token', array( 'status' => 401 ) );
		}

		return true;
	}
}


