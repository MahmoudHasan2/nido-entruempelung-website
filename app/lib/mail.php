<?php
declare(strict_types=1);

/**
 * Mail delivery. Uses SMTP when SMTP_HOST is configured in .env, otherwise PHP mail().
 * All header values are stripped of CR/LF to prevent header injection.
 */

function mail_clean_header(string $s): string
{
    return trim(preg_replace('/[\r\n]+/', ' ', $s) ?? '');
}

function mail_encode_subject(string $s): string
{
    return '=?UTF-8?B?' . base64_encode(mail_clean_header($s)) . '?=';
}

function mail_log(string $line): void
{
    @file_put_contents(STORAGE . '/mail.log', '[' . now() . '] ' . $line . "\n", FILE_APPEND | LOCK_EX);
}

/** @return array{ok:bool,error?:string} */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null, ?string $replyName = null): array
{
    $from = mail_clean_header((string) env('MAIL_FROM', 'noreply@nido-entruempelung.de'));
    $fromName = mail_clean_header((string) env('MAIL_FROM_NAME', 'Nido Entrümpelung Website'));
    $to = mail_clean_header($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Ungültige Absender- oder Empfängeradresse.'];
    }

    $headers = [
        'Date: ' . date('r'),
        'From: ' . mail_encode_subject($fromName) . " <$from>",
        'To: <' . $to . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: Nido-Website',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $rn = $replyName ? mail_encode_subject($replyName) . ' ' : '';
        $headers[] = 'Reply-To: ' . $rn . '<' . mail_clean_header($replyTo) . '>';
    }
    $subjectEnc = mail_encode_subject($subject);
    $body = str_replace(["\r\n", "\r"], "\n", $body);

    $host = (string) env('SMTP_HOST', '');
    if ($host === '') {
        $ok = @mail($to, $subjectEnc, str_replace("\n", "\r\n", $body), implode("\r\n", $headers), '-f' . $from);
        if (!$ok) {
            mail_log("mail() failed for subject '$subject'");
            return ['ok' => false, 'error' => 'mail() konnte die Nachricht nicht übergeben.'];
        }
        return ['ok' => true];
    }
    return smtp_send($host, $from, $to, $subjectEnc, $headers, $body);
}

function smtp_send(string $host, string $from, string $to, string $subjectEnc, array $headers, string $body): array
{
    $port = (int) env('SMTP_PORT', '587');
    $secure = strtolower((string) env('SMTP_SECURE', 'tls')); // tls (STARTTLS) | ssl | none
    $user = (string) env('SMTP_USER', '');
    $pass = (string) env('SMTP_PASS', '');

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        mail_log("SMTP connect failed: $errstr ($errno)");
        return ['ok' => false, 'error' => 'SMTP-Verbindung fehlgeschlagen.'];
    }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $c, array $expect) use ($fp, $read): ?string {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        return in_array((int) substr($r, 0, 3), $expect, true) ? $r : null;
    };
    $fail = function (string $why) use ($fp) {
        mail_log('SMTP error: ' . $why);
        @fclose($fp);
        return ['ok' => false, 'error' => 'SMTP-Fehler: ' . $why];
    };

    if ((int) substr($read(), 0, 3) !== 220) {
        return $fail('Begrüßung');
    }
    $ehloHost = preg_replace('/[^a-z0-9.\-]/i', '', (string) parse_url(site_url(), PHP_URL_HOST)) ?: 'localhost';
    if (!$cmd("EHLO $ehloHost", [250])) {
        return $fail('EHLO');
    }
    if ($secure === 'tls') {
        if (!$cmd('STARTTLS', [220]) || !stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return $fail('STARTTLS');
        }
        if (!$cmd("EHLO $ehloHost", [250])) {
            return $fail('EHLO nach STARTTLS');
        }
    }
    if ($user !== '') {
        if (!$cmd('AUTH LOGIN', [334]) || !$cmd(base64_encode($user), [334]) || !$cmd(base64_encode($pass), [235])) {
            return $fail('Anmeldung abgelehnt');
        }
    }
    if (!$cmd("MAIL FROM:<$from>", [250]) || !$cmd("RCPT TO:<$to>", [250, 251])) {
        return $fail('Absender/Empfänger abgelehnt');
    }
    if (!$cmd('DATA', [354])) {
        return $fail('DATA');
    }
    $msg = implode("\r\n", $headers) . "\r\nSubject: $subjectEnc\r\n\r\n";
    $msg .= preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body));
    fwrite($fp, $msg . "\r\n.\r\n");
    if ((int) substr($read(), 0, 3) !== 250) {
        return $fail('Nachricht abgelehnt');
    }
    $cmd('QUIT', [221]);
    @fclose($fp);
    return ['ok' => true];
}
