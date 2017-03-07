ALTER TABLE `vms` ADD `mac` VARCHAR(255) NOT NULL DEFAULT '' AFTER `locked`;
UPDATE `config` SET `valuechar`='20170306001' WHERE `name`='dbversion';