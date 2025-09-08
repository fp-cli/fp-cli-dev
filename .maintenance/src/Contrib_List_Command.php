<?php namespace FP_CLI\Maintenance;

use FP_CLI;
use FP_CLI\Utils;

final class Contrib_List_Command {

	/**
	 * Lists all contributors to this release.
	 *
	 * Run within the main FP-CLI project repository.
	 *
	 * ## OPTIONS
	 *
	 * [<repo>]
	 * : Name of the repository to fetch the release notes for. If no user/org
	 * was provided, 'fp-cli' org is assumed. If no repo is passed, release
	 * notes for the entire org state since the last bundle release are fetched.
	 *
	 * [<milestone>...]
	 * : Name of one or more milestones to fetch the release notes for. If none
	 * are passed, the current open one is assumed.
	 *
	 * [--format=<format>]
	 * : Render output in a specific format.
	 * ---
	 * default: markdown
	 * options:
	 *   - markdown
	 *   - html
	 * ---
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {
		$repos      = null;
		$use_bundle = false;

		$ignored_contributors = [
			'github-actions[bot]',
		];

		if ( count( $args ) > 0 ) {
			$repos = [ array_shift( $args ) ];
		}

		$milestone_names = $args;

		if ( empty( $repos ) ) {
			$use_bundle = true;
			$repos      = [
				'fp-cli/fp-cli-bundle',
				'fp-cli/fp-cli',
				'fp-cli/handbook',
				'fp-cli/fp-cli.github.com',
			];
		}

		$contributors       = array();
		$pull_request_count = 0;

		// Get the contributors to the current open large project milestones
		foreach ( $repos as $repo ) {
			if ( $milestone_names ) {
				$milestone_names = (array) $milestone_names;

				$potential_milestones = GitHub::get_project_milestones(
					$repo,
					array( 'state' => 'all' )
				);

				$milestones = array();
				foreach ( $potential_milestones as $potential_milestone ) {
					if ( in_array(
						$potential_milestone->title,
						$milestone_names,
						true
					) ) {
						$milestones[] = $potential_milestone;
						$index        = array_search(
							$potential_milestone->title,
							$milestone_names,
							true
						);
						unset( $milestone_names[ $index ] );
					}
				}

				if ( ! empty( $milestone_names ) ) {
					FP_CLI::warning(
						sprintf(
							"Couldn't find the requested milestone(s) '%s' in repository '%s'.",
							implode( "', '", $milestone_names ),
							$repo
						)
					);
				}
			} else {
				$milestones = GitHub::get_project_milestones( $repo );
				// Cheap way to get the latest milestone
				$milestone = array_shift( $milestones );
				if ( ! $milestone ) {
					continue;
				}
			}
			$entries = array();
			foreach ( $milestones as $milestone ) {
				FP_CLI::debug( "Using milestone '{$milestone->title}' for repo '{$repo}'", 'release-notes' );
				FP_CLI::log( 'Current open ' . $repo . ' milestone: ' . $milestone->title );
				$pull_requests     = GitHub::get_project_milestone_pull_requests( $repo, $milestone->number );
				$repo_contributors = GitHub::parse_contributors_from_pull_requests( $pull_requests );
				FP_CLI::log( ' - Contributors: ' . count( $repo_contributors ) );
				FP_CLI::log( ' - Pull requests: ' . count( $pull_requests ) );
				$pull_request_count += count( $pull_requests );
				$contributors        = array_merge( $contributors, $repo_contributors );
			}
		}

		if ( $use_bundle ) {
			// Identify all command dependencies and their contributors

			$bundle = 'fp-cli/fp-cli-bundle';

			$milestones = GitHub::get_project_milestones( $bundle, array( 'state' => 'closed' ) );
			$milestone  = array_reduce(
				$milestones,
				function ( $tag, $milestone ) {
					if ( ! $tag ) {
						return $milestone->title;
					}
					return version_compare( $milestone->title, $tag, '>' ) ? $milestone->title : $tag;
				}
			);
			$tag        = ! empty( $milestone ) ? "v{$milestone}" : GitHub::get_default_branch( $bundle );

			$composer_lock_url = sprintf( 'https://raw.githubusercontent.com/%s/%s/composer.lock', $bundle, $tag );
			FP_CLI::log( 'Fetching ' . $composer_lock_url );
			$response = Utils\http_request( 'GET', $composer_lock_url );
			if ( 200 !== $response->status_code ) {
				FP_CLI::error( sprintf( 'Could not fetch composer.json (HTTP code %d)', $response->status_code ) );
			}
			$composer_json = json_decode( $response->body, true );

			// TODO: Only need for initial v2.
			$composer_json['packages'][] = array(
				'name'    => 'fp-cli/i18n-command',
				'version' => 'v2',
			);
			usort(
				$composer_json['packages'],
				function ( $a, $b ) {
					return $a['name'] < $b['name'] ? -1 : 1;
				}
			);

			foreach ( $composer_json['packages'] as $package ) {
				$package_name       = $package['name'];
				$version_constraint = str_replace( 'v', '', $package['version'] );
				if ( ! preg_match( '#^fp-cli/.+-command$#', $package_name )
					&& ! in_array(
						$package_name,
						array(
							'fp-cli/fp-cli-tests',
							'fp-cli/regenerate-readme',
							'fp-cli/autoload-splitter',
							'fp-cli/fp-config-transformer',
							'fp-cli/php-cli-tools',
							'fp-cli/spyc',
						),
						true
					) ) {
					continue;
				}
				// Closed milestones denote a tagged release
				$milestones       = GitHub::get_project_milestones( $package_name, array( 'state' => 'closed' ) );
				$milestone_ids    = array();
				$milestone_titles = array();
				foreach ( $milestones as $milestone ) {
					if ( ! version_compare( $milestone->title, $version_constraint, '>' ) ) {
						continue;
					}
					$milestone_ids[]    = $milestone->number;
					$milestone_titles[] = $milestone->title;
				}
				// No shipped releases for this milestone.
				if ( empty( $milestone_ids ) ) {
					continue;
				}
				FP_CLI::log( 'Closed ' . $package_name . ' milestone(s): ' . implode( ', ', $milestone_titles ) );
				foreach ( $milestone_ids as $milestone_id ) {
					$pull_requests     = GitHub::get_project_milestone_pull_requests( $package_name, $milestone_id );
					$repo_contributors = GitHub::parse_contributors_from_pull_requests( $pull_requests );
					FP_CLI::log( ' - Contributors: ' . count( $repo_contributors ) );
					FP_CLI::log( ' - Pull requests: ' . count( $pull_requests ) );
					$pull_request_count += count( $pull_requests );
					$contributors        = array_merge( $contributors, $repo_contributors );
				}
			}
		}

		$contributors = array_diff( $contributors, $ignored_contributors );

		FP_CLI::log( 'Total contributors: ' . count( $contributors ) );
		FP_CLI::log( 'Total pull requests: ' . $pull_request_count );

		// Sort and render the contributor list
		asort( $contributors, SORT_NATURAL | SORT_FLAG_CASE );
		if ( in_array( $assoc_args['format'], array( 'markdown', 'html' ), true ) ) {
			$contrib_list = '';
			foreach ( $contributors as $url => $login ) {
				if ( 'markdown' === $assoc_args['format'] ) {
					$contrib_list .= '[@' . $login . '](' . $url . '), ';
				} elseif ( 'html' === $assoc_args['format'] ) {
					$contrib_list .= '<a href="' . $url . '">@' . $login . '</a>, ';
				}
			}
			$contrib_list = rtrim( $contrib_list, ', ' );
			FP_CLI::log( $contrib_list );
		}
	}
}
