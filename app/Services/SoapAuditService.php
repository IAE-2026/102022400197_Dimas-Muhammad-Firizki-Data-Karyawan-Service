<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SoapAuditService
{
    public function submit(string $token, string $activityName, array $payload): string
    {
        $teamId = htmlspecialchars((string) config('iae.team_id'), ENT_XML1);
        $activity = htmlspecialchars($activityName, ENT_XML1);
        $content = str_replace(']]>', ']]]]><![CDATA[>', json_encode($payload, JSON_THROW_ON_ERROR));

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$teamId}</iae:TeamID>
      <iae:ActivityName>{$activity}</iae:ActivityName>
      <iae:LogContent><![CDATA[{$content}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;

        $response = Http::withToken($token)
            ->withBody($xml, 'text/xml; charset=UTF-8')
            ->timeout(config('iae.http_timeout'))
            ->post(config('iae.soap_url'))
            ->throw();

        $receipt = $this->receiptNumber($response->body());

        if ($receipt === null) {
            throw new RuntimeException('SOAP audit tidak mengembalikan ReceiptNumber.');
        }

        return $receipt;
    }

    private function receiptNumber(string $xml): ?string
    {
        if (preg_match('/<(?:[A-Za-z0-9_-]+:)?ReceiptNumber\b[^>]*>([^<]+)<\/(?:[A-Za-z0-9_-]+:)?ReceiptNumber>/i', $xml, $matches) !== 1) {
            return null;
        }

        return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_XML1));
    }
}
