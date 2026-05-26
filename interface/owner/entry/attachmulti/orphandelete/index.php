<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
define('__TEXTCUBE_CUSTOM_HEADER__', true);
$IV = array();
require ROOT . '/library/preprocessor.php';
requireModel('blog.attachment');
session_write_close();

deleteAttachments(intval($blogid), 0);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('result' => 'ok'));
?>
