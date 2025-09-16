<?php

if ( ! defined( 'FIN_CLI' ) || ! FIN_CLI ) {
	return;
}

FIN_CLI::add_command( 'maintenance', 'FIN_CLI\Maintenance\Maintenance_Namespace' );

FIN_CLI::add_command( 'maintenance contrib-list', 'FIN_CLI\Maintenance\Contrib_List_Command' );
FIN_CLI::add_command( 'maintenance milestones-after', 'FIN_CLI\Maintenance\Milestones_After_Command' );
FIN_CLI::add_command( 'maintenance milestones-since', 'FIN_CLI\Maintenance\Milestones_Since_Command' );
FIN_CLI::add_command( 'maintenance release', 'FIN_CLI\Maintenance\Release_Command' );
FIN_CLI::add_command( 'maintenance release-date', 'FIN_CLI\Maintenance\Release_Date_Command' );
FIN_CLI::add_command( 'maintenance release-notes', 'FIN_CLI\Maintenance\Release_Notes_Command' );
FIN_CLI::add_command( 'maintenance replace-label', 'FIN_CLI\Maintenance\Replace_Label_Command' );
