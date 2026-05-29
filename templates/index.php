<?php
/** @var \OCP\IL10N $l */
\OCP\Util::addScript('lists', 'lists-main');
$urlGenerator = \OCP\Server::get(\OCP\IURLGenerator::class);
\OCP\Util::addHeader('link', [
	'rel'  => 'icon',
	'type' => 'image/svg+xml',
	'href' => $urlGenerator->imagePath('lists', 'favicon.svg'),
]);
?>
<div id="lists-root"></div>
