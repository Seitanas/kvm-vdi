ALTER TABLE `clients` ADD `isdomain` INT NOT NULL DEFAULT '0' AFTER `ip`;
UPDATE `config` SET `valuechar`='20160720001' WHERE `name`='dbversion';