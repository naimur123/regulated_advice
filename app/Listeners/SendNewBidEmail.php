<?php

namespace App\Listeners;

use App\EmailTemplate;
use App\Events\AuctionBid;
use App\Http\Components\Traits\Communication;
use App\Jobs\SendSingleMail;
use App\System;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewBidEmail
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

    /**
     * Handle the event.
     *
     * @param  AuctionBid  $event
     * @return void
     */
    public function handle(AuctionBid $event)
    {
        $auction = $event->auction;
        $email_template = EmailTemplate::where('type', "auction_bid_email")->first();
        if( isset($email_template->send_mail) && !$email_template->send_mail){
            return "";
        }

        $subject = $email_template->subject ?? "Auction Bid";
        $mail_body = $email_template->body ?? "You are now the highest bidder";
        $footer = $email_template->footer ?? "Thank You.";
        $send_to_cc = $email_template->send_to_cc ?? false;
        $advisor = User::find($auction->max_bidder_id);
        $subject = $this->setDynamicValue($auction, $advisor, $subject);
        $mail_body = $this->setDynamicValue($auction, $advisor, $mail_body);
        
        // Save Communication Message
        $this->addCommunicationMessage($mail_body, $subject, $advisor->id, true);

        $message = ["mail_message" => $mail_body, "mail_footer" => $footer];
        SendSingleMail::dispatch($advisor, $subject, $message, "email.auction", $send_to_cc)->delay(1);
    }

    /**
     * Set Dynamic Value into Email Template
     */
    protected function setDynamicValue($auction, $advisor, $mail_message){
        $system = System::first();
        $mail_message = str_replace("{PLATFORM_DATE}", Carbon::now()->format($system->date_format), $mail_message);

        $mail_message = str_replace("{PLATFORM_DATE}", date('Y-m-d h:i A'), $mail_message);
        $mail_message = str_replace("{ADVISOR_BILLING_ID}", $advisor->billing_info->id, $mail_message);
        $mail_message = str_replace("{ADVISOR_FIRST_NAME}", $advisor->first_name, $mail_message);
        $mail_message = str_replace("{AUCTION_POSTCODE}", $auction->post_code, $mail_message);
        return $mail_message;
    }
}
