<?php

namespace App\Support;

use App\Channels\DynamicSmsChannel;
use App\Models\Customer;

/**
 * Builds Laravel notification channel lists for tenant customer messages.
 */
class CustomerNotificationChannels
{
    private bool $includeMail = false;

    private bool $includeSms = false;

    private function __construct(private readonly Customer $customer) {}

    public static function for(Customer $customer): self
    {
        return new self($customer);
    }

    public function withMail(bool $enabled = true): self
    {
        $this->includeMail = $enabled;

        return $this;
    }

    public function withSms(bool $enabled = true): self
    {
        $this->includeSms = $enabled;

        return $this;
    }

    /**
     * @return list<string|class-string>
     */
    public function resolve(): array
    {
        $channels = [];

        if ($this->includeMail && ! empty($this->customer->email)) {
            $channels[] = 'mail';
        }

        if ($this->includeSms && $this->shouldSendSms()) {
            $channels[] = DynamicSmsChannel::class;
        }

        return $channels;
    }

    private function shouldSendSms(): bool
    {
        if (empty($this->customer->phone_number)) {
            return false;
        }

        $isp = $this->customer->relationLoaded('isp')
            ? $this->customer->isp
            : $this->customer->isp()->first();

        $config = IspSmsConfiguration::forIsp($isp);

        return $config->isDeliverable();
    }
}
