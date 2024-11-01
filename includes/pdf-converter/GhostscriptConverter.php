<?php
namespace Xthiago\PDFVersionConverter\Converter;
use Symfony\Component\Process\Process;

/**
 * Converter that uses ghostscript to change PDF version.
 *
 */
class GhostscriptConverter implements ConverterInterface {

	/**
	 * @var GhostscriptConverterCommand
	 */
	protected static $command;

	/**
	 * @param GhostscriptConverterCommand $command
	 * @param Filesystem                  $fs
	 * @param null|string                 $tmp
	 */
	public function __construct( GhostscriptConverterCommand $command ) {
		self::$command = $command;
	}

	/**
	 * {@inheritdoc }
	 */
	public function convert( $file, $new_file, $new_version ) {
		self::$command->run( $file, $new_file, $new_version );
	}

	/**
	 * Check if GhostScript is installed.
	 *
	 * @return boolean|\WP_Error
	 */
	public static function is_gs_installed( $return_error = true ) {
		try {
			$process = new Process( 'gs --version' );
			$process->run();
			if ( ! $process->isSuccessful() ) {
				if ( $return_error ) {
					return new \WP_Error(
						'gs-is-installed-check-failed',
						sprintf( esc_html( '%s' ), $process->getErrorOutput() )
					);
				}
				return false;
			}
			$result = trim( $process->getOutput() );
			return version_compare( $result, '0.0.1', '>=' ) ? $result : false;
		} catch ( \Exception $e ) {
			if ( $return_error ) {
				$error_message = $e->getMessage();
				if ( 'The Process class relies on proc_open, which is not available on your PHP installation.' === $error_message ) {
					$error_message = esc_html__( 'proc_open() function seems not available. Any PDF file has advanced compression technique will fail for watermarking, please contact your hosting support if it can be enabled or consider upgrading to a higher hosting plan', 'watermark-pdf' );
				}
				return new \WP_Error(
					'gs-check-install-error',
					$error_message
				);
			}
			return false;
		}
	}
}
