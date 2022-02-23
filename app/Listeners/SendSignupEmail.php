<?php

namespace App\Listeners;

use App\EmailTemplate;
use App\Events\Subscribe;
use App\Http\Components\Traits\Communication;
use App\Jobs\SendSingleMail;
use App\System;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class SendSignupEmail
{
    use Communication;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(Subscribe $event)
    {
        $advisor = $event->user;
        $email_template = EmailTemplate::where('type', "signup_email")->first();
        if( isset($email_template->send_mail) && !$email_template->send_mail){
            return "";
        }

        $subject = $email_template->subject ?? "You are now subscribed";
        $footer = $email_template->footer ?? "";
        $mail_message = $this->setDynamicValue($advisor, $email_template->body ?? "");
        $send_to_cc = $email_template->send_to_cc ?? false;

        // Save Communication Message
        $this->addCommunicationMessage($mail_message, $subject, $advisor->id, true);
        $msg_params = [
            "advisor" => $advisor, 
            "mail_message" => $mail_message, 
            "mail_footer" => $footer
        ];
        SendSingleMail::dispatch($advisor, $subject, $msg_params, "email.default", $send_to_cc)->delay(1);
    }

    /**
     * Set Dynamic Value into Email Template
     */
    protected function setDynamicValue($advisor, $mail_message){
        $profile_url = route('advisor_profile',['profession' =>Str::slug($advisor->profession->name ?? 'N-A'), 'location' => str::slug($advisor->town ?? "N-A"), 'name_id' => $advisor->id .'-'.($advisor->first_name . '-' . $advisor->last_name)]);
        $profile_url = "<a href='" . $profile_url . "'>".$profile_url."</a>";
        $system = System::first();
        $mail_message = str_replace("{PLATFORM_DATE}", Carbon::now()->format($system->date_format), $mail_message);

        $mail_message = str_replace("{ADVISOR_BILLING_ID}", $advisor->billing_info->id, $mail_message);
        $mail_message = str_replace("{ADVISOR_FIRST_NAME}", $advisor->first_name, $mail_message);
        $mail_message = str_replace("{ADVISOR_SUBSCRIPTION_PLAN}", $advisor->subscription_plan->name ?? "", $mail_message);
        $mail_message = str_replace("{ADVISOR_PROFILE_URL}", $profile_url, $mail_message);
        return $mail_message;
    }
}
