<?php

namespace Symfony\Component\Mailer\Bridge\GmailApi\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GmailApiTransport extends AbstractApiTransport
{
    private const HOST = 'api.postmarkapp.com';

    private string $key;

    private $messageStream;

    public function __construct(string $key, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->key = $key;

        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('postmark+api://%s', $this->getEndpoint()).($this->messageStream ? '?message_stream='.$this->messageStream : '');
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $response = new GmailApiResponse;
        
        $payload = $this->getPayload($email, $envelope);

        $sendnow = 'execute here';

        // $sentMessage->setMessageId($result['MessageID']);

        return $response;
    }

    private function getPayload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'From' => $envelope->getSender()->toString(),
            'To' => implode(',', $this->stringifyAddresses($this->getRecipients($email, $envelope))),
            'Cc' => implode(',', $this->stringifyAddresses($email->getCc())),
            'Bcc' => implode(',', $this->stringifyAddresses($email->getBcc())),
            'ReplyTo' => implode(',', $this->stringifyAddresses($email->getReplyTo())),
            'Subject' => $email->getSubject(),
            'TextBody' => $email->getTextBody(),
            'HtmlBody' => $email->getHtmlBody(),
            'Attachments' => $this->getAttachments($email),
        ];

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'subject', 'content-type', 'sender', 'reply-to'];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }

            if ($header instanceof TagHeader) {
                if (isset($payload['Tag'])) {
                    throw new TransportException('Postmark only allows a single tag per email.');
                }

                $payload['Tag'] = $header->getValue();

                continue;
            }

            if ($header instanceof MetadataHeader) {
                $payload['Metadata'][$header->getKey()] = $header->getValue();

                continue;
            }

            $payload['Headers'][] = [
                'Name' => $header->getName(),
                'Value' => $header->getBodyAsString(),
            ];
        }

        return $payload;
    }

    private function getAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $disposition = $headers->getHeaderBody('Content-Disposition');

            $att = [
                'Name' => $filename,
                'Content' => $attachment->bodyToString(),
                'ContentType' => $headers->get('Content-Type')->getBody(),
            ];

            if ('inline' === $disposition) {
                $att['ContentID'] = 'cid:'.$filename;
            }

            $attachments[] = $att;
        }

        return $attachments;
    }

    private function getEndpoint(): ?string
    {
        return ($this->host ?: self::HOST).($this->port ? ':'.$this->port : '');
    }

    /**
     * @return $this
     */
    public function setMessageStream(string $messageStream): static
    {
        $this->messageStream = $messageStream;

        return $this;
    }
}
