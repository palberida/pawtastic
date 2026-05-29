<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to staff when the bot hands a conversation off to a human. Carries full
 * context so someone can take over fast: phone, matched ad, transcript, reason,
 * and a click-to-WhatsApp link. The customer gets no acknowledgment.
 */
class MetabotEscalation extends Mailable
{
    use Queueable, SerializesModels;

    public string $phone;
    public ?string $adName;
    public ?string $sourceId;
    public string $reason;
    public array $transcript;
    public string $waLink;

    /**
     * @param  array<array{role:string,text:string}>  $transcript
     */
    public function __construct(string $phone, ?string $adName, ?string $sourceId, string $reason, array $transcript)
    {
        $this->phone      = $phone;
        $this->adName     = $adName;
        $this->sourceId   = $sourceId;
        $this->reason     = $reason;
        $this->transcript = $transcript;
        $this->waLink     = 'https://wa.me/' . preg_replace('/\D+/', '', $phone);
    }

    public function build()
    {
        return $this->subject('Metabot: conversación escalada — ' . $this->phone)
            ->view('emails.metabot_escalation');
    }
}
