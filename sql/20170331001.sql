ALTER TABLE `vms` ADD `osInstanceNetworks` VARCHAR(255) NOT NULL DEFAULT '' AFTER `osInstanceMasterVolume`;
UPDATE `config` SET `valuechar`='20170407001' WHERE `name`='dbversion';