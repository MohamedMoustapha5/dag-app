<?php

namespace App\Support\Messagerie;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends the application's e-mails through Brevo's transactional e-mail API
 * (MAIL_MAILER=brevo). Every e-mail Laravel builds — notifications, password
 * reset, address verification — goes through it unchanged.
 *
 * @see https://developers.brevo.com/reference/sendtransacemail
 */
class BrevoTransport extends AbstractTransport
{
    public const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(private ?string $cleApi)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (blank($this->cleApi)) {
            throw new TransportException('Brevo : la clé API est absente (BREVO_API_KEY).');
        }

        $original = $message->getOriginalMessage();

        // Brevo's API takes structured e-mails; a raw MIME message cannot be split into its fields.
        if (! $original instanceof Message) {
            throw new TransportException('Brevo : seuls les e-mails structurés peuvent être envoyés (message brut reçu).');
        }

        $email = MessageConverter::toEmail($original);

        try {
            $reponse = Http::withHeaders(['api-key' => $this->cleApi])
                ->acceptJson()
                ->timeout(15)
                ->post(self::ENDPOINT, $this->contenu($email, $message));
        } catch (ConnectionException $erreur) {
            throw new TransportException('Brevo injoignable : '.$erreur->getMessage(), 0, $erreur);
        }

        if ($reponse->failed()) {
            throw new TransportException(sprintf(
                'Brevo a refusé l’e-mail (%d) : %s',
                $reponse->status(),
                $reponse->json('message') ?? $reponse->body(),
            ), $reponse->status());
        }

        if (is_string($identifiant = $reponse->json('messageId'))) {
            $message->setMessageId($identifiant);
        }
    }

    /**
     * Brevo's request body, built from the e-mail Laravel prepared.
     *
     * @return array<string, mixed>
     */
    private function contenu(Email $email, SentMessage $message): array
    {
        $copies = [...$email->getCc(), ...$email->getBcc()];
        $destinataires = array_filter(
            $message->getEnvelope()->getRecipients(),
            fn (Address $adresse) => ! in_array($adresse, $copies),
        );

        $contenu = [
            'sender' => $this->adresse($message->getEnvelope()->getSender()),
            'to' => $this->adresses($destinataires),
            'subject' => $email->getSubject() ?? '',
        ];

        foreach (['cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $champ => $adresses) {
            if ($adresses !== []) {
                $contenu[$champ] = $this->adresses($adresses);
            }
        }

        if (($repondreA = $email->getReplyTo()) !== []) {
            $contenu['replyTo'] = $this->adresse($repondreA[0]);
        }

        if (is_string($html = $email->getHtmlBody())) {
            $contenu['htmlContent'] = $html;
        }

        if (is_string($texte = $email->getTextBody())) {
            $contenu['textContent'] = $texte;
        }

        foreach ($email->getAttachments() as $pieceJointe) {
            $contenu['attachment'][] = [
                'name' => $pieceJointe->getFilename() ?? 'piece-jointe',
                'content' => base64_encode($pieceJointe->getBody()),
            ];
        }

        // Only custom headers: Brevo sets the standard ones itself.
        foreach ($email->getHeaders()->all() as $entete) {
            if (str_starts_with(strtolower($entete->getName()), 'x-')) {
                $contenu['headers'][$entete->getName()] = $entete->getBodyAsString();
            }
        }

        return $contenu;
    }

    /**
     * @param  iterable<Address>  $adresses
     * @return list<array{email: string, name?: string}>
     */
    private function adresses(iterable $adresses): array
    {
        $liste = [];

        foreach ($adresses as $adresse) {
            $liste[] = $this->adresse($adresse);
        }

        return $liste;
    }

    /**
     * @return array{email: string, name?: string}
     */
    private function adresse(Address $adresse): array
    {
        return $adresse->getName() !== ''
            ? ['email' => $adresse->getAddress(), 'name' => $adresse->getName()]
            : ['email' => $adresse->getAddress()];
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
