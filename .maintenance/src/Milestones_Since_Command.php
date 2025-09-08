<?php namespace FP_CLI\Maintenance;

use DateTime;
use FP_CLI;

final class Milestones_Since_Command {

	/**
	 * Retrieves the milestones that were closed for a given repository after a
	 * specific date treshold.
	 *
	 * ## OPTIONS
	 *
	 * <repo>
	 * : Name of the repository to fetch the milestones for.
	 *
	 * <date>
	 * : Threshold date to filter by.
	 *
	 * @when before_fp_load
	 */
	public function __invoke( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		list( $repo, $date ) = $args;

		if ( false === strpos( $repo, '/' ) ) {
			$repo = "fp-cli/{$repo}";
		}

		$date = new DateTime( $date );

		$milestones = array_filter(
			GitHub::get_project_milestones(
				$repo,
				array( 'state' => 'closed' )
			),
			function ( $milestone ) use ( $date ) {
				$closed = new DateTime( $milestone->closed_at );
				return $closed > $date;
			}
		);

		$milestone_titles = array_map(
			function ( $milestone ) {
				return $milestone->title; },
			$milestones
		);

		FP_CLI::log( implode( ' ', $milestone_titles ) );
	}
}
