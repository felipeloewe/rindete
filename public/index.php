<?php
require dirname(__DIR__) . '/includes/init.php';
require dirname(__DIR__) . '/includes/sitio.php';

web_open(web_home_seo());
render('web/home');
web_close();
