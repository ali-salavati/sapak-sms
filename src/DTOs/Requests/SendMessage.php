<?php

namespace Sapak\Sms\DTOs\Requests;

use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * DTO (Data Transfer Object) for sending a new SMS message.
 * Validates input upon creation.
 */
class SendMessage
{
    /**
     * @param string $from Sender number.
     * @param string[] $to Array of recipient numbers (max 100).
     * @param string $text Message content.
     * @param bool $isFlash Whether the message is flash.
     * @param string|null $sendAt ISO-8601 datetime string for scheduled sending.
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $from,
        public readonly array $to,
        public readonly string $text,
        public readonly bool $isFlash = false,
        public readonly ?string $sendAt = null
    ) {
        // Guard clauses for input validation
        if (empty($from)) {
            throw new InvalidArgumentException('Sender "from" cannot be empty.');
        }
        if (empty($to)) {
            throw new InvalidArgumentException('Recipients "to" array cannot be empty.');
        }
        if (count($to) > 100) {
            throw new InvalidArgumentException('Cannot send to more than 100 recipients at once.');
        }
        if (empty($text)) {
            throw new InvalidArgumentException('Message "text" cannot be empty.');
        }

        // Validate scheduled send time
        if ($this->sendAt !== null) {
            $this->validateSendAt($this->sendAt);
        }
    }

    /**
     * Validate the sendAt datetime according to API rules.
     * 1. Must be ISO-8601 format.
     * 2. Cannot be more than one year in the future.
     * 3. Cannot be in the past (buffer of 5 minutes allowed).
     *
     * @param string $sendAt
     * @throws InvalidArgumentException
     */
    private function validateSendAt(string $sendAt): void
    {
        /*
        try {
            $sendAtDate = new \DateTime($sendAt);
        } catch (\Exception $e) {
            // 1. بررسی فرمت
            throw new InvalidArgumentException(
                'Invalid "sendAt" date format. Must be an ISO-8601 string (e.g., 2014-02-03T20:36:27Z).',
                0,
                $e
            );
        }
        */

        // نقد: تکیه بر سازنده عمومی (\DateTime) تنبلی است و می‌تواند فرمت‌های
        // مبهم یا ناخواسته را بپذیرد. ما باید به صراحت فرمت RFC3339 (که ATOM
        // نماینده آن است) را که در مستندات (e.g., ...Z) آمده، اعتبارسنجی کنیم.
        // ما از ثابت منسوخ شده DateTime::ISO8601 استفاده نمی‌کنیم.

        // (Y-m-d\TH:i:sP) - این فرمت اصلی RFC3339 است
        $sendAtDate = \DateTime::createFromFormat(\DateTime::ATOM, $sendAt);

        // (Y-m-d\TH:i:s.uP) - این فرمت RFC3339 با میکروثانیه‌ها است
        if ($sendAtDate === false) {
            $sendAtDate = \DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $sendAt);
        }

        if ($sendAtDate === false) {
             throw new InvalidArgumentException(
                'Invalid "sendAt" date format. Must be a valid RFC3339 / ATOM string (e.g., 2014-02-03T20:36:27Z).'
            );
        }


        // 2. بررسی سقف زمانی (بزرگتر از یک سال)
        // بر اساس مستندات: "تاریخ ارسال نباید بزرگتر از یکسال از زمان ایجاد پیام باشد"
        $oneYearFromNow = (new \DateTime())->modify('+1 year');

        if ($sendAtDate > $oneYearFromNow) {
            throw new InvalidArgumentException('Scheduled send time "sendAt" cannot be more than one year in the future.');
        }

        // 3. بررسی کف زمانی (گذشته)
        // مستندات چیزی در مورد گذشته نگفته، اما ارسال به 5 دقیقه قبل منطقی نیست
        // یک بافر 5 دقیقه‌ای برای تاخیرهای احتمالی شبکه در نظر می‌گیریم
        $fiveMinutesAgo = (new \DateTime())->modify('-5 minutes');
        if ($sendAtDate < $fiveMinutesAgo) {
            throw new InvalidArgumentException('Scheduled send time "sendAt" cannot be in the past.');
        }
    }
}
