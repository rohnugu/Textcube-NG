<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
$IV = array(
	'FILES' => array(
		'Filedata' => array('file')
	)
);

require ROOT . '/library/preprocessor.php';
requireModel("blog.attachment");
$file = array_pop($_FILES);
$attachment = addAttachment($blogid, $suri['id'], $file);
echo "&success";
?>
