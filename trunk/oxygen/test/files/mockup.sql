--
-- `test_products`
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `test_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) CHARACTER SET utf8 NOT NULL,
  `one_to_one` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `one_to_one` (`one_to_one`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;


INSERT INTO `test_products` (`id`, `value`, `one_to_one`) VALUES
(1, 'tv', 1),
(2, 'computer', 1),
(3, 'bike', 2),
(4, 'snowboard', 2);

--
-- `test_2`
--

CREATE TABLE IF NOT EXISTS `test_2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

INSERT INTO `test_2` (`id`, `value`) VALUES
(1, 'electronics'),
(2, 'sports');

--
-- `test_3`
--

CREATE TABLE IF NOT EXISTS `test_3` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

INSERT INTO `test_3` (`id`, `value`) VALUES
(1, 'plasma'),
(2, 'led'),
(3, 'lcd'),
(4, 'mountain'),
(5, 'water');

--
-- `test1_test3_rel`
--

CREATE TABLE IF NOT EXISTS `test1_test3_rel` (
  `test_products_id` int(11) NOT NULL,
  `test_3_id` int(11) NOT NULL,
  KEY `test_products_id` (`test_products_id`),
  KEY `test_3_id` (`test_3_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `test1_test3_rel` (`test_products_id`, `test_3_id`) VALUES
(1, 2),
(1, 1),
(2, 1),
(3, 4);

ALTER TABLE `test1_test3_rel`
  ADD CONSTRAINT `test1_test3_rel_ibfk_2` FOREIGN KEY (`test_3_id`) REFERENCES `test_3` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test1_test3_rel_ibfk_1` FOREIGN KEY (`test_products_id`) REFERENCES `test_products` (`id`) ON DELETE CASCADE;

ALTER TABLE `test_products`
  ADD CONSTRAINT `test_products_ibfk_1` FOREIGN KEY (`one_to_one`) REFERENCES `test_2` (`id`) ON DELETE CASCADE;
