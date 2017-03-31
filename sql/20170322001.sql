ALTER TABLE `vms` ADD `osInstanceMasterVolume` VARCHAR(255) NOT NULL DEFAULT '' AFTER `osInstancePort`;
UPDATE `config` SET `valuechar`='20170331001' WHERE `name`='dbversion';