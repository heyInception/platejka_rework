-- Representative production-shaped dump. It must only be parsed, never executed.
INSERT INTO `wp_users` (`ID`, `user_login`) VALUES
(1,'ignore-before-relevant-tables');

INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(1,'options_skvoznye_bloki','a:2:{i:0;s:14:"swift_problems";i:1;s:8:"audience";}','yes'),
(2,'options_skvoznye_bloki_0_title','Первый заголовок','yes'),
(3, 'options_skvoznye_bloki_0_title', 'Последний заголовок', 'yes'),
(9, 'options_skvoznye_bloki_0_show_block', '1', 'yes'),
(4,'siteurl','https://ignored.example','yes'),
(5,'options_skvoznye_bloki_0_intro','Строка с \'кавычкой\', слешем C:\\Temp и переносом\nниже','yes'),
(6,'options_skvoznye_bloki_broken');

INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(7,'_options_skvoznye_bloki','field_67924d166072f','no'),
(8,'options_skvoznye_bloki_0_multiline','Первая
вторая','yes');

INSERT INTO `wp_posts` (`ID`, `post_name`, `post_status`, `post_type`) VALUES
(2182,'perevody-v-evropu-dlya-yurlicz','publish','page'),
(2183,'chernovik-cross-blocks','draft','page'),
(2184,'ignored-post','publish','post');

INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(10,2182,'skvoznye_bloki','a:2:{i:0;s:14:"swift_problems";i:1;s:8:"audience";}'),
(11,2182,'skvoznye_bloki_0_title','Старый SWIFT'),
(12, 2182, 'skvoznye_bloki_0_title', 'Почему прямой SWIFT не работает'),
(16, 2182, 'skvoznye_bloki_0_show_block', '1'),
(13,2182,'_skvoznye_bloki_0_title','field_title'),
(14,2182,'unrelated_meta','ignore'),
(15,2183,'skvoznye_bloki','a:0:{}');

INSERT INTO `wp_users` (`ID`, `user_login`) VALUES
(1,'ignore-me');
