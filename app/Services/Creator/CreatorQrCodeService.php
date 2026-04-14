<?php

declare(strict_types=1);

namespace App\Services\Creator;

use App\Models\Creator;
use Illuminate\Support\Str;

class CreatorQrCodeService
{
    public function buildVCard(Creator $creator): string
    {
        $profileUrl = url($creator->public_url);
        $name = $this->escape($creator->name);
        $nameParts = $this->splitName($creator->name);

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            "FN:{$name}",
            "N:{$this->escape($nameParts['last'])};{$this->escape($nameParts['first'])};;;",
            'ORG:Topping Africa',
            "TITLE:{$this->escape(ucfirst((string) $creator->category))} Creator",
        ];

        if ($creator->bio) {
            $lines[] = 'NOTE:' . $this->escape(Str::limit($creator->bio, 300));
        }

        if ($creator->contact_email) {
            $lines[] = "EMAIL;TYPE=INTERNET:{$creator->contact_email}";
        }

        $lines[] = "URL:{$profileUrl}";

        foreach ($creator->socialLinks as $link) {
            if ($link->url) {
                $lines[] = "URL;TYPE={$this->escape((string) $link->platform)}:{$link->url}";
            }
        }

        if ($creator->country) {
            $lines[] = "ADR:;;;;;;{$this->escape($creator->country)}";
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines);
    }

    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [$full];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? end($parts) : '';

        return ['first' => $first, 'last' => $last];
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', "\n"], ['\,', '\;', '\n'], $value);
    }
}
