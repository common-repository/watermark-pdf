<?php

namespace Xthiago\PDFVersionConverter\Converter;

use Symfony\Component\Process\Process;

/**
 * Encapsulates the knowledge about gs command.
 *
 * @author Thiago Rodrigues <xthiago@gmail.com>
 */
class GhostscriptConverterCommand {

	/**
	 * Timeout in Seconds.
	 * Default 10min for big PDF Files.
	 *
	 * @var int
	 */
	private $timeout_in_sec = 10 * 60 * 60;

	/**
	 * @var string
	 */
	protected $base_command = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=%s -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -dColorConversionStrategy=/LeaveColorUnchanged -dEncodeColorImages=false -dEncodeGrayImages=false -dEncodeMonoImages=false -dDownsampleMonoImages=false -dDownsampleGrayImages=false -dDownsampleColorImages=false -dAutoFilterColorImages=false -dAutoFilterGrayImages=false -dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode  -sOutputFile=%s %s';

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Run Ghost Script Converter Command.
	 *
	 * @param string $original_file
	 * @param string $new_file
	 * @param string $new_version
	 * @return void
	 */
	public function run( $original_file, $new_file, $new_version ) {
		$command = sprintf( $this->base_command, $new_version, $new_file, escapeshellarg( $original_file ) );

		$process = new Process( $command );
		$process->setTimeout( $this->timeout_in_sec );
		$process->run();

		if ( ! $process->isSuccessful() ) {
			throw new \RuntimeException( $process->getErrorOutput() );
		}
	}
}
