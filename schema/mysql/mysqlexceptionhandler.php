<?php
class MySQLExceptionHandler extends HabariExceptionHandler {

	public function handle_exception ($exception) {
		// Find the SQL-92 error code. [?????]
		preg_match('%\[([\d\w]{5})\]%', $exception->getMessage(), $matches);
		
		switch ($matches[1]) {
			case 28000:
				echo 'MySQL: Authorization failed, please check the configuration file.';
				break;
			default:
				echo 'MySQL: An unknown exception error occurred, please verify the MySQL log files for more information.';
		}
	}

}
?>