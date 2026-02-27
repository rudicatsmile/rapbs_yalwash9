<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendWhatsAppTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:whatsapp-test {phone} {message=Test Message from Laravel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test WhatsApp message to a specific phone number using the real service.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');

        $this->info("Attempting to send message to: {$phone}");
        $this->info("Message content: {$message}");

        $service = new WhatsAppService();
        $success = $service->sendMessage($phone, $message);

        if ($success) {
            $this->info('Message sent successfully!');
        } else {
            $this->error('Failed to send message. Check logs for details.');
        }
    }
}
