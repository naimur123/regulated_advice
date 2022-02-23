<?php

namespace App\Jobs;

use App\Notifications\EmailNotification;
use App\Notifications\EmailNotificationWithoutCC;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSingleMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $advisor, $subject, $message, $page, $send_to_cc;
    /**
     * Create a new job instance.
     * @param message Will be Assiociative Array When will pass Page | Blade Page 
     * @param $page will be a Blade Page
     * @return void
     */
    public function __construct($advisor, $subject, $message, $page = "", $send_to_cc = false)
    {
        $this->advisor      = $advisor;
        $this->subject      = $subject;
        $this->message      = $message;
        $this->page         = $page;
        $this->send_to_cc   = $send_to_cc;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $advisor = $this->advisor;
        if($this->send_to_cc){
            $advisor->notify(new EmailNotification($this->subject, $this->message, $this->page));
        }else{
            $advisor->notify(new EmailNotificationWithoutCC($this->subject, $this->message, $this->page));
        }
    }
}
