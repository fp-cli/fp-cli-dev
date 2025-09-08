<?php

if ( ! defined( 'FP_CLI' ) || ! FP_CLI ) {
	return;
}

FP_CLI::add_command( 'maintenance', 'FP_CLI\Maintenance\Maintenance_Namespace' );

FP_CLI::add_command( 'maintenance contrib-list', 'FP_CLI\Maintenance\Contrib_List_Command' );
FP_CLI::add_command( 'maintenance milestones-after', 'FP_CLI\Maintenance\Milestones_After_Command' );
FP_CLI::add_command( 'maintenance milestones-since', 'FP_CLI\Maintenance\Milestones_Since_Command' );
FP_CLI::add_command( 'maintenance release', 'FP_CLI\Maintenance\Release_Command' );
FP_CLI::add_command( 'maintenance release-date', 'FP_CLI\Maintenance\Release_Date_Command' );
FP_CLI::add_command( 'maintenance release-notes', 'FP_CLI\Maintenance\Release_Notes_Command' );
FP_CLI::add_command( 'maintenance replace-label', 'FP_CLI\Maintenance\Replace_Label_Command' );
