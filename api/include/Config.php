<?php
/**
 * Database configuration
 */
define('DB_USERNAME', 'type_username');
define('DB_PASSWORD', 'type_password');
define('DB_HOST', 'type_host_name');
define('DB_NAME', 'type_DB_name');

define('SUCCESS', 0);
define('FAILED', 1);


define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);

// ALTER TABLE `chiti` ADD `ccomm` INT(10) NOT NULL AFTER `interestrate`;
// ALTER TABLE `chiti` ADD `suriccomm` INT(10) NOT NULL AFTER `ccomm`;
// ALTER TABLE `chiti` ADD `suri` INT(10) NOT NULL AFTER `sowji`;
// ALTER TABLE `collection` ADD `suri` INT(10) NOT NULL AFTER `sowji`;
// add suri comm in suri ledger and daybook
// daily chiti - work -> first check asalu in daybook , is there or not then reinsert sql data >slice

//ALTER TABLE `collection` ADD `notes` VARCHAR(500) NOT NULL AFTER `amount`;


//
?>
