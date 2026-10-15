<?php

namespace App\Support;

class RegisteredMailbox
{
    /**
     * Deliver one message to the mail server for the volunteer's registered address.
     */
    public static function send(string $to, string $subject, string $html, string $fromAddress, string $fromName): void
    {
        $to = trim($to);
        $domain = strtolower((string) strrchr($to, '@'));
        $domain = ltrim($domain, '@');
        if ($domain === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('The volunteer account does not have a valid email address.');
        }

        $hosts = self::mailHosts($domain);
        $lastError = 'The mail server for '.$domain.' did not accept the message.';
        foreach ($hosts as $host) {
            try {
                self::deliver($host, $to, $subject, $html, $fromAddress, $fromName);
                return;
            } catch (\Throwable $exception) {
                $lastError = $exception->getMessage();
            }
        }

        throw new \RuntimeException($lastError);
    }

    /**
     * @return array<int, string>
     */
    private static function mailHosts(string $domain): array
    {
        $records = dns_get_record($domain, DNS_MX) ?: [];
        usort($records, fn (array $left, array $right) => ($left['pri'] ?? 0) <=> ($right['pri'] ?? 0));
        $hosts = [];
        foreach ($records as $record) {
            if (!empty($record['target'])) {
                $hosts[] = $record['target'];
            }
        }

        return $hosts === [] ? [$domain] : array_values(array_unique($hosts));
    }

    /**
     * @return array<int, string>
     */
    private static function ipv4Targets(string $host): array
    {
        $records = dns_get_record($host, DNS_A) ?: [];
        $targets = [];
        foreach ($records as $record) {
            if (!empty($record['ip'])) {
                $targets[] = $record['ip'];
            }
        }

        return $targets === [] ? [$host] : $targets;
    }

    private static function deliver(string $host, string $to, string $subject, string $html, string $fromAddress, string $fromName): void
    {
        $errno = 0;
        $errstr = '';
        $socket = false;
        foreach (self::ipv4Targets($host) as $target) {
            $socket = @fsockopen($target, 25, $errno, $errstr, 15);
            if ($socket) {
                break;
            }
        }
        if (!$socket) {
            throw new \RuntimeException('Could not reach '.$host.' ('.$errstr.').');
        }

        stream_set_timeout($socket, 20);
        try {
            self::expect($socket, self::read($socket), [220]);
            self::expect($socket, self::write($socket, 'EHLO volunteerhub.local'), [250]);
            self::expect($socket, self::write($socket, 'MAIL FROM:<'.$fromAddress.'>'), [250]);
            self::expect($socket, self::write($socket, 'RCPT TO:<'.$to.'>'), [250, 251]);
            self::expect($socket, self::write($socket, 'DATA'), [354]);
            fwrite($socket, self::message($to, $subject, $html, $fromAddress, $fromName)."\r\n.\r\n");
            self::expect($socket, self::read($socket), [250]);
            self::write($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private static function message(string $to, string $subject, string $html, string $fromAddress, string $fromName): string
    {
        $headers = [
            'Date: '.date('r'),
            'From: '.self::headerName($fromName).' <'.$fromAddress.'>',
            'To: <'.$to.'>',
            'Message-ID: <'.bin2hex(random_bytes(16)).'@volunteerhub.ph>',
            'Subject: '.self::headerText($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $body = preg_replace("/(?m)^\./", '..', str_replace(["\r\n", "\r"], "\n", $html));
        $body = str_replace("\n", "\r\n", $body ?? $html);

        return implode("\r\n", $headers)."\r\n\r\n".$body;
    }

    private static function headerName(string $name): string
    {
        $clean = trim(str_replace(["\r", "\n"], '', $name));

        return '=?UTF-8?B?'.base64_encode($clean).'?=';
    }

    private static function headerText(string $value): string
    {
        $clean = trim(str_replace(["\r", "\n"], '', $value));
        if (preg_match('/[^\x20-\x7E]/', $clean)) {
            return '=?UTF-8?B?'.base64_encode($clean).'?=';
        }

        return $clean;
    }

    private static function write($socket, string $command): string
    {
        fwrite($socket, $command."\r\n");

        return self::read($socket);
    }

    private static function read($socket): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($response === '') {
            throw new \RuntimeException('The mail server closed the connection.');
        }

        return $response;
    }

    private static function expect($socket, string $response, array $codes): void
    {
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException(trim($response));
        }
    }
}
