<?php
$route = [];
$route['default_controller'] = 'home';
$route['info/(:any)'] = 'home/info/$1';
$route['guru/(:num)'] = 'home/guru/$1';