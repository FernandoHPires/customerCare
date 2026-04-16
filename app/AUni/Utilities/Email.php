<?php

namespace App\AUni\Utilities;

use App\Mail\GenericMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Email {

    /**
     * Envia um email HTML.
     *
     * @param array|string $to          Destinatário(s). Ex: 'a@b.com' ou ['a@b.com', 'c@d.com']
     *                                  ou [['email' => 'a@b.com', 'name' => 'Fulano'], ...]
     * @param string       $subject     Assunto
     * @param string       $body        Corpo em HTML
     * @param array        $cc          Cópia. Mesmo formato do $to
     * @param array        $bcc         Cópia oculta. Mesmo formato do $to
     * @param array        $attachments Anexos. Ex: [['path' => '/caminho/arquivo.pdf', 'name' => 'doc.pdf']]
     */
    public static function send(
        array|string $to,
        string       $subject,
        string       $body,
        array        $cc          = [],
        array        $bcc         = [],
        array        $attachments = []
    ): void {
        try {
            Mail::to($to)->send(new GenericMail($subject, $body, $cc, $bcc, $attachments));
        } catch (\Throwable $e) {
            Log::error('Email::send failed', [$e->getMessage()]);
        }
    }
}
