ALTER TABLE `vms` ADD `locked` VARCHAR(11) NOT NULL DEFAULT 'false' AFTER `os_type`;
UPDATE `config` SET `valuechar`='20160831001' WHERE `name`='dbversion';