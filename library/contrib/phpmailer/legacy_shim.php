<?php
// PHPMailer 6.x → 전역 클래스명 호환 shim
// mail.php 및 기존 호출 코드에서 'new PHPMailer()', 'new SMTP()' 등을 변경 없이 사용 가능.
// PHPMailer 6.x: PHP 8.x 완전 지원, each() 제거 대응 (STAGE 2 v1.81)

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
require_once __DIR__ . '/src/POP3.php';

if (!class_exists('PHPMailer', false)) {
    class_alias(PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer');
}
if (!class_exists('SMTP', false)) {
    class_alias(PHPMailer\PHPMailer\SMTP::class, 'SMTP');
}
if (!class_exists('POP3', false)) {
    class_alias(PHPMailer\PHPMailer\POP3::class, 'POP3');
}
if (!class_exists('phpmailerException', false)) {
    class_alias(PHPMailer\PHPMailer\Exception::class, 'phpmailerException');
}
