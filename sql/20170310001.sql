ALTER TABLE `vms` ADD `osInstancePort` VARCHAR(255) NOT NULL DEFAULT '' AFTER `osInstanceId`;
UPDATE `config` SET `valuechar`='20170322001' WHERE `name`='dbversion';