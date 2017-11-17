SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


-- -----------------------------------------------------
-- Table `armbreaker_fics`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `armbreaker_fics` (
  `tid` INT(10) UNSIGNED NOT NULL,
  `title` MEDIUMTEXT NOT NULL,
  `lastUpdated` TIMESTAMP NOT NULL,
  PRIMARY KEY (`tid`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `armbreaker_users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `armbreaker_users` (
  `uid` INT(10) UNSIGNED NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `lastUpdated` TIMESTAMP NOT NULL,
  PRIMARY KEY (`uid`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `armbreaker_posts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `armbreaker_posts` (
  `pid` INT(10) UNSIGNED NOT NULL,
  `tid` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `postTime` TIMESTAMP NOT NULL,
  `lastUpdated` TIMESTAMP NOT NULL,
  PRIMARY KEY (`pid`),
  INDEX `tid` (`tid` ASC),
  CONSTRAINT `armbreaker_posts_ibfk_1`
    FOREIGN KEY (`tid`)
    REFERENCES `armbreaker_fics` (`tid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `armbreaker_likes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `armbreaker_likes` (
  `pid` INT(10) UNSIGNED NOT NULL,
  `uid` INT(10) UNSIGNED NOT NULL,
  `likeTime` TIMESTAMP NOT NULL,
  `lastUpdated` TIMESTAMP NOT NULL,
  PRIMARY KEY (`pid`, `uid`),
  INDEX `uid` (`uid` ASC),
  CONSTRAINT `armbreaker_likes_ibfk_1`
    FOREIGN KEY (`uid`)
    REFERENCES `armbreaker_users` (`uid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `armbreaker_likes_ibfk_2`
    FOREIGN KEY (`pid`)
    REFERENCES `armbreaker_posts` (`pid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
