<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
///
/// ---- Modification Notice (GPL §2(a)) ----
/// Modified 2026 by @deokio for PHP 8.5 compatibility,
/// performed with AI assistance (Anthropic Claude) under human review.
/// Changes consist primarily of mechanical PHP migration transformations
/// per the official PHP upgrade documentation.
/// No additional copyright is asserted over these modifications.
/// See CHANGELOG.md and SECURITY.md for full modification history.
require ROOT . '/library/preprocessor.php';

$IV = array(
	'GET' => array(
		'userid' => array('id')
	) 
);
requireStrictRoute();
requirePrivilege('group.creators');

$result = User::remove($_GET['userid']);
if ($result===true) {
	Respond::PrintResult(array('error' => 0));
}
else {
	Respond::PrintResult(array('error' => -1 , 'result' =>$result));
}
?>
