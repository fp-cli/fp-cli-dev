<?php namespace FP_CLI\Maintenance;

use FP_CLI;

final class Milestones_After_Command {

	/**
	 * Retrieves the milestones that were closed after a given milestone.
	 *
	 * ## OPTIONS
	 *
	 * <repo>
	 * : Name of the repository to fetch the milestones for.
	 *
	 * <milestone>
	 * : Milestone to serve as treshold.
	 *
	 * @when before_fp_load
	 */
	public function __invoke( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		list( $repo, $milestone_name ) = $args;

		if ( false === strpos( $repo, '/' ) ) {
			$repo = "fp-cli/{$repo}";
		}

		$threshold_reached = false;
		$milestones        = array_filter(
			GitHub::get_project_milestones(
				$repo,
				array( 'state' => 'closed' )
			),
			function ( $milestone ) use (
				$milestone_name,
				&$threshold_reached
			) {
				if ( $threshold_reached ) {
					return true;
				}

				if ( $milestone->title === $milestone_name ) {
					$threshold_reached = true;
				}

				return false;
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
