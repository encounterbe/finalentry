<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/guests.php';
require_once APP_ROOT . '/lib/fpdf/fpdf.php';
require_once APP_ROOT . '/lib/phpmailer/Exception.php';
require_once APP_ROOT . '/lib/phpmailer/PHPMailer.php';
require_once APP_ROOT . '/lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function respond(bool $success, string $message = '', int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    respond(false, 'Invalid request. Please reload the page.', 403);
}

$guestId = $_SESSION['ticket_guest_id'] ?? null;
if (!$guestId || empty($_SESSION['ticket_ready'])) {
    respond(false, 'Please verify your ticket first.', 403);
}

$stmt = db()->prepare(
    'SELECT id, first_name, last_name, qr_code, email, last_email_sent_at FROM guests WHERE id = ? AND blocked = 0'
);
$stmt->execute([$guestId]);
$guest = $stmt->fetch();

if (!$guest || $guest['email'] === null) {
    respond(false, 'Ticket not found.', 404);
}

if ($guest['last_email_sent_at'] !== null && time() - strtotime($guest['last_email_sent_at']) < 60) {
    respond(false, 'Please wait at least one minute before requesting the email again.', 429);
}

$qrBase = tempnam(sys_get_temp_dir(), 'qr_');
$qrPath = $qrBase . '.png';
$pdfPath = tempnam(sys_get_temp_dir(), 'ticket_');

try {
    file_put_contents($qrPath, qr_png($guest['qr_code'], 10));

    $pdf = new FPDF('P', 'pt', 'A4');
    $pdf->AddPage();
    $pdf->Image(__DIR__ . '/ticket_template.png', 0, 0, 595, 842);
    $pdf->Image($qrPath, 50, 170, 180, 180);
    $pdf->Output('F', $pdfPath);

    $event = [
        'title' => e(config('event.title')),
        'venue' => e(config('event.venue')),
        'address' => e(config('event.address')),
        'date' => e((new DateTimeImmutable((string) config('event.date')))->format('d.m.Y')),
        'doors' => e(config('event.doors_open')),
    ];
    $name = e($guest['first_name']);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Timeout = 15;
    $mail->Host = (string) config('mail.host');
    $mail->Port = (int) config('mail.port');
    $mail->SMTPAuth = true;
    $mail->Username = (string) config('mail.user');
    $mail->Password = (string) config('mail.pass');
    $mail->SMTPSecure = (string) config('mail.secure');
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->setFrom((string) config('mail.from'), (string) config('mail.from_name'));
    $mail->addAddress($guest['email']);
    $mail->isHTML(true);
    $mail->Subject = 'Your digital ticket – ' . config('event.title');
    $mail->Body = <<<HTML
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
  <h2>Hi {$name}, it's almost time!</h2>
  <p>Attached is your <strong>personal digital ticket</strong> (PDF) for {$event['title']}.</p>
  <p>
    <strong>Location:</strong> {$event['venue']}, {$event['address']}<br>
    <strong>Date:</strong> {$event['date']}<br>
    <strong>Doors open:</strong> {$event['doors']}
  </p>
  <p>Bring the PDF with you, on your phone or printed.</p>
  <hr>
  <p><strong>Entry only with a valid ID.</strong> Tickets are personal and must not be resold or copied.</p>
</body>
</html>
HTML;
    $mail->AltBody = "Hi {$guest['first_name']},\n\nattached is your personal digital ticket for "
        . config('event.title') . ".\n\nLocation: " . config('event.venue') . ', ' . config('event.address')
        . "\nDate: " . config('event.date') . "\nDoors open: " . config('event.doors_open')
        . "\n\nEntry only with a valid ID. Tickets are personal.";
    $mail->addAttachment($pdfPath, 'ticket.pdf');
    $mail->send();

    db()->prepare('UPDATE guests SET last_email_sent_at = NOW() WHERE id = ?')->execute([$guest['id']]);
} catch (Throwable $ex) {
    error_log('Ticket mail failed: ' . $ex->getMessage());
    respond(false, 'The email could not be sent. Please try again later.', 500);
} finally {
    foreach ([$qrBase, $qrPath, $pdfPath] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

respond(true);
