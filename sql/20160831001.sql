ALTER TABLE `config` CHANGE `valuechar` `valuechar` VARCHAR(255) NOT NULL DEFAULT '';
UPDATE `config` SET `valuechar`='20161129001' WHERE `name`='dbversion';