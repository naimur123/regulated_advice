<?php

namespace App\Listeners;

use App\AuctionBid as AppAuctionBid;
use App\EmailTemplate;
use App\Events\AuctionBid;
use App\Http\Components\Traits\Communication;
use App\Jobs\SendMail;
use App\Jobs\SendSingleMail;
use App\System;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLowerBidEmail
{
    use Communication;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        
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
        $email_template = EmailTemplate::where('type', "auction_outbid_email")->first();
        if( isset($email_template->send_mail) && !$email_template->send_mail){
            return "";
        }
        
        $subject = $email_template->subject ?? "Auction Outbid";
        $raw_mail_body = $email_template->body ?? "You have been outbid";
        $footer = $email_template->footer ?? "Thank You.";
        $advisor_arr = AppAuctionBid::where('auction_id', $auction->id)->where('bidder_id', '!=', $auction->max_bidder_id)->select("bidder_id as advisor_id")->get()->pluck('advisor_id')->toArray();  
        $send_to_cc = $email_template->send_to_cc ?? false;

        foreach($advisor_arr as $advisor_id){
            $advisor = User::find($advisor_id);
            $subject = $this->setDynamicValue($auction, $advisor, $subject);
            $mail_body = $this->setDynamicValue($auction, $advisor, $raw_mail_body);
            
            // Save Communication Message
            $this->addCommunicationMessage($mail_body, $subject, $advisor->id, true);

            $message = ["mail_message" => $mail_body, "mail_footer" => $footer];
            SendSingleMail::dispatch($advisor, $subject, $message, "email.auction", $send_to_cc)->delay(1); 
        }
    }

    /**
     * Set Dynamic Value into Email Template
     */
    protected function setDynamicValue($auction, $advisor, $mail_message){
        
        $system = System::first();
        $mail_message = str_replace("{PLATFORM_DATE}", Carbon::now()->format($system->date_format), $mail_message);

        $mail_message = str_replace("{ADVISOR_BILLING_ID}", $advisor->billing_info->id, $mail_message);
        $mail_message = str_replace("{ADVISOR_FIRST_NAME}", $advisor->first_name, $mail_message);
        $mail_message = str_replace("{AUCTION_POSTCODE}", $auction->post_code, $mail_message);
        return $mail_message;
    }
}
