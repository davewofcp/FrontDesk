CREATE TRIGGER `issue_inv_insert` AFTER INSERT ON `issue_inv`
FOR EACH ROW
thisTrigger: BEGIN
  IF ((@TRIGGER_CHECKS = 0) OR (@TRIGGER_AFTER_INSERT_CHECKS = 0))
  AND (EXISTS(
      SELECT *
      FROM `mysql`.`user`
      WHERE (`Super_priv` = 'Y')
      AND (`User` = LEFT(USER(),LOCATE('@',USER()) - 1 ))
      AND (`Host` = RIGHT(USER(),LENGTH(USER()) - LOCATE('@',USER())))
  ))
  THEN LEAVE thisTrigger;
  END IF;
  IF NEW.`inventory_items__id` IS NULL THEN
  UPDATE `inventory` SET `qty` = `qty` - NEW.`qty` WHERE `id` = NEW.`inventory__id` AND `inventory_items__id` IS NULL;
  END IF;
END
