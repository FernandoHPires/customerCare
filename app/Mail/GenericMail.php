<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $htmlBody;
    public array  $ccAddresses;
    public array  $bccAddresses;
    public array  $emailAttachments; // renomeado: 'attachments' conflita com a classe pai Mailable

    /**
     * @param string $subject
     * @param string $htmlBody
     * @param array  $cc               [['email' => '...', 'name' => '...'], ...]  ou ['email@...', ...]
     * @param array  $bcc              Mesmo formato do cc
     * @param array  $emailAttachments [['path' => '/full/path/to/file.pdf', 'name' => 'arquivo.pdf'], ...]
     */
    public function __construct(
        string $subject,
        string $htmlBody,
        array  $cc               = [],
        array  $bcc              = [],
        array  $emailAttachments = []
    ) {
        $this->subject          = $subject;
        $this->htmlBody         = $htmlBody;
        $this->ccAddresses      = $cc;
        $this->bccAddresses     = $bcc;
        $this->emailAttachments = $emailAttachments;
    }

    public function build(): static
    {
        $mail = $this->html($this->htmlBody);

        foreach ($this->ccAddresses as $cc) {
            if (is_array($cc)) {
                $mail->cc($cc['email'], $cc['name'] ?? null);
            } else {
                $mail->cc($cc);
            }
        }

        foreach ($this->bccAddresses as $bcc) {
            if (is_array($bcc)) {
                $mail->bcc($bcc['email'], $bcc['name'] ?? null);
            } else {
                $mail->bcc($bcc);
            }
        }

        foreach ($this->emailAttachments as $attachment) {
            $mail->attach($attachment['path'], [
                'as'   => $attachment['name'] ?? basename($attachment['path']),
                'mime' => $attachment['mime'] ?? null,
            ]);
        }

        return $mail;
    }
}
