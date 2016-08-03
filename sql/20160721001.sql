ALTER TABLE `vms` ADD `os_type` VARCHAR(255) NOT NULL AFTER `lastused`;
UPDATE `config` SET `valuechar`='20160803001' WHERE `name`='dbversion';