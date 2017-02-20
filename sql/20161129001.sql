ALTER TABLE `users` ADD `config` TEXT default '' AFTER `lastlogin`;
UPDATE `config` SET `valuechar`='20170220001' WHERE `name`='dbversion';